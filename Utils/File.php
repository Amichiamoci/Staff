<?php
namespace Amichiamoci\Utils;

use Mpdf\Mpdf;

class File
{
    public static function GetMimeType(string $filename) : string
    {
        try {
            $finfo = finfo_open(flags: FILEINFO_MIME_TYPE);
            if (!$finfo)
            {
                throw new \Exception(message: "Invalid file");
            }
            $mime = finfo_file(finfo: $finfo, filename: $filename);
            if (!$mime)
            {
                throw new \Exception(message: "Invalid result");
            }
            finfo_close(finfo: $finfo);
            return $mime;
        } catch (\Exception $ex) {
            return "application/octet-stream";
        }
    }

    public static function ServerPath(string $db_path): string|false
    {
        if (!isset($db_path))
            return "";

        if (!str_starts_with(haystack: $db_path, needle: DIRECTORY_SEPARATOR))
        {
            $db_path = DIRECTORY_SEPARATOR . $db_path;
        }

        return realpath(path: SERVER_UPLOAD_PATH . $db_path);
    }

    public static function AbsoluteToDbPath(string $server_path): string {
        if (!str_starts_with(haystack: $server_path, needle: SERVER_UPLOAD_PATH)) {
            return $server_path;
        } 
        return substr(string: $server_path, offset: strlen(string: SERVER_UPLOAD_PATH));
    }

    public static function Exists(string $db_path): bool
    {
        $res = self::ServerPath(db_path: $db_path);
        if (!$res) 
            return false;
        return is_file(filename: $res);
    }
    public static function Delete(string $server_path): bool
    {
        return file_exists(filename: $server_path) && unlink(filename: $server_path);
    }

