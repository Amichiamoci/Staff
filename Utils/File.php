<?php
namespace Amichiamoci\Utils;

use Mpdf\Mpdf;
use Richie314\SimpleMvc\Utils\File as BaseFile;

class File extends BaseFile
{
    /**
     * Checks if the given path refers to an external file: 
     * accessible via a network protocol like ftp, http, https, or app schema.
     * @param string $filename The file path to check
     * @return bool True if the file is external, false otherwise
     */
    public static function IsExternalFile(string $filename): bool
    {
        $protocols = ['http://', 'https://', 'app:///', 'ftp://', 'ftps://'];

        return array_any(
            array: $protocols, 
            callback: function (string $protocol) use ($filename): bool {
                return str_starts_with(haystack: $filename, needle: $protocol);
        });
    }
    

    public static function ServerPath(string $db_path): string|false
    {
        if (strlen(string: $db_path) === 0 || 
            self::IsExternalFile(filename: $db_path)
        )
            return false;

        if (!str_starts_with(haystack: $db_path, needle: DIRECTORY_SEPARATOR))
            $db_path = DIRECTORY_SEPARATOR . $db_path;

        return realpath(path: SERVER_UPLOAD_PATH . $db_path);
    }

    public static function AbsoluteToDbPath(string $server_path): string
    {
        if (self::IsExternalFile(filename: $server_path) ||
            !str_starts_with(haystack: $server_path, needle: SERVER_UPLOAD_PATH)
        )
            return $server_path;
        
        return substr(string: $server_path, offset: strlen(string: SERVER_UPLOAD_PATH));
    }

    public static function Exists(string $file_path): bool
    {
        if (self::IsExternalFile(filename: $file_path))
            return true; // Assume file exists

        $res = self::ServerPath(db_path: $file_path);
        if (!$res) 
            return false;

        return parent::Exists(file_path: $res);
    }

    public static function Delete(string $file_path): bool
    {
        if (self::IsExternalFile(filename: $file_path))
            return false;

        return parent::Delete(file_path: $file_path);
    }

    public static function GetExportUrl(string $path): string
    {
        if (self::IsExternalFile(filename: $path))
        {
            if (str_starts_with(haystack: $path, needle: 'app:///'))
            {
                // File is on a separate server. The server that hosts the app specifically.
                // TODO: conver schema app:/// to the app's export url
            }
            return $path;
        }
        return INSTALLATION_PATH . "/file?name=$path";
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
    public static function ALLOWED_EXT_DOTS(): string
    {
        return join(
            separator: ', ',
            array: array_map(
                callback: function(string $s): string { return ".$s"; }, 
                array: self::$ALLOWED_EXT
            )
        );
    } 

    public static function MAX_SIZE(): int
    {
        $str = Security::LoadEnvironmentOfFromFile(var: 'FILE_MAX_SIZE');

        if (!empty($str) && is_numeric(value: $str))
            return (int)$str;

        return 10 * 1024 * 1024; // Default size limit of 10MB
    }

    public static function IsUploadError(mixed $file): bool
    {
        return 
            array_key_exists(key: 'error', array: $file) && 
            (int)$file['error'] !== UPLOAD_ERR_OK;
    }

    public static function IsUploadValidFileSize(mixed $file): bool
    {
        return
            array_key_exists(key: 'size', array: $file) &&
            (int)$file['size'] <= self::MAX_SIZE(); 
    }

    public static function IsAllowedExtension(mixed $file): bool
    {
        if (!array_key_exists(key: 'name', array: $file))
            return false;

        $ext = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
        return in_array(needle: $ext, haystack: self::$ALLOWED_EXT);
    }

    public static function IsUploadOk(mixed $file): bool
    {
        return 
            !self::IsUploadError(file: $file) &&
            self::IsUploadValidFileSize(file: $file) &&
            self::IsAllowedExtension(file: $file);
    }

    public static function CombinePdfs(array $file_names, string $final_name): bool
    {
        if (count(value: $file_names) === 0)
            return false;

        try {
            if (count(value: $file_names) === 1)
                return copy(from: $file_names[0], to: $final_name);

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

                    if ($file_index < count(value: $file_names) - 1 || 
                        $page_index !== $pages_count
                    )
                        $pdf->WriteHTML(html: '<pagebreak />');
                }
            }

            $pdf->Output(name: $final_name, dest: 'F');
            return true;

        } catch (\Throwable) {
            // echo $ex->getMessage();
            return false;
        }
    }

    public static function ImageToPdf(string $img): ?string
    {
        if (!is_file(filename: $img))
            return null;

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
        if (empty($final_name))
            $final_name = uniqid(more_entropy: true);

        if (!str_starts_with(haystack: $final_name, needle: DIRECTORY_SEPARATOR))
            $final_name = DIRECTORY_SEPARATOR . $final_name;

        $final_name = SERVER_UPLOAD_PATH . $final_name;

        if (count(value: $files) === 0)
            return null;

        function isWordDocument($file): bool
        {
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            return $extension === 'docx' || $extension === 'doc';
        }

        if (array_any(array: $files, callback: function ($f): bool { return isWordDocument(file: $f); }))
        {
            // Upload the first docx file

            $file = array_filter(array: $files, callback: function ($f): bool { return isWordDocument(file: $f); })[0];
            $extension = pathinfo(path: $file['name'], flags: PATHINFO_EXTENSION);
            $final_name .= ".$extension";
            
            if (!move_uploaded_file(from: $file['tmp_name'], to: $final_name))
                // Error in saving the file
                return null;

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
            
            if (!move_uploaded_file(from: $file['tmp_name'], to: $pre_merge_name))
                // Error in saving the file
                return null;

            if ($extension !== 'pdf')
            {
                // Convert image to pdf
                $pdf_path = self::ImageToPdf(img: $pre_merge_name);
                if (empty($pdf_path))
                    // Could not convert to pdf
                    return null;

                self::Delete(file_path: $pre_merge_name);
                $pre_merge_name = $pdf_path;
            }

            $paths[] = $pre_merge_name;
        }

        if (!self::CombinePdfs(
            file_names: $paths, 
            final_name: $final_name)
        ) 
            return null;

        // Delete temporary files
        foreach ($paths as $tmps)
            self::Delete(file_path: $tmps);

        return $final_name;
    }
}