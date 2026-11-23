<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Amichiamoci\Models\User;

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)$argv[1]);
$db->query('SET NAMES utf8mb4 COLLATE utf8mb4_general_ci');
$db->query('CREATE TEMPORARY TABLE utenti (
    id INT PRIMARY KEY, user_name VARCHAR(100), password VARCHAR(255), is_admin BOOL, is_blocked BOOL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->query('CREATE TEMPORARY TABLE sessioni (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT)');
$db->query('CREATE PROCEDURE StartSession(IN userId INT, IN flag VARCHAR(64), IN ip VARCHAR(32))
    BEGIN
        INSERT INTO sessioni (user_id) VALUES (userId);
        SELECT LAST_INSERT_ID() AS session_id;
    END');

try {
    $password = bin2hex(random_bytes(16));
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->execute_query("INSERT INTO utenti VALUES (1, 'SampleAccount', ?, 0, 0), (2, 'BlockedFixture', ?, 0, 1)", [$hash, $hash]);
    $login = static fn(string $name, string $secret): bool => User::Login($db, $name, $secret, 'Fixture', '127.0.0.1');

    if ($login('missing', $password) || $login('BlockedFixture', $password) || $login('SampleAccount', 'invalid')) {
        throw new RuntimeException('Login accepted an invalid account or password.');
    }
    if (!$login('sampleaccount', $password) || ($_SESSION[User::$USER_ID] ?? null) !== 1) {
        throw new RuntimeException('A valid account could not log in across collations.');
    }
    session_destroy();
} finally {
    $db->query('DROP PROCEDURE StartSession');
}

echo "Login checks passed.\n";
