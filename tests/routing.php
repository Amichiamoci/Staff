<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Amichiamoci\Controllers\HomeController;
use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;

define('SITE_NAME', 'Staff fixture');
define('RECAPTCHA_PUBLIC_KEY', '');
define('MAIN_SITE_URL', 'https://example.invalid');
define('POWERED_BY', 'https://example.invalid');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

$router = new Router(applicationInstallationPath: dirname(__DIR__));
$router->AddController(HomeController::class, '/');

ob_start();
$status = $router->Dispatch('/login');
$html = ob_get_clean();
if ($status !== StatusCode::Ok || !str_contains($html, 'id="login-form"')) {
    throw new RuntimeException('Simple MVC did not render the login view.');
}

ob_start();
$status = $router->Dispatch('/missing?fixture=private-query');
$html = ob_get_clean();
if ($status !== StatusCode::NotFound || str_contains($html, 'private-query')) {
    throw new RuntimeException('The error view did not handle the request safely.');
}

echo "Routing checks passed.\n";

$sport = new ReflectionClass(Amichiamoci\Controllers\SportController::class);
foreach ($sport->getMethods(ReflectionMethod::IS_PUBLIC) as $action) {
    if (!str_starts_with($action->name, 'match_') && !str_starts_with($action->name, 'tournament_')) {
        continue;
    }
    $attributes = $action->getAttributes(Richie314\SimpleMvc\Controllers\Attributes\RequireLogin::class);
    if ((($attributes[0] ?? null)?->getArguments()['requireAdmin'] ?? false) !== true) {
        throw new RuntimeException("Sport action {$action->name} must require an administrator.");
    }
}
echo "Sport authorization checks passed.\n";
