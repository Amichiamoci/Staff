<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

use Amichiamoci\Utils\Security;

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
define(
    constant_name: "ADMIN_PATH", 
    value: Security::LoadEnvironmentOfFromFile(var: "ADMIN_PATH", default: "/"));
define(constant_name: 'POWERED_BY', value: 'https://github.com/Amichiamoci/Staff');

// Public key, private key will only be loaded when used
define(
    constant_name: "CF_TURNSTILE_TOKEN", 
    value: Security::LoadEnvironmentOfFromFile(var: "CF_TURNSTILE_TOKEN", default: ""));

define(
    constant_name: 'MAIN_SITE_URL',
    value: Security::LoadEnvironmentOfFromFile(var: 'MAIN_SITE_URL', default: 'https://www.amichiamoci.it'));

define(
    constant_name: 'SERVER_UPLOAD_PATH', 
    value: $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'Uploads');