<?php

declare(strict_types=1);

use Richie314\SimpleMvc\Controllers\Controller;
use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;

use Amichiamoci\Utils\ErrorLog;

require dirname(__DIR__) . '/vendor/autoload.php';

final class ErrorFixtureController extends Controller
{
    public function fixture(): StatusCode
    {
        throw new RuntimeException('private fixture message', 42);
    }
}

define('SITE_NAME', 'Staff fixture');
define('MAIN_SITE_URL', 'https://example.invalid');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

$root = sys_get_temp_dir() . '/staff-errors-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
mkdir($root . '/Utils');
mkdir($root . '/data/log', recursive: true);
copy(dirname(__DIR__) . '/Utils/ErrorLog.php', $root . '/Utils/ErrorLog.php');
require $root . '/Utils/ErrorLog.php';
ini_set('error_log', $root . '/stderr.log');

try {
    $router = new Router(applicationInstallationPath: dirname(__DIR__));
    $router->AddRouteAction('/fixture', ErrorFixtureController::class, 'fixture');
    ob_start();
    $status = $router->Dispatch('/fixture');
    $response = ob_get_clean();
    if ($status !== StatusCode::ServerError || str_contains($response, 'private fixture message')) {
        throw new RuntimeException('The router did not return a generic HTTP 500.');
    }
    ErrorLog::Report();
    foreach ([$root . '/stderr.log', $root . '/data/log/errors.log'] as $path) {
        $log = file_get_contents($path);
        if (substr_count($log, 'Staff internal error') !== 1 || !str_contains($log, 'RuntimeException')
            || !str_contains($log, 'error-log.php') || !str_contains($log, '"code":42')
            || str_contains($log, 'private fixture message')
        ) {
            throw new RuntimeException('The error sinks did not receive the same sanitized diagnostic.');
        }
    }
} finally {
    foreach (['stderr.log', 'data/log/errors.log', 'Utils/ErrorLog.php'] as $file) {
        if (is_file($root . '/' . $file)) {
            unlink($root . '/' . $file);
        }
    }
    rmdir($root . '/data/log');
    rmdir($root . '/data');
    rmdir($root . '/Utils');
    rmdir($root);
}

echo "Error logging checks passed.\n";