    public static function Size(string $db_path): string
    {
        if (!self::Exists(db_path: $db_path)) return '';
        $size = filesize(filename: self::ServerPath(db_path: $db_path));
        if (!$size) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count(value: $units) - 1) {
            $size = (int)($size / 1024);
            $i++;
        }
        return "$size " . $units[$i];
    }

    public static function GetExportUrl(string $path): string
    {
        return INSTALLATION_PATH . "/file?name=$path";
    }

    public static function RemoveCharacters(string $str) : string
    {
        $exploded = str_split(string: $str);
        $regex = "/" . join(separator: "|", array: str_split(string: self::$FILE_NAME_CHAR_WHITELIST)) . "/";
        return join(separator: "", array: preg_grep(pattern: $regex, array: $exploded));
    }

    function Spaces2Underscores(string $str) : string
    {
        return preg_replace(pattern: "/\s+/", replacement: "_", subject: $str);
    }

    function CapitalizeWords(string $str) : string
    {
        $parts = explode(separator: " ", string: $str);
        
        $parts = array_filter(array: $parts, callback: function (string $s): bool {
            return strlen(string: $s) > 0;
        });
        $parts = array_map(callback: function (string $str): string {
            return strtoupper(string: substr(string: $str, offset: 0, length: 1)) . strtolower(string: substr(string: $str, offset: 1));
        }, array: $parts);

        return join(separator: " ", array: $parts);
    }

    public static array $ALLOWED_EXT = [
        "jpg", 
        "jpeg", 
        "png",
        "webp",
        "heic",
        "heif",
        "pdf",        
        "doc",
        "docx",
    ];
    public static function ALLOWED_EXT_DOTS(): string {
        return join(
            separator: ', ',
            array: array_map(
                callback: function(string $s): string { return ".$s"; }, 
                array: self::$ALLOWED_EXT
            )
        );
    } 

    public static string $FILE_NAME_CHAR_WHITELIST = 
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . 
        'abcdefghijklmnopqrstuvwxyz' . 
        '0123456789 _';

    public static function MAX_SIZE(): int {
        return 
            array_key_exists(key: 'FILE_MAX_SIZE', array: $_ENV) ?
                $_ENV['FILE_MAX_SIZE'] : 
                10 * 1024 * 1024;
    }
    public static function ListDirectory(string $dir): array {
        $result = [];
        $files = scandir(directory: $dir);
    
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
    
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir(filename: $path)) {
                $result[$file] = self::ListDirectory(dir: $path);
            } else {
                $result[$file] = $file;
            }
        }
    
        return $result;
    }

    public static function UploadingFiles(string $form_name): array {
        if (!array_key_exists(key: $form_name, array: $_FILES)) {
            return [];
        }

        if (is_array(value: $_FILES[$form_name]['name'])) {
            // Handling multiple files
            return self::UploadingFilesParse(files: $_FILES[$form_name]);
        }

        // Handling only one file
        return [ $_FILES[$form_name] ];
    }
    private static function UploadingFilesParse(array $files): array {
        $result = [];
        $num_files = count(value: $files['name']);
        $keys = array_keys($files);
        for ($i = 0; $i < $num_files; $i++)
        {
            $file = [];
            foreach ($keys as $key)
            {
                $file[$key] = $files[$key][$i];
            }
            $result[] = $file;
        }
        return $result;
    }

    public static function IsUploadError(mixed $file): bool {
        return 
            array_key_exists(key: 'error', array: $file) && 
            (int)$file['error'] !== UPLOAD_ERR_OK;
    }

    public static function IsUploadValidFileSize(mixed $file): bool {
        return
            array_key_exists(key: 'size', array: $file) &&
            (int)$file['size'] <= self::MAX_SIZE(); 
    }

    public static function IsAllowedExtension(mixed $file): bool {
        if (!array_key_exists(key: 'name', array: $file))
            return false;
        $ext = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
        return in_array(needle: $ext, haystack: self::$ALLOWED_EXT);
    }

    public static function IsUploadOk(mixed $file): bool {
        return 
            !self::IsUploadError(file: $file) &&
            self::IsUploadValidFileSize(file: $file) &&
            self::IsAllowedExtension(file: $file);
    }

    public static function CombinePdfs(array $file_names, string $final_name): bool {
        /*
        $merger = new PDFMerger();
        foreach ($filenames as $file) {
            $merger->addPDF($file);
        }
        $object = $merger->merge(outputpath: $final_name);
        */
        if (count(value: $file_names) === 0)
        {
            return false;
        }
        try {
            if (count(value: $file_names) === 1)
            {
                return copy(from: $file_names[0], to: $final_name);
            }

            $pdf = new Mpdf(config: [
                'mode' => 'utf-8',
                'tempDir' => SERVER_UPLOAD_TMP,
            ]);

            // Filter out non-existing files
            $file_names = array_filter(array: $file_names, callback: function (string $file): bool {
                return is_file(filename: $file) && str_ends_with(haystack: $file, needle: '.pdf');
            });

            for ($file_index = 0; $file_index < count(value: $file_names); $file_index++)
            {
                $pages_count = $pdf->SetSourceFile(file: $file_names[$file_index]);
                for ($page_index = 1; $page_index <= $pages_count; $page_index++)
                {
                    $tplId = $pdf->ImportPage(pageNumber: $page_index);
                    $pdf->UseTemplate(tpl: $tplId);
                    if (($file_index < count(value: $file_names) - 1) || ($page_index != $pages_count))
                    {
                        $pdf->WriteHTML(html: '<pagebreak />');
                    }
                }
            }

            $pdf->Output(name: $final_name, dest: 'F');
            return true;

        } catch (\Throwable) {
            // echo $ex->getMessage();
            return false;
        }
    }

    public static function ImageToPdf(string $img): ?string {
        if (!is_file(filename: $img)) {
            return null;
        }
        try {
            $pdf = new Mpdf(config: [
                'mode' => 'utf-8',
                'tempDir' => SERVER_UPLOAD_TMP,
            ]);
            // $pdf->debug = true;
            // $pdf->showImageErrors = true;
            $pdf->imageVars['img'] = file_get_contents(filename: $img);
            $pdf->WriteHtml(html: '<img src="var:img" style="width: 100%;" />');

            $pdf->Output(name: $img . '.pdf', dest: 'F');
            return $img . '.pdf';
        } catch (\Exception) {
            return null;
        }
    }

    public static function UploadDocumentsMerge(array $files, string $final_name): ?string
    {
        if (empty($final_name)) {
            $final_name = uniqid(more_entropy: true);
        }
        if (!str_starts_with(haystack: $final_name, needle: DIRECTORY_SEPARATOR)) {
            $final_name = DIRECTORY_SEPARATOR . $final_name;
        }
        $final_name = SERVER_UPLOAD_PATH . $final_name;

        if (count(value: $files) === 0) {
            return null;
        }

        function isWordDocument($file): bool {
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            return $extension === 'docx' || $extension === 'doc';
        }

        if (array_any(array: $files, callback: function ($f): bool { return isWordDocument(file: $f); })) {
            // Upload the first docx file

            $file = array_filter(array: $files, callback: function ($f): bool { return isWordDocument(file: $f); })[0];
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            $final_name .= ".$extension";
            
            if (!move_uploaded_file(from: $file['tmp_name'], to: $final_name)) {
                // Error in saving the file
                return null;
            }
            return $final_name;
        }

        // All pdfs or images -> combine them in a single pdf

        $final_name .= ".pdf";
        // Merge all files into a pdf
        $paths = [];
        foreach ($files as $file)
        {
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            $pre_merge_name = 
                SERVER_UPLOAD_TMP . DIRECTORY_SEPARATOR .
                uniqid(more_entropy: true) . "." . $extension;
            
            if (!move_uploaded_file(from: $file['tmp_name'], to: $pre_merge_name)) {
                // Error in saving the file
                return null;
            }

            if ($extension !== 'pdf') {
                // Convert image to pdf
                $pdf_path = self::ImageToPdf(img: $pre_merge_name);
                if (empty($pdf_path)) {
                    // Could not convert to pdf
                    return null;
                }
                self::Delete(server_path: $pre_merge_name);
                $pre_merge_name = $pdf_path;
            }

            $paths[] = $pre_merge_name;
        }

        if (!self::CombinePdfs(
            file_names: $paths, 
            final_name: $final_name)) {
            return null;
        }

        // Delete temporary files
        foreach ($paths as $tmps) {
            self::Delete(server_path: $tmps);
        }

        return $final_name;
        
    }
}