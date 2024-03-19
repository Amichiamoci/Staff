<?php 
function getMimeType(string $filename) : string
{
    try {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo)
        {
            throw new Exception("Invalid file");
        }
        $mime = finfo_file($finfo, $filename);
        if (!$mime)
        {
            throw new Exception("Invalid result");
        }
        finfo_close($finfo);
        return $mime;
    } catch (Exception $ex) {
        return "application/octet-stream";
    }
}

function upload_file($file, string &$future_file_name, string &$error): bool
{
    if ($file == null)
        return false;
    $file_name = $file["name"];
    $file_size = $file["size"];

    // Verify file extension
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);

    if (!in_array($ext, ALLOWED_EXT)) {
        $error = "Tipo di file non valido!";
        return false;
    }    
        
    $maxsize = 10 * 1024 * 1024;
          
    if ($file_size > $maxsize) {
        $error = "File troppo grande!";
        return false;
    }            
    
    
    $actual_path = SERVER_UPLOAD_PATH . "$future_file_name.$ext";
    
    // Check whether file exists before uploading it
    while (file_exists($actual_path))
    {
        $ext_index = strrpos($actual_path, $ext) - 1;
        $actual_path = substr($actual_path, 0, $ext_index). "_nuovo." . $ext;
    }        
    if (move_uploaded_file($file["tmp_name"], $actual_path))
    {
        $future_file_name = substr($actual_path, strlen(SERVER_UPLOAD_PATH));
        return true;
    } 
    $error = "Impossibile uploadare il file!<!--Path: $actual_path -->";
    return false;
}
function server_file_path(string $db_path) : string|false
{
    if (!isset($db_path)) //.. characters colud be used to steal files
        return "";
    
    if (!str_starts_with($db_path, "/"))
    {
        $db_path = "/$db_path";
    }

    return realpath(SERVER_UPLOAD_PATH . $db_path);
}
function we_have_file(string $path) : bool
{
    $res = server_file_path($path);
    if (!$res) 
        return false;
    return is_file($res);
}
function get_file_export_url(string $path) : string
{
    return ADMIN_URL . "/get_file.php?target=$path";
}
define("ALLOWED_EXT", array(
    "jpg",        "jpeg",        "gif",
    "png",        "bmp",        "avif",
    "tif",        "tiff",        "webp",
    "heic",        "heif",        "pdf",        
    "doc",        "docx",         "ppt",
    "pptx"));
define("ALLOWED_EXT_DOTS", array_map(function(string $s) { return ".$s"; }, ALLOWED_EXT));
define("FILE_NAME_CHAR_WHITELIST", array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'), array(' ')));

function file_remove_characters(string $str) : string
{
    $exploded = str_split($str);
    $regex = join("|", FILE_NAME_CHAR_WHITELIST);
    return join("", preg_grep($regex, $exploded));
}
function file_spaces_to_underscores(string $str) : string
{
    return preg_replace("/\s+/", "_", $str);
}

function string_capitalize_words(string $str) : string
{
    $parts = explode(" ", $str);
    $parts = array_filter($parts, function (string $s){
        return strlen($s) > 0;
    });
    $parts = array_map(function (string $str) {
        return strtoupper(substr($str, 0, 1)) . strtolower(substr($str, 1));
    }, $parts);
    return join(" ", $parts);
}