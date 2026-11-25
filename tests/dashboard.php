<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;

use Amichiamoci\Controllers\HomeController;
use Amichiamoci\Models\{DashboardStatistics, Staff, User};

define('SITE_NAME', 'Dashboard fixture');
define('MAIN_SITE_URL', 'https://example.invalid');
$_SERVER['REQUEST_METHOD'] = 'GET';

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)$argv[1]);
$schema = 'fixture_dashboard_' . bin2hex(random_bytes(8));
$db->query("CREATE DATABASE `$schema`");
$db->select_db($schema);
register_shutdown_function(static fn() => $db->query("DROP DATABASE `$schema`"));

$db->query('CREATE TABLE settings (name VARCHAR(64), enabled BOOLEAN)');
$db->query("INSERT INTO settings VALUES ('subscriptions_enabled', FALSE), ('teams_enabled', TRUE)");
$db->query('CREATE TABLE edizioni (id INT, anno YEAR)');
$db->query('CREATE TABLE parrocchie (id INT, nome VARCHAR(64), indirizzo VARCHAR(64), website VARCHAR(64))');
$db->query('CREATE TABLE iscritti (
    id INT, dati_anagrafici INT, edizione INT, parrocchia INT, taglia_maglietta VARCHAR(8)
)');
$db->query('CREATE TABLE staffisti (id INT, parrocchia INT)');
$db->query('CREATE TABLE partecipaz_staff_ediz (staff INT, edizione INT, maglia VARCHAR(8))');
$db->query('CREATE TABLE sport (id INT, area VARCHAR(16))');
$db->query('CREATE TABLE tornei (id INT, edizione INT, nome VARCHAR(64), sport INT)');
$db->query('CREATE TABLE partite (
    id INT, torneo INT, data DATE, orario TIME, campo INT, a_tavolino INT
)');
$db->query('CREATE TABLE punteggi (partita INT)');
$db->query('CREATE TABLE compleanni_oggi (nome VARCHAR(32), cognome VARCHAR(32), eta INT)');
$db->query('ALTER TABLE edizioni ADD motto VARCHAR(64)');
$db->multi_query(file_get_contents(dirname(__DIR__) . '/Database/views/10_dashboard.sql'));
while ($db->more_results()) {
    $db->next_result();
}

