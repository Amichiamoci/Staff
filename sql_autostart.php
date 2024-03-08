<?php

$dir   = new RecursiveDirectoryIterator(__DIR__ . "/queries");
$flat  = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($flat, '/\.sql$/i');
foreach($files as $file) {
    echo $file , PHP_EOL;
}