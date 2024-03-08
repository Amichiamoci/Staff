<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

//
// Load from .env in root
//
require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
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
if (isset($_ENV["CONTACT_EMAIL"]))
    define("CONTACT_EMAIL", $_ENV("CONTACT_EMAIL"));
unset($dotenv);

error_reporting(E_ALL);

if (!defined("DOMAIN"))
    define("DOMAIN", $_SERVER['HTTP_HOST']);

define("CURRENT_PROTOCOL", empty($_SERVER["HTTPS"]) ? "http://" : "https://");
define("DOMAIN_URL", CURRENT_PROTOCOL . DOMAIN);

define("DOCUMENT_ROOT_COUNT", count(explode('/', str_replace('\\', '/', $_SERVER["DOCUMENT_ROOT"]))));
$DOCUMENT_PATH = explode('/', str_replace('\\', '/', dirname(__DIR__, 1)));
for ($i = 0; $i < DOCUMENT_ROOT_COUNT; $i++)
{
    unset($DOCUMENT_PATH[$i]);
}
$DOCUMENT_PATH = array_values($DOCUMENT_PATH);
define("ADMIN_PATH", '/' . join('/', $DOCUMENT_PATH));
unset($DOCUMENT_PATH);

define("ADMIN_URL", DOMAIN_URL . ADMIN_PATH);
define("UPLOAD_PATH", ADMIN_PATH . "/upload");
define("SERVER_UPLOAD_PATH", $_SERVER["DOCUMENT_ROOT"] . UPLOAD_PATH);

if (!defined("EMAIL_SOURCE"))
    define("EMAIL_SOURCE", "dev@" . DOMAIN);
if (!defined("CONTACT_EMAIL"))
    define("CONTACT_EMAIL", "info@" . DOMAIN);

if (!defined("MYSQL_HOST"))
    define("MYSQL_HOST", "localhost");
if (!defined("MYSQL_USER"))
    define("MYSQL_USER", "root");
if (!defined("MYSQL_PASSWORD"))
    define("MYSQL_PASSWORD", "");
if (!defined("MYSQL_DB"))
    define("MYSQL_DB", "amichiamoci");

    
$is_extern = isset($is_extern) && (bool) $is_extern;