<?php

if (!isset($_POST["DB_NAME"]) || !is_string($_POST["DB_NAME"]) ||
    !isset($_POST["RESTART_SECRET"]) || !is_string($_POST["RESTART_SECRET"]))
{
    http_response_code(400);
    echo "Invalid request";
    exit;
}

$dir   = new RecursiveDirectoryIterator(__DIR__ . "/queries");
$flat  = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($flat, '/\.sql$/i');

require_once "./load/db_manager.php";
if (!defined("MYSQL_RESTART_SECRET"))
{
    define("MYSQL_RESTART_SECRET", $_POST["RESTART_SECRET"] . "_comparison_will_fail");
}
if ($_POST["RESTART_SECRET"] !== MYSQL_RESTART_SECRET || $_POST["DB_NAME"] !== MYSQL_DB)
{
    http_response_code(401);
    echo "Wrong password";
    exit;
}

foreach($files as $file)
{
    if (!$connection)
        continue;
    $content = file_get_contents($file);
    if (!$content) 
        continue;
    try {
        $result = $connection->query($content);
        echo "$file: SUCCESS" . PHP_EOL;
        $connection->next_result();
    } catch (Exception $ex) {
        echo "$file: $ex" . PHP_EOL;
    }
}