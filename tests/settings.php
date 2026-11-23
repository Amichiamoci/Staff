<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;

use Amichiamoci\Controllers\{HomeController, SportController, StaffController, TeamsController};
use Amichiamoci\Models\{Settings, Staff, User};

define('SITE_NAME', 'Staff fixture');
define('MAIN_SITE_URL', 'https://example.invalid');
define('RECAPTCHA_PUBLIC_KEY', 'fixture-public');
$_ENV['CRON_CAPTURE_OUTGOING_ADDRESS'] = 'capture@example.invalid';
$_ENV['SESSION_COOKIE_SECURE'] = 'false';
$_ENV['PHP_INFO_FIXTURE'] = 'synthetic-private-setting';
$_SERVER['HTTP_AUTHORIZATION'] = 'synthetic-private-header';

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)$argv[1]);
$schema = file_get_contents(dirname(__DIR__) . '/queries/db-schema.sql');
$settingsSchema = strstr($schema, '-- System configuration');
if ($settingsSchema === false) {
    throw new RuntimeException('The database schema is missing the settings section.');
}
$schema = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', strstr($settingsSchema, 'COMMIT;', true));
$applySchema = static function () use ($db, $schema): void {
    $db->multi_query($schema);
    while ($db->more_results()) {
        $db->next_result();
    }
};
$applySchema();

