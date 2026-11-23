<?php
setlocale(LC_ALL, 'ita', 'it_IT.utf8');

require_once __DIR__ . '/vendor/autoload.php';

use Amichiamoci\Utils\Security;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use GuzzleHttp\Client as HttpClient;
use Richie314\SimpleVirtualFS\VirtualFilesystem;
use Dotenv\Dotenv;

$_ENV += getenv();
$dotenv = Dotenv::createImmutable(paths: __DIR__);
$dotenv->safeLoad();

/******************************************************************
 *                                                                *
 *                        FILE HANDLING                           *
 *                                                                *
 ******************************************************************/

// Default staff:/// local filesystem
$files = new VirtualFilesystem();
$files->mountLocal('staff', __DIR__ . DIRECTORY_SEPARATOR . 'data', name: 'Locale (Portale)');

// Google drive drive:/// filesystem
$driveFolder =       Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_FOLDER_ID');
$driveClientId =     Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_CLIENT_ID');
$driveSecret =       Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_CLIENT_SECRET');
$driveRefreshToken = Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_REFRESH_TOKEN');
$driveKey =          Security::LoadEnvironmentOrFromFile(var: 'VFS_DRIVE_BASE_KEY');
if (!empty($driveFolder)
    && !empty($driveClientId)
    && !empty($driveSecret)
    && !empty($driveRefreshToken)
    && !empty($driveKey)
) {
    $key = base64_decode(trim($driveKey), strict: true);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('VFS_DRIVE_BASE_KEY must encode 32 bytes in base64.');
    }

    $client = new GoogleClient();
    $client->setHttpClient(new HttpClient(['timeout' => 20]));
    $client->setClientId($driveClientId);
    $client->setClientSecret($driveSecret);

    // The adapter refreshes this expired token on the first Drive operation.
    $client->setAccessToken(['access_token' => '', 'refresh_token' => $driveRefreshToken]);
    $files->mountGoogleDrive('drive', 
                             new Drive($client), 
                             options: ['sharedFolderId' => $driveFolder],
                             key: $key,
                             name: 'Google Drive'
                             );
    unset($client, $key);
}
unset($driveFolder, $driveClientId, $driveSecret, $driveRefreshToken, $driveKey);

/******************************************************************
 *                                                                *
 *                      DATABASE CONNECTION                       *
 *                                                                *
 ******************************************************************/

$db_host =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_HOST', default: 'localhost');
$db_port = (int)Security::LoadEnvironmentOrFromFile(var: 'MYSQL_PORT', default: '3306');
$db_user =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_USER', default: 'amichiamoci');
$db_password =  Security::LoadEnvironmentOrFromFile(var: 'MYSQL_PASSWORD');
$db_name =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_DB');

$connection = new \mysqli(
    hostname: $db_host, 
    port:     $db_port,
    username: $db_user, 
    password: $db_password, 
    database: $db_name,
);
unset($db_host, $db_port, $db_user, $db_password, $db_name);


/******************************************************************
 *                                                                *
 *                       SITE URL AND LINKS                       *
 *                                                                *
 ******************************************************************/

if (!array_key_exists(key: 'HTTP_HOST', array: $_SERVER)) {
    // When running via CLI, set a default host
    $_SERVER['HTTP_HOST'] = 'localhost';
}

define(
    constant_name: "SITE_NAME", 
    value: Security::LoadEnvironmentOrFromFile(var: "SITE_NAME", default: 'Amichiamoci'));
define(
    constant_name: "DOMAIN", 
    value: Security::LoadEnvironmentOrFromFile(var: "DOMAIN", default: $_SERVER['HTTP_HOST']));
define(constant_name: 'POWERED_BY', value: 'https://github.com/Amichiamoci/Staff');

// Public key, private key will only be loaded when used
define(
    constant_name: "RECAPTCHA_PUBLIC_KEY", 
    value: Security::LoadEnvironmentOrFromFile(var: "RECAPTCHA_PUBLIC_KEY"));

define(
    constant_name: 'MAIN_SITE_URL',
    value: Security::LoadEnvironmentOrFromFile(var: 'MAIN_SITE_URL', default: 'https://www.amichiamoci.it'));
