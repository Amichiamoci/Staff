<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
$dir   = new RecursiveDirectoryIterator(realpath(__DIR__ . "/queries/"));
$flat  = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($flat, '/\.sql$/i');

$all = "";

foreach($files as $file)
{
    $content = file_get_contents($file);
    if (!$content) 
        continue;
    $all .= "-- $file" . PHP_EOL;
    $all .= $content . PHP_EOL . PHP_EOL;
}

if ($all && strlen($all) > 0)
{
    $f = fopen(realpath(__DIR__ . "/db_tools.sql"), "w");
    if ($f) {
        fwrite($f, $all);
        fclose($f);
    }
}
echo $all;