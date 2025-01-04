<?php
namespace Amichiamoci\Utils;

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
        if (!isset($db_path)) //.. characters colud be used to steal files
            return "";

        if (!str_starts_with(haystack: $db_path, needle: "/"))
        {
            $db_path = "/$db_path";
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

    public static function GetExportUrl(string $path): string
    {
        return ADMIN_URL . "/get_file.php?target=$path";
    }

    public static function RemoveCharacters(string $str) : string
    {
        $exploded = str_split(string: $str);
        $regex = "/" . join(separator: "|", array: self::$FILE_NAME_CHAR_WHITELIST) . "/";
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
        "jpg",        "jpeg",        "gif",
        "png",        "bmp",        "avif",
        "tif",        "tiff",        "webp",
        "heic",        "heif",        "pdf",        
        "doc",        "docx",         "ppt",
        "pptx"
    ];
    public static array $ALLOWED_EXT_DOTS = array_map(
        function(string $s): string { return ".$s"; }, 
        self::$ALLOWED_EXT
    );

    public static array $FILE_NAME_CHAR_WHITELIST = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'), [' ']);

    public static int $MAX_SIZE = 
        array_key_exists('FILE_MAX_SIZE', $_ENV) ?
        $_ENV['FILE_MAX_SIZE'] : 
        10 * 1024 * 1024;

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
            
        if ($file_size > self::$MAX_SIZE) {
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
}