$admin = new User(1, 'Fixture administrator', time(), admin: true);
$staffUser = new User(2, 'Fixture staff', time());
$staffUser->IdStaff = 1;
$dispatch = static function (
    User $user, string $path, StatusCode $expected, ?array $post = null,
) use ($db): string {
    $_SERVER['REQUEST_METHOD'] = $post === null ? 'GET' : 'POST';
    $_POST = $post ?? [];
    $router = new Router(applicationInstallationPath: dirname(__DIR__));
    $router->AddController(HomeController::class, '/');
    $router->AddController(StaffController::class, '/staff');
    $router->AddController(TeamsController::class, '/teams');
    $router->AddController(SportController::class, '/sport');
    $router->SetDbConnection($db);
    $router->SetUser($user);
    if ($user->IdStaff !== null) {
        $router->SetDefaultVariable('staff', new Staff(1, 'Fixture staff', 1, 'Fixture', taglia: 'M', codice_fiscale: 'SYNTHETIC'));
    }

    ob_start();
    try {
        if ($router->Dispatch($path) !== $expected) {
            throw new RuntimeException("Unexpected status for $path.");
        }
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
};

$db->query("DELETE FROM settings WHERE name IN ('tournaments_enabled', 'api_enabled')");
$html = $dispatch($admin, '/settings', StatusCode::Ok);
foreach ([
    'capture@example.invalid', 'Attivo', '/file/list', 'name="tournaments_enabled"',
    'name="api_enabled"', 'Reverse proxy HTTPS', 'Cookie sicuri non forzati',
    'id="open-php-info"', 'id="php-info-dialog"', 'sandbox', 'PHP Version',
] as $expected) {
    if (!str_contains($html, $expected)) {
        throw new RuntimeException('The settings page omitted system information.');
    }
}
if (str_contains($html, 'synthetic-private-setting') || str_contains($html, 'synthetic-private-header')) {
    throw new RuntimeException('The PHP report exposed environment or request data.');
}
if (str_contains($html, '/api/admin')) {
    throw new RuntimeException('The API token manager was shown while the API was disabled.');
}
$dispatch($admin, '/settings', StatusCode::BadRequest, [
    'csrf_token' => $_SESSION['account_csrf_token'],
    'api_enabled' => 'invalid',
]);
$dispatch($admin, '/settings', StatusCode::BadRequest, ['csrf_token' => 'invalid']);
if (Settings::Read($db) !== ['subscriptions_enabled' => true, 'teams_enabled' => true, 'tournaments_enabled' => false, 'api_enabled' => false]) {
    throw new RuntimeException('Feature defaults changed after an invalid submission.');
}
$dispatch($admin, '/settings', StatusCode::SeeOther, ['csrf_token' => $_SESSION['account_csrf_token']]);
$applySchema();
$dispatch($staffUser, '/staff/iscrivi', StatusCode::NotAuthorized, ['id' => '1']);
$dispatch($staffUser, '/teams/new', StatusCode::NotAuthorized, ['name' => 'Fixture']);
$dispatch($staffUser, '/teams/edit?id=1', StatusCode::NotAuthorized);
$dispatch($admin, '/teams/new', StatusCode::BadRequest);

$tournamentActions = ['tournament_create', 'tournament_add_team', 'tournament_remove_team', 'tournament_generate_calendar'];
$tournamentPost = ['csrf_token' => $_SESSION['account_csrf_token']];
foreach ($tournamentActions as $action) {
    $dispatch($staffUser, '/sport/' . $action, StatusCode::NotAuthorized, $tournamentPost);
    $dispatch($admin, '/sport/' . $action, StatusCode::BadRequest, $tournamentPost);
}

$db->query('CREATE TEMPORARY TABLE iscritti (id INT, dati_anagrafici INT, parrocchia INT)');
$db->query('INSERT INTO iscritti VALUES (91, 11, 1), (92, 12, 2)');
$db->query("CREATE PROCEDURE SingolaIscrizione(IN enrollment INT)
    SELECT id AS id_iscrizione, 'Sample' AS nome, 'Fixture' AS cognome, parrocchia AS id_parrocchia,
    'Fixture' AS parrocchia, 2025 AS anno, 'M' AS maglia, NULL AS id_tutore, NULL AS certificato_medico
    FROM iscritti WHERE id = enrollment");
try {
    $html = $dispatch($staffUser, '/staff/modifica_iscrizione?id=91', StatusCode::Ok);
    if (!str_contains($html, 'name="certificato"') || str_contains($html, 'name="taglia"')) {
        throw new RuntimeException('The staff subscription form should only accept a certificate.');
    }
    $dispatch($staffUser, '/staff/modifica_iscrizione?id=92', StatusCode::NotAuthorized);
} finally {
    $db->query('DROP PROCEDURE SingolaIscrizione');
}

$dispatch($admin, '/settings', StatusCode::SeeOther, [
    'csrf_token' => $_SESSION['account_csrf_token'],
    'subscriptions_enabled' => '1',
    'teams_enabled' => '1',
    'tournaments_enabled' => '1',
    'api_enabled' => '1',
]);
if (Settings::Read($db) !== ['subscriptions_enabled' => true, 'teams_enabled' => true, 'tournaments_enabled' => true, 'api_enabled' => true]) {
    throw new RuntimeException('The feature flags could not be re-enabled.');
}

$_ENV['SESSION_COOKIE_SECURE'] = '1';
$html = $dispatch($admin, '/settings', StatusCode::Ok);
if (!str_contains($html, '/api/admin') || !str_contains($html, 'Cookie sicuri forzati')) {
    throw new RuntimeException('The settings page omitted the enabled API or secure-cookie override.');
}

$db->query('CREATE TEMPORARY TABLE edizioni (anno INT)');
$db->query('CREATE TEMPORARY TABLE sport (id INT, nome VARCHAR(32))');
$db->query('CREATE TEMPORARY TABLE tipi_torneo (id INT, nome VARCHAR(32))');
$html = $dispatch($staffUser, '/sport/tournament_create', StatusCode::Ok);
if (!str_contains($html, 'name="csrf_token"') || !str_contains($html, 'name="edition"')) {
    throw new RuntimeException('Staff could not open the tournament creation form.');
}
$ordinaryUser = new User(3, 'Fixture user', time());
foreach ($tournamentActions as $action) {
    $dispatch($ordinaryUser, '/sport/' . $action, StatusCode::NotAuthorized, $tournamentPost);
    $dispatch($staffUser, '/sport/' . $action, StatusCode::BadRequest, $tournamentPost);
}
$dispatch($staffUser, '/sport/tournament_create', StatusCode::BadRequest, [
    'edition' => '1', 'sport' => '1', 'type' => '1', 'name' => 'Fixture',
]);

$dispatch($admin, '/settings', StatusCode::SeeOther, [
    'csrf_token' => $_SESSION['account_csrf_token'],
    'subscriptions_enabled' => '1',
    'teams_enabled' => '1',
    'tournaments_enabled' => '1',
]);
if (Settings::Read($db)['api_enabled']) {
    throw new RuntimeException('The API could not be disabled again.');
}

echo "Settings checks passed.\n";
