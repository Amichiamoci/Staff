<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;

use Amichiamoci\Controllers\{StaffController, UserController};
use Amichiamoci\Models\User;

define('SITE_NAME', 'Portal fixture');
define('MAIN_SITE_URL', 'https://example.invalid');
define('RECAPTCHA_PUBLIC_KEY', '');
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)$argv[1]);
$schema = 'fixture_users_' . bin2hex(random_bytes(8));
$db->query("CREATE DATABASE `$schema`");
$db->select_db($schema);
register_shutdown_function(static fn() => $db->query("DROP DATABASE `$schema`"));

$db->query('CREATE TABLE utenti (
    id INT PRIMARY KEY, user_name VARCHAR(100), is_admin BOOL, is_blocked BOOL DEFAULT 0
)');
$db->query("INSERT INTO utenti (id, user_name, is_admin) VALUES
    (1, 'Admin fixture', 1), (2, 'Member <fixture>', 0), (3, 'Unlinked fixture', 0)");
$db->query('CREATE TABLE sessioni (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, time_start DATETIME,
    time_log DATETIME, user_flag VARCHAR(64), device_ip VARCHAR(32)
)');
$db->query("INSERT INTO sessioni (user_id, time_start, time_log, user_flag, device_ip) VALUES
    (2, '2000-01-01 10:00:00', '2000-01-01 11:15:00', 'device fixture', '192.0.2.1'),
    (3, '2000-01-02 10:00:00', '2000-01-02 10:00:00', 'device fixture', NULL)");
$db->query('CREATE TEMPORARY TABLE staff_correnti_incompleto (
    id_staffista INT, nome VARCHAR(100), cognome VARCHAR(100), id_parrocchia INT, parrocchia VARCHAR(100),
    lista_commissioni VARCHAR(100), maglia VARCHAR(10), referente BOOL, cf VARCHAR(16)
)');
$db->query("INSERT INTO staff_correnti_incompleto VALUES
    (7, 'Staff', '<fixture>', 9, 'Parish fixture', 'Outreach', 'M', 1, 'FIXTURECF0000000')");
$db->query('CREATE TEMPORARY TABLE staff_attuali LIKE staff_correnti_incompleto');
$db->query('INSERT INTO staff_attuali SELECT * FROM staff_correnti_incompleto');
$db->query('CREATE TEMPORARY TABLE staff_list_raw (staff INT, nome_completo VARCHAR(100))');
$db->query("INSERT INTO staff_list_raw VALUES (7, 'Staff <fixture>'), (8, 'Historical fixture')");
$db->query('CREATE TEMPORARY TABLE users_extended (
    id INT, user_name VARCHAR(100), last_seen_time DATETIME, is_admin BOOL, is_blocked BOOL,
    staff_id INT, anagrafica_id INT, full_name VARCHAR(100)
)');
$db->query("INSERT INTO users_extended VALUES
    (2, 'Member <fixture>', '2000-01-01 11:15:00', 0, 0, 7, 5, 'Staff <fixture>'),
    (3, 'Unlinked fixture', NULL, 0, 0, NULL, NULL, NULL)");

$db->query(file_get_contents(dirname(__DIR__) . '/Database/views/09_user_sessions.sql'));

$procedures = [
    'GetStaffFromUserId' => "IN account_id INT)
        SELECT 'Staff <fixture>' AS nome, 5 AS id_anagrafica, 7 AS staffista WHERE account_id = 2",
    'BanUser' => 'IN account_id INT) UPDATE utenti SET is_blocked = 1 WHERE id = account_id',
    'RestoreUser' => 'IN account_id INT) UPDATE utenti SET is_blocked = 0 WHERE id = account_id',
];
$created = [];
try {
    foreach ($procedures as $name => $definition) {
        $db->query("CREATE PROCEDURE $name($definition");
        $created[] = $name;
    }

    $dispatch = static function (User $user, string $path, ?array $post = null) use ($db): array {
        $_SERVER['REQUEST_METHOD'] = $post === null ? 'GET' : 'POST';
        $_POST = $post ?? [];
        $router = new Router(pathPrefix: '/portal', applicationInstallationPath: dirname(__DIR__));
        $router->AddController(UserController::class, '/user');
        $router->AddController(StaffController::class, '/staff');
        $router->SetDbConnection($db);
        $router->SetUser($user);
        ob_start();
        $status = $router->Dispatch('/portal' . $path);
        return [$status, ob_get_clean()];
    };

    $admin = new User(1, 'Admin fixture', time(), admin: true);
    $member = new User(2, 'Member <fixture>', time());
    [$status, $html] = $dispatch($member, '/user/me');
    if ($status !== StatusCode::Ok || !str_contains($html, 'Staff &lt;fixture&gt;')
        || !str_contains($html, 'Parish fixture') || !str_contains($html, 'Outreach')
        || !str_contains($html, '/portal/staff/view?id=7') || !str_contains($html, '75 min')
        || str_contains($html, 'Gestione account') || str_contains($html, 'device fixture')
    ) {
        throw new RuntimeException('The regular account did not show its linked staff profile and login history.');
    }

    [$status] = $dispatch($member, '/user/view?id=3');
    if ($status !== StatusCode::NotAuthorized) {
        throw new RuntimeException('A regular user could open another account.');
    }

    [$status, $html] = $dispatch($admin, '/user/activity');
    $latest = strpos($html, '/portal/user/view?id=3');
    $earlier = strpos($html, '/portal/user/view?id=2');
    if ($status !== StatusCode::Ok || $latest === false || $earlier === false || $latest >= $earlier) {
        throw new RuntimeException('The activity page did not show the newest login first with account links.');
    }

    [$status, $html] = $dispatch($admin, '/user/all');
    if ($status !== StatusCode::Ok || !str_contains($html, 'Nessuna associazione')
        || !str_contains($html, '/portal/staff/view?id=7')
    ) {
        throw new RuntimeException('The accounts directory did not show linked and unlinked accounts.');
    }

    [$status, $html] = $dispatch($admin, '/staff/all');
    if ($status !== StatusCode::Ok || !str_contains($html, 'Historical fixture')
        || !str_contains($html, 'Non iscritto') || !str_contains($html, 'Outreach')
    ) {
        throw new RuntimeException('The staff directory did not show both current and historical entries.');
    }
    if (str_contains($html, 'Codice fiscale') || str_contains($html, 'FIXTURECF0000000')) {
        throw new RuntimeException('The staff directory exposed a fiscal code.');
    }

    [$status, $html] = $dispatch($admin, '/staff/current');
    if ($status !== StatusCode::Ok || str_contains($html, 'Historical fixture') || !str_contains($html, 'Outreach')) {
        throw new RuntimeException('The current edition directory did not filter historical entries.');
    }
    if (str_contains($html, 'Codice fiscale') || str_contains($html, 'FIXTURECF0000000')) {
        throw new RuntimeException('The staff directory exposed a fiscal code.');
    }

    [$status, $html] = $dispatch($admin, '/user/view?id=2');
    $token = $_SESSION['account_csrf_token'];
    if ($status !== StatusCode::Ok || !str_contains($html, 'action="/portal/user/ban"')
        || str_contains($html, 'action="/portal/user/restore"')
    ) {
        throw new RuntimeException('An enabled account did not show only the block action.');
    }

    [$status] = $dispatch($admin, '/user/ban', ['target_id' => '2']);
    if ($status !== StatusCode::BadRequest || User::ById($db, 2)->IsBanned) {
        throw new RuntimeException('A block request without the account form token was accepted.');
    }

    [$status] = $dispatch($admin, '/user/ban', ['target_id' => '1', 'csrf_token' => $token]);
    if ($status !== StatusCode::BadRequest || User::ById($db, 1)->IsBanned) {
        throw new RuntimeException('An administrator could block their own account.');
    }

    [$status, $html] = $dispatch($admin, '/user/ban', ['target_id' => '2', 'csrf_token' => $token]);
    if ($status !== StatusCode::Ok || !User::ById($db, 2)->IsBanned
        || !str_contains($html, 'action="/portal/user/restore"') || str_contains($html, 'action="/portal/user/ban"')
    ) {
        throw new RuntimeException('Blocking an account did not update its status and available action.');
    }

    [$status] = $dispatch($admin, '/user/restore', ['target_id' => '2', 'csrf_token' => $token]);
    if ($status !== StatusCode::Ok || User::ById($db, 2)->IsBanned) {
        throw new RuntimeException('Restoring an account did not enable it.');
    }
} finally {
    foreach ($created as $name) {
        $db->query("DROP PROCEDURE $name");
    }
}

echo "Account management checks passed.\n";
