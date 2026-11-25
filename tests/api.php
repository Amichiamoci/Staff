<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;
use Richie314\SimpleVirtualFS\VirtualFilesystem;

use Amichiamoci\Api\{Iscrizioni, Login, Sport, System};
use Amichiamoci\Controllers\ApiController;
use Amichiamoci\Models\Api\Token;
use Amichiamoci\Models\User;
use Amichiamoci\Utils\Database;

define('SITE_NAME', 'Portal test');

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)$argv[1]);
Database::ConfigureConnection($db);
$schema = 'fixture_api_' . bin2hex(random_bytes(8));
$db->query("CREATE DATABASE `$schema`");
$db->select_db($schema);

$check = static function (bool $condition, string $description): void {
    if (!$condition) {
        throw new RuntimeException($description);
    }
};

try {
    foreach (preg_split('~^DELIMITER (?:;|//)\h*$~m', Database::GetSchema()) as $index => $sql) {
        if ($index % 2 === 0) {
            $db->multi_query($sql);
            while ($db->more_results()) {
                $db->next_result();
            }
        } else {
            foreach (preg_split('~//\h*$~m', trim($sql), flags: PREG_SPLIT_NO_EMPTY) as $statement) {
                if (trim($statement) !== '') {
                    $db->query($statement);
                }
            }
        }
    }

    $email = 'participant@example.invalid';
    $otherEmail = 'another@example.invalid';
    $appToken = 'SyntheticAppToken';
    $db->execute_query("INSERT INTO api_token (name, `key`) VALUES ('Fixture', ?)", [$appToken]);
    $tokenId = $db->insert_id;
    $db->query("INSERT INTO parrocchie (nome) VALUES ('Fixture parish'), ('Second parish')");
    $db->query("UPDATE settings SET enabled = 1 WHERE name = 'subscriptions_enabled'");
    $db->query("INSERT INTO sport (nome, area) VALUES ('Fixture sport', 'Calcio')");
    foreach (['Unknown', 'calcio', 'Calcio '] as $area) {
        try {
            $db->execute_query('INSERT INTO sport (nome, area) VALUES (?, ?)', ['Invalid fixture sport', $area]);
            throw new RuntimeException('The schema accepted an invalid sport area.');
        } catch (mysqli_sql_exception $error) {
            $check(in_array($error->getCode(), [3819, 4025], true), 'The sport area constraint must reject invalid values.');
        }
    }
    $db->query("INSERT INTO squadre (nome, parrocchia, sport, edizione) VALUES ('Fixture home', 1, 1, 1), ('Fixture away', 2, 1, 1)");
    $db->query("INSERT INTO tornei (edizione, nome, sport, tipo_torneo) VALUES (1, 'Fixture tournament', 1, 1)");
    $db->query("INSERT INTO partecipaz_squad_torneo (torneo, squadra) VALUES (1, 1), (1, 2)");
    $db->query("INSERT INTO campi (nome, indirizzo, posizione) VALUES ('Fixture court', 'Example Street', POINT(12, 41))");
    $db->query("INSERT INTO partite (torneo, data, orario, campo, squadra_casa, squadra_ospite)
                VALUES (1, CURRENT_DATE, '17:00', 1, 1, 2), (1, NULL, NULL, NULL, 1, 2)");
    $db->query('UPDATE partite SET a_tavolino = 2 WHERE id = 2');
    $db->query("INSERT INTO punteggi (partita, home, guest) VALUES (1, '3', '1'), (1, '2', '0')");
    $db->query('UPDATE partite SET a_tavolino = 2 WHERE id = 2');
    $db->query("INSERT INTO punteggio_parrocchia (parrocchia, edizione, punteggio) VALUES (1, 1, '10')");

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REMOTE_ADDR'] = '::1';
    $files = new VirtualFilesystem();
    $router = new Router(pathPrefix: '/staff', applicationInstallationPath: dirname(__DIR__));
    $router->AddController(ApiController::class, '/api');
    $router->SetDbConnection($db);
    $router->SetDefaultVariable('files', $files);
    $get = static function (string $path, StatusCode $status = StatusCode::Ok) use ($router, $check): array {
        ob_start();
        try {
            $actual = $router->Dispatch('/staff/api/' . $path);
            $check($actual === $status, "Unexpected API status {$actual->value} for $path.");
            return $status === StatusCode::ServerError ? [] : json_decode(ob_get_contents(), true, flags: JSON_THROW_ON_ERROR);
        } finally {
            ob_end_clean();
        }
    };

    $get('event', StatusCode::NotAuthorized);
    $_SERVER['HTTP_APP_BEARER'] = strtolower($appToken);
    $get('event', StatusCode::NotAuthorized);
    $_SERVER['HTTP_APP_BEARER'] = $appToken;
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.20';
    $check($get('event')['subscriptionsOpen'] === true, 'Subscriptions should be open.');
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    $usage = $db->execute_query('SELECT ip_address FROM api_usage WHERE token_id = ?', [$tokenId])->fetch_row();
    $check($usage === ['192.0.2.20'], 'API token activity must use the MVC client address.');
    $check($get('event')['storage'] === ['allowedSources' => ['app:///' => ['type' => 'built-in']], 'preferred' => 'app:///'], 'The empty mount list must prefer app storage.');
    $check($get('leaderboard')[0]['rankPosition'] === 1, 'Parish rankings must use integer ranks.');
    $check($get('tournaments')[0]['matchesWithResults'] === 2, 'Scores and forfeits must be counted once per match.');
    $check(count($get('tournament_standings?tournament_id=1')) === 2, 'Tournament standings must include both teams.');
    $check($get('event')['fields'][0]['longitude'] === 12, 'Field coordinates must match the schedule.');
    $matches = $get('matches?team_id=2&tournament_id=1');
    $check(count($matches) === 2 && count($matches[0]['scores']) === 2 && $matches[1]['fieldId'] === null && $matches[1]['forfeitWinnerTeamId'] === 2, 'Match results or scheduling data are incorrect.');
    $check($get('matches?team_id=3') === [], 'Match filters must return an empty list when there are no matches.');
    $get('matches?team_id[]=2', StatusCode::ServerError);
    $get('matches?team_id=2.5', StatusCode::BadRequest);
    $get('matches?team_id=2147483648', StatusCode::BadRequest);
    $get('tournament_standings', StatusCode::BadRequest);
    $db->query('UPDATE partite SET data = CURRENT_DATE - INTERVAL 1 DAY WHERE id = 2');
    $check($get('today_matches?parish_id=1') === [$matches[0]], 'Today must include the home parish and exclude earlier matches.');
    $check($get('today_matches?parish_id=2') === [$matches[0]], 'Today must include the away parish and preserve match scores.');
    $check($get('today_matches?parish_id=3') === [], 'An unrelated parish must get an empty list.');
    $get('today_matches', StatusCode::BadRequest);
    $get('today_matches?parish_id[]=1', StatusCode::ServerError);
    foreach (['people', 'save_person', 'subscriptions', 'subscribe', 'my_teams', 'login'] as $path) {
        $get($path, StatusCode::BadRequest);
    }

    $fixtureKey = random_bytes(32);
    $_ENV['VFS_DRIVE_BASE_KEY'] = base64_encode($fixtureKey);
    $_ENV['VFS_DRIVE_FOLDER_ID'] = 'EXAMPLE_FOLDER_ID';
    $files->mountLocal('drive', sys_get_temp_dir(), key: $fixtureKey);
    $storage = $get('event')['storage'];
    $check($storage === [
        'allowedSources' => [
            'app:///' => ['type' => 'built-in'],
            'drive:///' => [
                'type' => 'google-drive',
                'config' => ['folderId' => 'EXAMPLE_FOLDER_ID'],
                'encryptionKey' => base64_encode($fixtureKey),
            ],
        ],
        'preferred' => 'drive:///',
    ], 'Drive configuration must describe both sources and return the shared mount key.');
    $check(System::reference('drive:///documenti/fixture.pdf', 'uri', 'documenti', true) === 'drive:///documenti/fixture.pdf', 'Drive references must be accepted when enabled.');
    try {
        System::reference('app:///documenti/../fixture.pdf', 'uri', 'documenti', false);
        throw new RuntimeException('A traversal reference was accepted.');
    } catch (InvalidArgumentException) {
    }

    $registration = new Iscrizioni($db, $email);
    $other = new Iscrizioni($db, $otherEmail);
    $profile = [
        'firstName' => 'Fixture',
        'lastName' => 'Participant',
        'birthDate' => '2000-04-05',
        'birthPlace' => 'Example City',
        'taxCode' => 'FIXTURE101',
        'documentTypeId' => 1,
        'documentExpiry' => '2030-04-05',
        'documentUri' => 'app:///documenti/fixture.pdf',
    ];
    $person = $registration->savePerson(false, ...$profile);
    $check($person['email'] === $email && $person['hasDocument'] === true, 'New profiles must use the verified email and retain the document reference.');
    $id = $person['id'];
    try {
        $other->savePerson(false, ...$profile);
        throw new RuntimeException('Creating a duplicate tax code was accepted.');
    } catch (DomainException) {
    }
    $ward = $other->savePerson(false, ...array_replace($profile, ['taxCode' => 'FIXTUREWARD']));
    unset($profile['documentUri']);
    $profile['id'] = $id;
    $profile['firstName'] = 'Updated';
    $updated = $registration->savePerson(false, ...$profile);
    $check($updated['hasDocument'] === true, 'Omitting the document URI must retain the reference.');
    $check(!array_key_exists('documentNumber', $registration->people()[0]), 'API profile reads must omit document numbers.');
    try {
        $other->savePerson(false, ...$profile);
        throw new RuntimeException('Another email updated the profile.');
    } catch (DomainException) {
    }

    $subscriptionFields = ['personId' => $id, 'editionId' => 1, 'parishId' => 1, 'shirtSize' => 'M',
        'certificateUri' => 'app:///certificati/fixture.pdf'];
    $subscription = $registration->subscribe(false, ...$subscriptionFields);
    unset($subscriptionFields['certificateUri']);
    $check($registration->subscribe(false, ...$subscriptionFields) === $subscription, 'Retrying a subscription must preserve its ID and certificate.');
    $check($other->subscriptions() === [], 'An unrelated email must not see the subscription.');
    $db->execute_query('INSERT INTO squadre_iscritti (squadra, iscritto) VALUES (2, ?)', [$subscription['id']]);

    $check($registration->people()[0]['firstName'] === 'Updated', 'The updated profile must remain visible.');
    $check((new Sport($db))->teams($email)[0]['teamId'] === 2, 'The team lookup must follow current enrollment membership.');

    $wardRegistration = $other->subscribe(false, ...array_replace($subscriptionFields, ['personId' => $ward['id']]));
    $db->execute_query('UPDATE iscritti SET tutore = ? WHERE id = ?', [$id, $wardRegistration['id']]);
    $check(array_column($registration->people(), 'id') === [$ward['id'], $id], 'A current guardian must be able to discover the linked profile.');
    $check(count($registration->subscriptions()) === 2, 'Current guardian-linked registrations must be readable.');

    $db->query("UPDATE settings SET enabled = 0 WHERE name = 'subscriptions_enabled'");
    $check($registration->subscriptions()[0] === $subscription, 'Closed subscriptions must remain readable.');
    try {
        $registration->subscribe(false, ...$subscriptionFields);
        throw new RuntimeException('A closed subscription was modified.');
    } catch (DomainException) {
    }

    $indexes = [];
    foreach ($db->query('SHOW INDEX FROM web_app_logins')->fetch_all(MYSQLI_ASSOC) as $index) {
        $indexes[$index['Key_name']][(int)$index['Seq_in_index']] = $index['Column_name'];
    }
    $check(array_values($indexes['PRIMARY']) === ['email', 'ip_address', 'logged_at']
        && array_values($indexes['web_app_logins_logged_at']) === ['logged_at']
        && array_values($indexes['web_app_logins_email']) === ['email'],
        'The Web App login table must use the composite key and separate timestamp and email indexes.');

    $check(!Login::record($db, $email, $tokenId, '192.0.2.10'), 'A profile without a Portal admin association must not be an admin.');
    $db->query("INSERT INTO utenti (user_name, password, is_admin) VALUES ('Synthetic admin', 'unused', 1)");
    $adminId = $db->insert_id;
    $db->execute_query('INSERT INTO staffisti (dati_anagrafici, id_utente, parrocchia) VALUES (?, ?, 1)', [$id, $adminId]);
    $check(Login::record($db, $email, $tokenId, '2001:db8::123'), 'An administrator associated through a staff profile must be recognized.');
    $db->execute_query('UPDATE utenti SET is_blocked = 1 WHERE id = ?', [$adminId]);
    $check(!Login::record($db, $email, $tokenId, '192.0.2.11'), 'A blocked administrator must not receive admin status.');
    $check(!Login::record($db, 'unlinked@example.invalid', $tokenId, '192.0.2.12'), 'An unlinked email must not receive admin status.');

    $logins = Login::recent($db, 0, 2);
    $check(count($logins) === 2 && $logins[0]['person_id'] === null && $logins[1]['person_id'] === $id, 'The log must include unlinked emails and resolve matching profiles.');
    $older = Login::recent($db, 2, 2);
    $check($older[0]['ip_address'] === '2001:db8::123' && $older[0]['token_id'] === $tokenId && $older[0]['logged_at'] !== '', 'The log must preserve IPv6, token identity, and timestamp when paging.');

    $router->SetUser(new User($adminId, 'Synthetic admin', time(), admin: true));
    $router->SetDefaultVariable('api_routes_enabled', true);
    $render = static function (string $path) use ($router, $check): string {
        ob_start();
        try {
            $check($router->Dispatch($path) === StatusCode::Ok, 'The administrator page did not render.');
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    };
    $html = $render('/staff/api/logins?page=2');
    $check(str_contains($html, 'Accessi più recenti') && str_contains($html, 'btn btn-outline-primary'),
        'The log pagination must use the primary navigation style.');
    $html = $render('/staff/api/logins');
    $check(str_contains($html, "/staff/staff/edit_anagrafica?id=$id")
        && str_contains($html, 'infobyip.com/ip-2001%3Adb8%3A%3A123.html')
        && str_contains($html, 'unlinked@example.invalid') && !str_contains($html, $appToken),
        'The administrator log must link profiles and IP lookups while keeping token secrets out of the page.');
    $html = $render('/staff/api/admin');
    $check(substr_count($html, 'href="/staff/api/logins"') === 2
        && str_contains($html, 'action="/staff/api/delete_token"')
        && str_contains($html, 'name="token_name"')
        && !str_contains($html, $appToken),
        'The token page must link the log from its header and navbar and retain both token actions.');

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    $_POST = ['email' => $email];
    $postRouter = new Router(pathPrefix: '/staff', applicationInstallationPath: dirname(__DIR__));
    $postRouter->AddController(ApiController::class, '/api');
    $postRouter->SetDbConnection($db);
    $postRouter->SetDefaultVariable('files', $files);
    ob_start();
    try {
        $status = $postRouter->Dispatch('/staff/api/people?email=unlinked@example.invalid');
        $profiles = json_decode(ob_get_contents(), true, flags: JSON_THROW_ON_ERROR);
    } finally {
        ob_end_clean();
    }
    $check($status === StatusCode::Ok && in_array($id, array_column($profiles, 'id'), true),
        'POST must bind form body fields instead of query fields.');
    $_POST = [];

    $check(Token::Delete($db, $tokenId), 'The fixture token could not be revoked.');
    $get('event', StatusCode::NotAuthorized);
    $afterRevocation = Login::recent($db, 0, 1)[0];
    $check($afterRevocation['token_id'] === $tokenId && $afterRevocation['token_name'] === null, 'Revocation must preserve login history.');
} finally {
    $db->query("DROP DATABASE `$schema`");
}
