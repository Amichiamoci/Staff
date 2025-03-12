<?php

use Amichiamoci\Controllers\ApiController;
use Amichiamoci\Controllers\EmailController;
use Amichiamoci\Controllers\HomeController;
use Amichiamoci\Controllers\UserController;
use Amichiamoci\Controllers\FileController;
use Amichiamoci\Controllers\SportController;
use Amichiamoci\Controllers\StaffController;
use Amichiamoci\Controllers\TeamsController;
use Amichiamoci\Routing\Router;
use Amichiamoci\Utils\Security;

$router = new Router(logger: $log, base_path: INSTALLATION_PATH);

$router->AddController(controller: HomeController::class, route_base: '/');
$router->AddController(controller: UserController::class, route_base: '/user');
$router->AddController(controller: FileController::class, route_base: '/file');
$router->AddController(controller: StaffController::class, route_base: '/staff');
$router->AddController(controller: TeamsController::class, route_base: '/teams');
$router->AddController(controller: SportController::class, route_base: '/sport');
$router->AddController(controller: EmailController::class, route_base: '/email');

// This controller is not present by default
$enable_api = Security::LoadEnvironmentOfFromFile(var: 'ENABLE_API');
if (is_string(value: $enable_api) && (bool)$enable_api)
{
    $router->AddController(controller: ApiController::class, route_base: '/api');
}