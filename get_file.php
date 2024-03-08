<?php
include "./check_login.php";
error_reporting(0);
if (!isset($_GET["target"]) || !is_string($_GET["target"]))
{
    http_response_code(404);
    header("Location: ./not-found.php");
    //echo "target invalid!";
    die();
}
if (!we_have_file($_GET["target"]))
{
    http_response_code(404);
    header("Location: ./not-found.php");
    die();
}
$file_path = server_file_path($_GET["target"]);
$name_parts = explode("/", str_replace("\\", "/", $file_path));
$actual_file_name = basename(urlencode( end($name_parts) ));
$mime = getMimeType($file_path);
header('Content-Description: File Transfer');
header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($file)).' GMT');
header("Content-type: $mime");
header("Content-length: " . filesize($file_path));
header("Content-Disposition: attachment; filename=\"$actual_file_name\"");
ob_clean();
echo file_get_contents($file_path);
exit;