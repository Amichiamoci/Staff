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

    public static function Exists(string $db_path): bool
    {
        $res = self::ServerPath(db_path: $db_path);
        if (!$res) 
            return false;
        return is_file(filename: $res);
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
        return "/file?name=$path";
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
        //"bmp",
        //"avif",
        //"tif", 
        //"tiff",
        "webp",
        "heic",
        "heif",
        "pdf",        
        "doc",
        "docx",
        //"ppt",
        //"pptx"
    ];
    public static function ALLOWED_EXT_DOTS(): array {
        return array_map(
            callback: function(string $s): string { return ".$s"; }, 
            array: self::$ALLOWED_EXT
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

    public static function Upload($file, string &$future_file_name, string &$error): bool
    {
        if ($file == null)
            return false;
        $file_name = $file["name"];
        $file_size = $file["size"];

        // Verify file extension
        $ext = pathinfo(path: $file_name, flags: PATHINFO_EXTENSION);

        if (!in_array(needle: $ext, haystack: self::$ALLOWED_EXT)) {
            $error = "Tipo di file non valido!";
            return false;
        }    
            
        if ($file_size > self::MAX_SIZE()) {
            $error = "File troppo grande!";
            return false;
        }            


        $actual_path = SERVER_UPLOAD_PATH . "$future_file_name.$ext";
        $actual_path = str_replace(search: ' ', replace: '_', subject: $actual_path);

        // Check whether file exists before uploading it
        while (file_exists(filename: $actual_path))
        {
            $ext_index = strrpos(haystack: $actual_path, needle: $ext) - 1;
            $actual_path = substr(string: $actual_path, offset: 0, length: $ext_index). "_nuovo." . $ext;
        }        
        if (move_uploaded_file(from: $file["tmp_name"], to: $actual_path))
        {
            $future_file_name = substr(string: $actual_path, offset: strlen(string: SERVER_UPLOAD_PATH));
            return true;
        } 
        $error = "Impossibile uploadare il file!<!--Path: $actual_path -->";
        return false;
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

    public static function UploadingFiles(string $form_name): array{
        if (array_key_exists(key: $form_name, array: $_FILES)) {
            return $_FILES[$form_name];
        }
        return [];
    }

    public static function IsUploadError(mixed $file): bool {
        return 
            !array_key_exists(key: 'error', array: $file) || 
            $file['error'] !== UPLOAD_ERR_OK;
    }

    public static function IsUploadValidFileSize(mixed $file): bool {
        return
            !array_key_exists(key: 'size', array: $file) ||
            (int)$file['size'] > self::MAX_SIZE(); 
    }

    public static function IsAllowedMimeType(mixed $file): bool {
        if (!array_key_exists(key: 'tmp_name', array: $file))
            return false;
        try {
            $mime = self::GetMimeType(filename: $file['tmp_name']);
            $whitelist = [];//self::AllowedMimeTypes();
            return in_array(needle: $mime, haystack: $whitelist);
        } catch (\Exception) {
            return false;
        }
    }

    public static function IsUploadOk(mixed $file): bool {
        return 
            // !!$file &&
            !self::IsUploadError(file: $file) &&
            self::IsUploadValidFileSize(file: $file) &&
            self::IsAllowedMimeType(file: $file);
    }

    public static function CombinePdfs(array $file_names, string $final_name): bool {
        /*
        $merger = new PDFMerger();
        foreach ($filenames as $file) {
            $merger->addPDF($file);
        }
        $object = $merger->merge(outputpath: $final_name);
        */
        try {
            $pdf = new Mpdf([
                'mode' => 'utf-8',
            ]);
            $pdf->SetImportUse();

            if (!is_file(filename: $final_name)) {
                // Create file if not existing yet
                file_put_contents(filename: $final_name, data: '');
            }

            // Filter out non-existing files
            $file_names = array_filter(array: $file_names, callback: function (string $file): bool {
                return is_file(filename: $file) && str_ends_with(haystack: $file, needle: '.pdf');
            });

            for ($file_index = 1; $file_index <= count(value: $file_names); $file_index++)
            {
                $pages_count = $pdf->SetSourceFile($file_names[$file_index]);
                for ($page_index = 1; $page_index <= $pages_count; $page_index++)
                {
                    $tplId = $pdf->ImportPage($page_index);
                    $pdf->UseTemplate($tplId);
                    if (($file_index < count(value: $file_names)) || ($page_index != $pages_count))
                    {
                        $pdf->WriteHTML('<pagebreak />');
                    }
                }
            }

            $pdf->Output($final_name, 'F');
            return true;

        } catch (\Exception) {
            return false;
        }
    }
}