<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

//
// Load from .env in root
//
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "../");
$dotenv->ifPresent(array("DB_HOST", "DB_USER", "DB_NAME"))->notEmpty();
$dotenv->safeLoad();
if (isset($_ENV["DB_HOST"]))
    define("MYSQL_HOST", $_ENV("DB_HOST"));
if (isset($_ENV["DB_USER"]))
    define("MYSQL_USER", $_ENV("DB_USER"));
if (isset($_ENV["DB_PASSWORD"]))
    define("MYSQL_PASSWORD", $_ENV("DB_PASSWORD"));
if (isset($_ENV["DB_NAME"]))
    define("MYSQL_USER", $_ENV("DB_NAME"));
if (isset($_ENV["WEBSITE_DOMAIN"]))
    define("DOMAIN", $_ENV("WEBSITE_DOMAIN"));
if (isset($_ENV["EMAIL_SOURCE"]))
    define("EMAIL_SOURCE", $_ENV("EMAIL_SOURCE"));
unset($dotenv);


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (!defined("DOMAIN"))
    define("DOMAIN", $_SERVER['HTTP_HOST']);
define("DOMAIN_URL", "https://" . DOMAIN);

define("ADMIN_PATH", dirname($_SERVER["PHP_SELF"]));
define("ADMIN_URL", DOMAIN . ADMIN_PATH);

if (!defined("EMAIL_SOURCE"))
    define("EMAIL_SOURCE", "dev@" . DOMAIN);

if (!defined("MYSQL_HOST"))
    define("MYSQL_HOST", "localhost");
if (!defined("MYSQL_USER"))
    define("MYSQL_USER", "root");
if (!defined("MYSQL_PASSWORD"))
    define("MYSQL_PASSWORD", "");
if (!defined("MYSQL_DB"))
    define("MYSQL_DB", "amichiamoci");