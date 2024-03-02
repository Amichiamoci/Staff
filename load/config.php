<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (!defined("DOMAIN"))
    define("DOMAIN", "https://" . $_SERVER['HTTP_HOST']);
if (!defined("ADMIN_PATH"))
    define("ADMIN_PATH", "/admin");