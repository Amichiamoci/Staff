<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

use Amichiamoci\Utils\Security;
use Dotenv\Dotenv;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$dotenv = Dotenv::createImmutable(paths: __DIR__);
$dotenv->safeLoad();

$MYSQL_HOST = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_HOST', default: 'localhost');
$MYSQL_PORT = (int)Security::LoadEnvironmentOfFromFile(var: 'MYSQL_PORT', default: '3306');
$MYSQL_USER = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_USER', default: 'amichiamoci');
$MYSQL_PASSWORD = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_PASSWORD');
$MYSQL_DB = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_DB');

define(
    constant_name: "SITE_NAME", 
    value: Security::LoadEnvironmentOfFromFile(var: "SITE_NAME", default: 'Amichiamoci'));
define(
    constant_name: "DOMAIN", 
    value: Security::LoadEnvironmentOfFromFile(var: "DOMAIN", default: $_SERVER['HTTP_HOST']));
define(constant_name: 'POWERED_BY', value: 'https://github.com/Amichiamoci/Staff');

//
// Detect if the app is running in a subfolder (DOCUMENT_ROOT != __DIR__) in this file
//
if ($_SERVER['DOCUMENT_ROOT'] !== __DIR__)
{
    define(
        constant_name: 'INSTALLATION_PATH',
        value: substr(string: __DIR__, offset: strlen(string: $_SERVER['DOCUMENT_ROOT']))
    );
} else {
    define(constant_name: 'INSTALLATION_PATH', value: '');
}


// Public key, private key will only be loaded when used
define(
    constant_name: "RECAPTCHA_PUBLIC_KEY", 
    value: Security::LoadEnvironmentOfFromFile(var: "RECAPTCHA_PUBLIC_KEY"));

define(
    constant_name: 'MAIN_SITE_URL',
    value: Security::LoadEnvironmentOfFromFile(var: 'MAIN_SITE_URL', default: 'https://www.amichiamoci.it'));

define(
    constant_name: 'SERVER_UPLOAD_PATH', 
    value: $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'Uploads');
define(
    constant_name: 'SERVER_UPLOAD_TMP', 
    value: SERVER_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'tmp');

define(
    constant_name: 'CRON_LOG_DIR',
    value: SERVER_UPLOAD_TMP . DIRECTORY_SEPARATOR . 'cron');

$log = new Logger(name: 'Request logger');
$log->pushHandler(
    handler: new StreamHandler(stream: SERVER_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'requests.log', 
    level: Level::Warning)
);