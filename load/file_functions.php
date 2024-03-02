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

function upload_file($file, array $allowed_ext, string &$future_file_name, string &$error): bool
{
    if ($file == null)
        return false;
    $file_name = $file["name"];
    $file_size = $file["size"];

    // Verify file extension
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);

    if (!in_array($ext, $allowed_ext)) {
        $error = "Tipo di file non valido!";
        return false;
    }    
        
    $maxsize = 10 * 1024 * 1024;
          
    if ($file_size > $maxsize) {
        $error = "File troppo grande!";
        return false;
    }            
    
    
    $actual_path = $_SERVER["DOCUMENT_ROOT"] . "$future_file_name.$ext";
    //$actual_path = "/$future_file_name.$ext";
    // Check whether file exists before uploading it
    while (file_exists($actual_path))
    {
        $ext_index = strrpos($actual_path, $ext) - 1;
        $actual_path = substr($actual_path, 0, $ext_index). "_nuovo." . $ext;
    }        
    if (move_uploaded_file($file["tmp_name"], $actual_path))
    {
        $future_file_name = substr($actual_path, strlen($_SERVER["DOCUMENT_ROOT"]));
        return true;
    } 
    $error = "Impossibile uploadare il file!<!--Path: $actual_path -->";
    return false;
}
function server_file_path(string $db_path) : string
{
    if (!isset($db_path)) //.. characters colud be used to steal files
        return "";
    
    if (str_starts_with($db_path, "/"))
    {
        $db_path = substr($db_path, 1);
    }

    if (!str_starts_with($db_path, "upload/"))
    {
        $db_path = "upload/$db_path";
    } 
    return realpath($_SERVER["DOCUMENT_ROOT"] . $db_path);
}
function we_have_file(string $path):bool
{
    return file_exists(server_file_path($path));
}
function get_file_export_url(string $path)
{
    return ADMIN_PATH . "/get_file.php?target=$path";
}