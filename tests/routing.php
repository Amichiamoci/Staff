<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Amichiamoci\Controllers\{FileController, HomeController};
use Amichiamoci\Models\User;
use Richie314\SimpleVirtualFS\VirtualFilesystem;
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
$router->AddController(FileController::class, '/file');

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

$fs = new VirtualFilesystem();
$fs->mountLocal('staff', sys_get_temp_dir(), name: 'Locale (Portale)');
$router->SetDefaultVariable('files', $fs);
$router->SetUser(new User(1, 'Fixture administrator', time(), admin: true));
ob_start();
$status = $router->Dispatch('/file/list');
$html = ob_get_clean();
if ($status !== StatusCode::Ok || !str_contains($html, 'Locale (Portale)')
    || str_contains($html, 'Locale (Web App)') || str_contains($html, 'Google Drive')) {
    throw new RuntimeException('The administrator file route did not render the source index.');
}

ob_start();
$status = $router->Dispatch('/file/list?source=drive%3A%2F%2F%2F');
ob_end_clean();
if ($status !== StatusCode::NotFound) {
    throw new RuntimeException('An unregistered source was accepted by the file browser.');
}

$fixtureDirectory = sys_get_temp_dir() . '/staff-browser-' . bin2hex(random_bytes(12));
mkdir($fixtureDirectory, 0700);
try {
    $fs->mountLocal('drive', $fixtureDirectory, key: random_bytes(32), name: 'Google Drive');
    $fs->write('drive:///sample.txt', 'text fixture');
    $fs->write('drive:///cipher.pdf', 'encrypted fixture');

    ob_start();
    $status = $router->Dispatch('/file/list?source=drive%3A%2F%2F%2F');
    $html = ob_get_clean();
    if ($status !== StatusCode::Ok || !str_contains($html, 'sample.txt') || !str_contains($html, 'cipher.pdf')) {
        throw new RuntimeException('The file browser did not list both files.');
    }
    foreach (['sample.txt' => 'text fixture', 'cipher.pdf' => 'encrypted fixture'] as $name => $expected) {
        ob_start();
        $status = $router->Dispatch('/file/download?name=' . rawurlencode('drive:///' . $name));
        $content = ob_get_clean();
        if ($status !== StatusCode::Ok || $content !== $expected) {
            throw new RuntimeException('The administrator could not download from the encrypted source.');
        }
    }
} finally {
    $fs->delete('drive:///sample.txt');
    $fs->delete('drive:///cipher.pdf');
    rmdir($fixtureDirectory);
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
