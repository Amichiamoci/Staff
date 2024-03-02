<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Todo: Load .env with data


if (!defined("DOMAIN"))
    define("DOMAIN", "https://" . $_SERVER['HTTP_HOST']);
if (!defined("ADMIN_PATH"))
    define("ADMIN_PATH", "/admin");

define("ADMIN_URL", DOMAIN . ADMIN_PATH);

if (!defined("EMAIL_SOURCE"))
    define("EMAIL_SOURCE", "dev" . $_SERVER['HTTP_HOST']);

if (!defined("MYSQL_HOST"))
    define("MYSQL_HOST", "localhost");
if (!defined("MYSQL_USER"))
    define("MYSQL_USER", "root");
if (!defined("MYSQL_PASSWORD"))
    define("MYSQL_PASSWORD", "");
if (!defined("MYSQL_DB"))
    define("MYSQL_DB", "amichiamoci");