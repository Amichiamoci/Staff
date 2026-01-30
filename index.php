<?php

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
*/

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
use Amichiamoci\Models\User;
use Amichiamoci\Models\Staff;

use Richie314\SimpleMvc\Routers\Router;
use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Utils\Security as UtilsSecurity;

$router = new Router(
    pathPrefix: INSTALLATION_PATH, 
    applicationInstallationPath: $_SERVER['DOCUMENT_ROOT'] . INSTALLATION_PATH,
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

set_error_handler(callback: function(\Throwable|int $ex, ?string $msg = null): void {
    if ($ex instanceof \Throwable) {
    ?>
    <pre class="text-error"><?= htmlspecialchars(string: $ex->getMessage())?></pre>
    <pre class="text-warning"><?= htmlspecialchars(string: $ex->getTraceAsString())?></pre>
    <?php } else { ?>
        <span class="user-select-none font-monospace text-error">
            <strong> Errore 0x<?= dechex(num: $ex) ?> </strong> (<?= $ex ?>)
        </span>
        <?php if (isset($msg)) { ?>
            <code class="text-error">
                <?= htmlspecialchars(string: $msg) ?>
            </code>
        <?php } ?>
    <?php }
});
set_exception_handler(callback: function(\Throwable $ex): void {
    ?>
    <pre><?= htmlspecialchars(string: $ex->getMessage())?></pre>
    <pre><?= htmlspecialchars(string: $ex->getTraceAsString())?></pre>
    <?php
});

// Establish db connection
$connection = new \mysqli(
    hostname: $MYSQL_HOST, 
    username: $MYSQL_USER, 
    password: $MYSQL_PASSWORD, 
    database: $MYSQL_DB,
    port: $MYSQL_PORT
);
$router->SetDbConnection(connection: $connection);

// Delete variables once used for security
unset($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);

// Load user data
$user = User::LoadFromSession();
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
$client_ip = UtilsSecurity::GetIpAddress();

$result = $router->dispatch(uri: $uri);
if (StatusCode::IsError(statusCode: $result))
    $log->warning(message: "[$client_ip] [$result->value] $uri");
else
    $log->info(message: "[$client_ip] [$result->value] $uri");