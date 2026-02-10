<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

require_once __DIR__ . '/vendor/autoload.php';

use Amichiamoci\Utils\Security;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(paths: __DIR__);
$dotenv->safeLoad();

$MYSQL_HOST = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_HOST', default: 'localhost');
$MYSQL_PORT = (int)Security::LoadEnvironmentOfFromFile(var: 'MYSQL_PORT', default: '3306');
$MYSQL_USER = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_USER', default: 'amichiamoci');
$MYSQL_PASSWORD = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_PASSWORD');
$MYSQL_DB = Security::LoadEnvironmentOfFromFile(var: 'MYSQL_DB');

$connection = new \mysqli(
    hostname: $MYSQL_HOST, 
    port:     $MYSQL_PORT,
    username: $MYSQL_USER, 
    password: $MYSQL_PASSWORD, 
    database: $MYSQL_DB,
);
unset($MYSQL_HOST, $MYSQL_PORT, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);

if (!array_key_exists(key: 'HTTP_HOST', array: $_SERVER)) {
    // When running via CLI, set a default host
    $_SERVER['HTTP_HOST'] = 'localhost';
}

define(
    constant_name: "SITE_NAME", 
    value: Security::LoadEnvironmentOfFromFile(var: "SITE_NAME", default: 'Amichiamoci'));
define(
    constant_name: "DOMAIN", 
    value: Security::LoadEnvironmentOfFromFile(var: "DOMAIN", default: $_SERVER['HTTP_HOST']));
define(constant_name: 'POWERED_BY', value: 'https://github.com/Amichiamoci/Staff');

// Public key, private key will only be loaded when used
define(
    constant_name: "RECAPTCHA_PUBLIC_KEY", 
    value: Security::LoadEnvironmentOfFromFile(var: "RECAPTCHA_PUBLIC_KEY"));

define(
    constant_name: 'MAIN_SITE_URL',
    value: Security::LoadEnvironmentOfFromFile(var: 'MAIN_SITE_URL', default: 'https://www.amichiamoci.it'));
