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
define('DOMAIN', 'portal.example.invalid:8443');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

$router = new Router(applicationInstallationPath: dirname(__DIR__));
$router->AddController(HomeController::class, '/');
$router->AddController(FileController::class, '/file');

ob_start();
$status = $router->Dispatch('/login');
$html = ob_get_clean();
if ($status !== StatusCode::Ok || !str_contains($html, 'id="login-form"')
    || !str_contains($html, '/js/navigation.js')) {
    throw new RuntimeException('Simple MVC did not render the login view.');
}

ob_start();
$status = $router->Dispatch('/missing?fixture=private-query');
$html = ob_get_clean();
if ($status !== StatusCode::NotFound || str_contains($html, 'private-query')) {
    throw new RuntimeException('The error view did not handle the request safely.');
}

ob_start();
$status = $router->Dispatch('/church?id=1');
ob_end_clean();
if ($status !== StatusCode::NotFound) {
    throw new RuntimeException('The retired parish page is still routed.');
}

$fs = new VirtualFilesystem();
$fs->mountLocal('staff', sys_get_temp_dir(), name: 'Locale (Portale)');
$router->SetDefaultVariable('files', $fs);
$user = new User(1, 'Fixture administrator', time(), admin: true);
$router->SetUser($user);
$aboutRouter = new Router(pathPrefix: '/fixture-staff', applicationInstallationPath: dirname(__DIR__));
$aboutRouter->AddController(HomeController::class, '/');
$aboutRouter->SetUser($user);
ob_start();
$status = $aboutRouter->Dispatch('/fixture-staff/about');
$html = ob_get_clean();
$manifest = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
if ($status !== StatusCode::Ok || !str_contains($html, 'Informazioni sul portale')
    || !str_contains($html, 'id="swup"')
    || !str_contains($html, 'id="portal-nav"')
    || !str_contains($html, 'id="portal-footer"')
    || !str_contains($html, 'src="/fixture-staff/js/navigation.js"')
    || !str_contains($html, 'href="/fixture-staff/credits"')
    || !str_contains($html, 'Librerie installate')
    || !str_contains($html, '>' . htmlspecialchars($manifest['version']) . '</dd>')
    || !str_contains($html, '>' . htmlspecialchars(PHP_VERSION) . '</dd>')
    || !str_contains($html, '>portal.example.invalid:8443</dd>')
    || !str_contains($html, '>/fixture-staff</dd>')
    || !str_contains($html, '>richie314/simple-mvc</dt>')
    || !str_contains($html, '>'
        . htmlspecialchars(Composer\InstalledVersions::getPrettyVersion('richie314/simple-mvc')) . '</dd>')) {
    throw new RuntimeException('The about page did not render the versions and installation details.');
}

ob_start();
$status = $aboutRouter->Dispatch('/fixture-staff/credits');
$html = ob_get_clean();
if ($status !== StatusCode::Ok || !str_contains($html, htmlspecialchars($manifest['license']))
    || !str_contains($html, 'https://polyformproject.org/licenses/noncommercial/1.0.0')
    || !str_contains($html, 'I componenti di terze parti mantengono le rispettive condizioni.')) {
    throw new RuntimeException('The credits page did not show the portal license and its scope.');
}

$renderFooter = static function (string $P, ?User $user): string {
    ob_start();
    require dirname(__DIR__) . '/Views/Shared/Footer.php';
    return ob_get_clean();
};
$footer = $renderFooter('/fixture-staff', $user);
if (substr_count($footer, 'href="/fixture-staff/about"') !== 1
    || !str_contains($footer, 'Alimentato da')
    || !str_contains($footer, 'Portale Staff Amichiamoci')
    || str_contains($renderFooter('/fixture-staff', null), '<a ')) {
    throw new RuntimeException('The footer attribution must link to the about page only for signed-in users.');
}

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

$delegatedTournamentActions = [
    'tournament_create', 'tournament_add_team', 'tournament_remove_team', 'tournament_generate_calendar',
];
$sport = new ReflectionClass(Amichiamoci\Controllers\SportController::class);
foreach ($sport->getMethods(ReflectionMethod::IS_PUBLIC) as $action) {
    if (!str_starts_with($action->name, 'match_') && !str_starts_with($action->name, 'tournament_')) {
        continue;
    }
    $attributes = $action->getAttributes(Richie314\SimpleMvc\Controllers\Attributes\RequireLogin::class);
    $adminOnly = !in_array($action->name, $delegatedTournamentActions, true);
    if ($attributes === [] || ($attributes[0]->getArguments()['requireAdmin'] ?? false) !== $adminOnly) {
        throw new RuntimeException("Unexpected login requirements for {$action->name}.");
    }
}
echo "Sport authorization checks passed.\n";
