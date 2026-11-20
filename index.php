<?php

ini_set('display_errors', '0');
set_exception_handler(static function (Throwable $exception): void {
    error_log('Staff request failed: ' . $exception::class);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'Errore interno.';
});

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config.php';

use Amichiamoci\Controllers\ApiController;
use Amichiamoci\Controllers\EmailController;
use Amichiamoci\Controllers\HomeController;
use Amichiamoci\Controllers\UserController;
use Amichiamoci\Controllers\FileController;
use Amichiamoci\Controllers\SportController;
use Amichiamoci\Controllers\StaffController;
use Amichiamoci\Controllers\TeamsController;

use Amichiamoci\Utils\Security;
use Amichiamoci\Utils\File;
use Amichiamoci\Models\User;
use Amichiamoci\Models\Staff;

use Richie314\SimpleMvc\Routers\Router;
use Richie314\SimpleMvc\Http\StatusCode;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$logger = new Logger(name: 'Request logger');
$logger->pushHandler(
    handler: new RotatingFileHandler(
        filename: File::getLogDir() . DIRECTORY_SEPARATOR .  'requests.log', 
        level: Level::Info,
        maxFiles: (int)Security::LoadEnvironmentOrFromFile(
            var: 'LOG_ROTATING_MAX_FILES', 
            default: '14',
        ),
    )
);

$router = new Router(
    pathPrefix: File::getInstallationPath(), 
    applicationInstallationPath: $_SERVER['DOCUMENT_ROOT'] . File::getInstallationPath(),
);
$router->AddController(controller: HomeController::class,  route_base: '/');
$router->AddController(controller: UserController::class,  route_base: '/user');
$router->AddController(controller: FileController::class,  route_base: '/file');
$router->AddController(controller: StaffController::class, route_base: '/staff');
$router->AddController(controller: TeamsController::class, route_base: '/teams');
$router->AddController(controller: SportController::class, route_base: '/sport');
$router->AddController(controller: EmailController::class, route_base: '/email');

// This controller is not present by default
if (Security::ApiEnabled())
    $router->AddController(controller: ApiController::class, route_base: '/api');

// Pass the db connection to the router,
// so it can be passed to the controllers
$router->SetDbConnection(connection: $connection);

// Load user data
$user = User::LoadFromSession();
if ($user !== null) {
    $account = User::ById($connection, $user->Id);
    if ($account === null || $account->IsBanned) {
        $user->Logout();
        $user = null;
    } else {
        $user->Admin = $account->Admin;
        $user->Name = $account->Name;
    }
}
if ($user !== null)
{
    $router->SetUser(user: $user);
    $user->UpdateLogTs();
    $user->PutLogTsInSession();
    $user->UploadDbLog(connection: $connection);

    if (!$user->HasAdditionalData() && 
        $user->LoadAdditionalData(connection: $connection)
    )
        $user->PutAdditionalInSession();

    if (!empty($user->IdStaff))
    {
        $staff = Staff::ById(connection: $connection, id: $user->IdStaff);
        $router->SetDefaultVariable(key: 'staff', value: $staff);
    }
}

$uri = $_SERVER['REQUEST_URI'];
$client_ip = Security::GetIpAddress();

$result = $router->Dispatch(uri: $uri);

$path = parse_url($uri, PHP_URL_PATH);
$log_message = "[$client_ip] [$result->value] $path";
if (StatusCode::IsError(statusCode: $result))
    $logger->warning(message: $log_message);
else
    $logger->info(message: $log_message);