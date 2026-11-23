<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;

use Amichiamoci\Controllers\EmailController;
use Amichiamoci\Models\{Email, User};

define('SITE_NAME', 'Staff fixture');
define('MAIN_SITE_URL', 'https://example.invalid');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)$argv[1]);
$db->query('CREATE TEMPORARY TABLE email (
    id INT PRIMARY KEY, dest VARCHAR(64) NOT NULL, subject VARCHAR(128),
    sent DATETIME NOT NULL, opened DATETIME, ricevuta BOOL NOT NULL
)');
$db->query("INSERT INTO email
    SELECT seq, 'archive+fixture@example.invalid', CONCAT('Sample ', seq),
           DATE_ADD('2026-01-01', INTERVAL seq MINUTE), NULL, TRUE
    FROM seq_1_to_505");
$db->query("INSERT INTO email VALUES
    (506, 'other@example.invalid', 'Same time', '2026-01-01 08:25:00', NULL, FALSE),
    (507, 'archive+fixture@example.invalid', 'Older imported email', '2025-12-31 00:00:00', NULL, TRUE)");

$recent = Email::All($db);
$history = Email::All($db, 'archive+fixture@example.invalid');
if (count($recent) !== 500 || $recent[0]->Id !== 506 || $recent[1]->Id !== 505
    || count($history) !== 506 || $history[0]->Id !== 505 || $history[505]->Id !== 507
    || Email::Recipients($db) !== ['archive+fixture@example.invalid', 'other@example.invalid']
) {
    throw new RuntimeException('Email ordering, limit or recipient selection is incorrect.');
}

$dispatch = static function (string $path) use ($db): array {
    $router = new Router(applicationInstallationPath: dirname(__DIR__));
    $router->AddController(EmailController::class, '/email');
    $router->SetDbConnection($db);
    $router->SetUser(new User(1, 'Fixture administrator', time(), admin: true));
    ob_start();
    $status = $router->Dispatch($path);
    return [$status, ob_get_clean()];
};

[$status, $html] = $dispatch('/email/history?recipient=archive%2Bfixture%40example.invalid');
if ($status !== StatusCode::Ok || !preg_match('/value="archive\+fixture@example\.invalid"\s+selected\b/', $html)
    || !str_contains($html, 'Older imported email') || str_contains($html, 'Same time')
) {
    throw new RuntimeException('The selected recipient history was not rendered correctly.');
}

[$status, $html] = $dispatch('/email/send');
if ($status !== StatusCode::Ok || !str_contains($html, 'src="/quill/quill.js"')
    || !str_contains($html, 'href="/quill/quill.snow.css"')
    || !is_file(dirname(__DIR__) . '/Public/quill/quill.js') || !str_contains($html, 'name="csrf_token"')
) {
    throw new RuntimeException('The compose form did not include its editor and CSRF token.');
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['to' => 'recipient@example.invalid', 'subject' => 'Fixture', 'body' => 'Fixture'];
[$status] = $dispatch('/email/send');
if ($status !== StatusCode::BadRequest) {
    throw new RuntimeException('A send request without a CSRF token was accepted.');
}

echo "Email management checks passed.\n";