$currentYear = (int)date('Y');
$db->execute_query('INSERT INTO edizioni (id, anno) VALUES (1, 2024), (2, 2025), (3, ?), (4, ?)', [$currentYear, $currentYear + 1]);
$db->query("INSERT INTO parrocchie VALUES (1, 'Fixture parish', NULL, NULL), (2, 'Other parish', NULL, NULL)");
$db->query("INSERT INTO iscritti VALUES (1, 101, 1, 1, 'S'), (2, 101, 2, 1, 'M'),
    (3, 102, 2, 1, 'unknown'), (4, 103, 2, 2, 'S'), (5, 101, 2, 2, 'M')");
$db->query('INSERT INTO staffisti VALUES (201, 1), (202, 2)');
$db->query("INSERT INTO partecipaz_staff_ediz VALUES (201, 2, 'XL'), (202, 2, NULL), (201, 1, 'L')");
$db->query("INSERT INTO sport VALUES (1, 'Calcio'), (2, 'Pallavolo'), (3, 'Basket')");
$db->query("INSERT INTO tornei VALUES (1, 2, 'Fixture tournament', 1), (2, 2, 'Empty tournament', 2),
    (3, 1, 'Older tournament', 1), (4, 2, 'Fixture basket tournament', 3)");
$db->query("INSERT INTO partite VALUES (1, 1, NULL, NULL, NULL, NULL),
    (2, 1, '2025-06-01', '12:00:00', 1, NULL), (3, 1, NULL, NULL, NULL, NULL), (4, 1, NULL, NULL, NULL, 1)");
$db->query('INSERT INTO punteggi VALUES (3), (3)');
$db->query("CREATE PROCEDURE ProblemiParrocchia(IN parish_id INT, IN year_filter YEAR)
    SELECT 102 AS id, 3 AS iscrizione, 'Fixture participant' AS chi, 'SYNTHETIC' AS cf, 'M' AS sesso,
        'Mancante' AS doc, NULL AS scadenza,
        'Mancante' AS certificato, 'Mancante' AS tutore,
        NULL AS eta, NULL AS maglia, NULL AS email, NULL AS telefono
    WHERE parish_id = 1 AND year_filter = 2025");

$check = static function (mixed $actual, mixed $expected, string $message): void {
    if ($actual !== $expected) {
        throw new RuntimeException($message);
    }
};

try {
    $readiness = DashboardStatistics::RegistrationReadiness($db, 2025, 1);
    $check(array_column($readiness['rows'], 'values'), [[1, 1]], 'Readiness counted issues instead of enrollments.');
    $check(array_values($readiness['issues']), [1, 1, 1], 'Readiness did not group overlapping document flags.');
    $check(count(DashboardStatistics::RegistrationReadiness($db, 2025, null)['rows']), 2, 'Admin readiness omitted a parish.');
    $history = DashboardStatistics::Participation($db, 2025);
    $check(array_column($history, 'values'), [[1, 1, 0], [3, 2, 1]], 'History did not deduplicate people.');
    $check($history[1]['label'], '2025 (finora)', 'Current-year history omitted its label.');

    $shirts = DashboardStatistics::ShirtDemand($db, 2, 1);
    $check(array_column($shirts, 'label'), ['M', 'XL', 'Da assegnare'], 'Shirt demand did not order sizes or isolate unknown sizes.');
    $check(array_column($shirts, 'values'), [[1, 0], [0, 1], [1, 0]], 'Shirt demand mixed parish, edition, or role totals.');
    $globalShirts = DashboardStatistics::ShirtDemand($db, 2, null);
    $check(array_column($globalShirts, 'values', 'label')['Da assegnare'], [1, 1], 'Missing staff sizes were excluded.');
    $tournaments = DashboardStatistics::TournamentReadiness($db, 2);
    $check(array_keys($tournaments), ['Calcio', 'Pallavolo', 'Basket'], 'Tournament readiness did not split sport areas.');
    $check(array_column($tournaments['Calcio'], 'values'), [[1, 1, 2]], 'Tournament readiness misclassified scores or forfeits.');
    $check(array_column($tournaments['Pallavolo'], 'values'), [[0, 0, 0]], 'Tournament readiness omitted an empty tournament.');
    $check($tournaments['Basket'][0]['url'], '/sport/tournament?id=4', 'Basket readiness omitted its tournament link.');

    $render = static function (string $path, User $user, ?Staff $staff = null) use ($db): array {
        $router = new Router(pathPrefix: '/fixture', applicationInstallationPath: dirname(__DIR__));
        $router->AddController(HomeController::class, '/');
        $router->SetDbConnection($db);
        $router->SetUser($user);
        if ($staff !== null) {
            $router->SetDefaultVariable('staff', $staff);
        }

        ob_start();
        try {
            return [$router->Dispatch('/fixture' . $path), ob_get_contents()];
        } finally {
            ob_end_clean();
        }
    };

    $staff = new Staff(201, 'Fixture staff', 1, 'Fixture parish');
    $user = new User(1, 'Fixture user', time());
    [$status, $home] = $render('/', $user, $staff);
    $check($status, StatusCode::Ok, 'The parameterless dashboard did not render.');
    $check(substr_count($home, 'data-dashboard-chart='), 2, 'The staff dashboard omitted charts.');
    $document = Dom\HTMLDocument::createFromString($home, LIBXML_NOERROR, 'UTF-8');
    $check(trim($document->querySelector('main h1')->textContent), (string)$currentYear, 'An edition without a motto did not show only its year.');
    foreach (['readiness', 'tournaments'] as $filter) {
        $card = $document->getElementById("dashboard-$filter");
        $check($card?->querySelector('select option[selected]')?->getAttribute('value'), (string)$currentYear, 'A chart did not default to the current edition.');
        $check($card?->querySelector('form[action]'), null, 'A chart still had a submit action.');
    }
    $check($document->querySelector('#dashboard-history'), null, 'Participation history was still shown on the dashboard.');
    $check($document->querySelector('#dashboard-shirts'), null, 'Shirt demand was still shown on the dashboard.');
    $check($document->querySelectorAll('#dashboard-tournaments select')->length, 2, 'The tournament card omitted the area selector.');
    $check(str_contains($home, 'aria-labelledby="chart-details-tournaments"'), true, 'The tournament card omitted its labeled modal.');
    $check(str_contains($home, 'Fixture tournament'), false, 'The home response embedded chart data.');
    $check(
        $document->querySelector('main > header + section[aria-labelledby="today-birthdays"] + .dashboard-cards') !== null
            && strpos($home, 'id="dashboard-tournaments"') < strpos($home, 'Anagrafiche senza email'),
        true,
        'The new chart group moved outside the existing and email cards.',
    );
    $check(str_contains($home, 'Nessun compleanno oggi.'), true, 'The birthday banner omitted the empty state.');
    $db->query("INSERT INTO compleanni_oggi VALUES ('Fixture <nome>', 'Cognome', 18)");
    $check(str_contains($home, 'email-stats-chart'), false, 'The demographics charts remained on the dashboard.');

    $admin = new User(2, 'Fixture admin', time(), admin: true);
    [$status, $adminHome] = $render('/', $admin);
    $check($status, StatusCode::Ok, 'The admin dashboard did not render.');
    $check(str_contains($adminHome, 'Fixture &lt;nome&gt;'), true, 'The banner omitted or failed to escape a birthday name.');
    $check(str_contains($adminHome, 'Email duplicate') || str_contains($adminHome, 'Stato CRON'), false, 'Admin cards remained on the home dashboard.');
    $check(str_contains($adminHome, 'id="double_subscriptions-list"'), false, 'Double subscriptions were shown while registrations were closed.');
    $db->query("UPDATE settings SET enabled = TRUE WHERE name = 'subscriptions_enabled'");
    $db->execute_query('UPDATE edizioni SET motto = ? WHERE id = 3', ['Fixture <motto>']);
    [$status, $adminHome] = $render('/', $admin);
    $check($status, StatusCode::Ok, 'The dashboard did not render for open registrations.');
    $check(str_contains($adminHome, 'id="double_subscriptions-list"'), true, 'The admin card was missing while registrations were open.');
    $adminPage = Dom\HTMLDocument::createFromString($adminHome, LIBXML_NOERROR, 'UTF-8');
    $heading = $adminPage->querySelector('main h1');
    $check(trim($heading->textContent), "$currentYear: Fixture <motto>", 'The dashboard omitted or did not escape the current motto.');
    [$status, $home] = $render('/', $user);
    $check($status, StatusCode::Ok, 'The ordinary user dashboard did not render.');
    $check(substr_count($home, 'data-dashboard-chart='), 1, 'The ordinary user dashboard exposed readiness data.');
    $check(str_contains($home, 'id="double_subscriptions-list"'), false, 'The admin card was shown to an ordinary user.');

    [$status, $body] = $render('/dashboard_chart?chart=readiness&year=2025', $user, $staff);
    $check($status, StatusCode::Ok, 'The staff readiness request did not load.');
    $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    $check(array_column($data['rows'], 'url'), ['/staff?church=1&year=2025'], 'The staff endpoint exposed another parish or edition.');
    [$status] = $render('/dashboard_chart?chart=readiness&year=2025', $user);
    $check($status, StatusCode::NotAuthorized, 'Readiness data was exposed to ordinary users.');
    [$status, $body] = $render('/dashboard_chart?chart=shirts&year=2024', $user, $staff);
    $check($status, StatusCode::Ok, 'Shirt demand did not load by year.');
    $check(array_column(json_decode($body, true, flags: JSON_THROW_ON_ERROR)['rows'], 'label'), ['S', 'L'], 'Shirt demand ignored its edition filter.');
    [$status, $body] = $render('/dashboard_chart?chart=history&year=2024', $user, $staff);
    $check($status, StatusCode::Ok, 'Participation history did not load.');
    $check(
        array_column(json_decode($body, true, flags: JSON_THROW_ON_ERROR)['rows'], 'label'),
        ['2024', '2025', "$currentYear (finora)"],
        'History was cut off early or included a future edition.',
    );
    [$status, $body] = $render('/dashboard_chart?chart=tournaments&year=2025', $user);
    $check($status, StatusCode::Ok, 'Tournament readiness did not load.');
    $check(array_keys(json_decode($body, true, flags: JSON_THROW_ON_ERROR)['groups']), ['Calcio', 'Pallavolo', 'Basket'], 'Tournament areas were missing from the endpoint.');
    [$status] = $render('/dashboard_chart?chart=shirts&year=1901', $user);
    $check($status, StatusCode::NotFound, 'An unknown edition was accepted.');
    [$status, $statistics] = $render('/statistics', $user);
    $check($status, StatusCode::Ok, 'The statistics page did not render.');
    $check(str_contains($statistics, 'id="stats-chart"') && str_contains($statistics, 'id="email-stats-chart"'), true, 'The statistics page omitted relocated charts.');
    $statisticsPage = Dom\HTMLDocument::createFromString($statistics, LIBXML_NOERROR, 'UTF-8');
    $historyCard = $statisticsPage->querySelector('#dashboard-history');
    $check($historyCard !== null, true, 'The statistics page omitted participation history.');
    $check($historyCard?->querySelector('select'), null, 'History displayed a year selector.');
    $check(str_contains($statistics, 'aria-labelledby="chart-details-history"'), true, 'Participation history omitted its details modal.');
    $check($statisticsPage->querySelector('#dashboard-shirts select') !== null, true, 'The statistics page omitted the shirt demand edition selector.');
    $check(str_contains($statistics, 'aria-labelledby="chart-details-shirts"'), true, 'The shirt demand chart omitted its details modal.');
    $db->query('DELETE FROM edizioni WHERE id = 3');
    [$status, $home] = $render('/', $user);
    $check($status, StatusCode::Ok, 'The dashboard did not render without a current edition.');
    $document = Dom\HTMLDocument::createFromString($home, LIBXML_NOERROR, 'UTF-8');
    $check(trim($document->querySelector('main h1')->textContent), 'Panoramica', 'The heading did not fall back without a current edition.');
} finally {
    $db->query('DROP PROCEDURE ProblemiParrocchia');
}

echo "Dashboard statistics checks passed.\n";
