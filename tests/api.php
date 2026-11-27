<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;
use Richie314\SimpleVirtualFS\VirtualFilesystem;

use Amichiamoci\Api\{Iscrizioni, Login, Sport};
use Amichiamoci\Controllers\{ApiController, SportController};
use Amichiamoci\Models\Api\Token;
use Amichiamoci\Models\{Partita, Punteggio, Sport as SportModel, Squadra, Staff, Torneo, User};
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
            return json_decode(ob_get_contents(), true, flags: JSON_THROW_ON_ERROR);
        } finally {
            ob_end_clean();
        }
    };

    $get('event', StatusCode::NotAuthorized);
    $_SERVER['HTTP_APP_BEARER'] = strtolower($appToken);
    $get('event', StatusCode::NotAuthorized);
    $_SERVER['HTTP_APP_BEARER'] = $appToken;
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.20';
    $check($get('leaderboard')[0]['rankPosition'] === 1, 'Parish rankings must use integer ranks.');
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    $usage = $db->execute_query('SELECT ip_address FROM api_usage WHERE token_id = ?', [$tokenId])->fetch_row();
    $check($usage === ['192.0.2.20'], 'API token activity must use the MVC client address.');
    $event = $get('event');
    $check($event['subscriptionsOpen'] === true, 'Subscriptions should be open.');
    $check($event['storage'] === ['sources' => ['app:///' => ['type' => 'built-in']], 'preferred' => 'app:///'], 'The empty mount list must prefer app storage.');
    $check($get('tournaments')[0]['matchesWithResults'] === 2, 'Scores and forfeits must be counted once per match.');
    $standings = $get('tournament_standings?tournament_id=1');
    $check(count($standings) === 2 && array_column($standings, 'matchesRemaining') === [0, 0],
        'Tournament standings must include both teams and treat forfeits as decided.');
    $check($event['fields'][0]['longitude'] === 12, 'Field coordinates must match the schedule.');
    $matches = $get('matches?team_id=2&tournament_id=1');
    $check(count($matches) === 2 && count($matches[0]['scores']) === 2 && $matches[1]['fieldId'] === null && $matches[1]['forfeitWinnerTeamId'] === 2, 'Match results or scheduling data are incorrect.');
    $check($get('matches?team_id=3') === [], 'Match filters must return an empty list when there are no matches.');
    $get('matches?team_id=2.5', StatusCode::BadRequest);
    $get('matches?team_id=2147483648', StatusCode::BadRequest);
    $get('tournament_standings', StatusCode::BadRequest);
    $db->query('UPDATE partite SET data = CURRENT_DATE - INTERVAL 1 DAY WHERE id = 2');
    $check($get('today_matches?parish_id=1') === [$matches[0]], 'Today must include the home parish and exclude earlier matches.');
    $check($get('today_matches?parish_id=2') === [$matches[0]], 'Today must include the away parish and preserve match scores.');
    $check($get('today_matches?parish_id=3') === [], 'An unrelated parish must get an empty list.');
    $get('today_matches', StatusCode::BadRequest);
    $db->query("SET timestamp = UNIX_TIMESTAMP(CONCAT(YEAR(CURRENT_DATE), '-10-10 12:00:00'))");
    $db->query("UPDATE `partite`
        SET `data` = CURRENT_DATE, `orario` = IF(`id` = 1, '11:00:00', '12:00:00')");
    $db->query("INSERT INTO `partite` (`torneo`, `data`, `orario`, `squadra_casa`, `squadra_ospite`)
        VALUES (1, CURRENT_DATE, '10:59:59', 1, 2), (1, CURRENT_DATE, '12:10:00', 1, 2), (1, CURRENT_DATE, '12:10:01', 1, 2)");
    $live = $get('live_matches');
    $check(array_column($live, 'id') === [1, 4] && $live[0]['sportId'] === 1 && $live[0]['sportName'] === 'Fixture sport',
        'The live endpoint must include both window boundaries, exclude forfeits, and identify the sport.');
    $check(array_map(static fn(Partita $match): int => $match->Id, Partita::Live($db)) === [1, 4],
        'The dashboard and API must use the same live window.');
    $db->query('SET timestamp = DEFAULT');

    foreach (['people', 'save_person', 'subscriptions', 'subscribe', 'my_teams', 'login'] as $path) {
        $response = $get($path . '?email=' . rawurlencode($email), StatusCode::BadRequest);
        $check(($response['error'] ?? null) === 'Unsupported method for this route.', "$path must reject GET requests.");
    }

    $fixtureKey = random_bytes(32);
    $_ENV['VFS_DRIVE_BASE_KEY'] = base64_encode($fixtureKey);
    $_ENV['VFS_DRIVE_FOLDER_ID'] = 'EXAMPLE_FOLDER_ID';
    $files->mountLocal('drive', sys_get_temp_dir(), key: $fixtureKey);
    $storage = $get('event')['storage'];
    $check($storage === [
        'sources' => [
            'app:///' => ['type' => 'built-in'],
            'drive:///' => [
                'type' => 'google-drive',
                'config' => ['folderId' => 'EXAMPLE_FOLDER_ID'],
                'encryptionKey' => base64_encode($fixtureKey),
            ],
        ],
        'preferred' => 'app:///',
    ], 'Drive configuration must describe both sources and return the shared mount key.');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
    $postRouter = new Router(pathPrefix: '/staff', applicationInstallationPath: dirname(__DIR__));
    $postRouter->AddController(ApiController::class, '/api');
    $postRouter->SetDbConnection($db);
    $postRouter->SetDefaultVariable('files', $files);
    $post = static function (string $path, array $fields, StatusCode $expected = StatusCode::Ok) use ($postRouter, $check): array {
        $_POST = $fields;
        ob_start();
        try {
            $status = $postRouter->Dispatch('/staff/api/' . $path);
            $check($status === $expected, "Unexpected API status {$status->value} for $path.");
            return json_decode(ob_get_contents(), true, flags: JSON_THROW_ON_ERROR);
        } finally {
            ob_end_clean();
            $_POST = [];
        }
    };
    $_SERVER['REQUEST_METHOD'] = 'GET';

    $profile = [
        'email' => $email,
        'firstName' => 'Fixture',
        'lastName' => 'Participant',
        'birthDate' => '2000-04-05',
        'birthPlace' => 'Example City',
        'taxCode' => 'FIXTURE101',
        'documentTypeId' => 1,
        'documentExpiry' => '2030-04-05',
        'documentUri' => 'drive:///documenti/fixture storage reference',
    ];
    $person = $post('save_person', $profile);
    $id = $person['id'];
    $check($person['email'] === $email && $person['hasDocument'] === true, 'The profile must include its verified email and document status.');
    $storedDocument = $db->execute_query('SELECT `documento`
        FROM `anagrafiche`
        WHERE `id` = ?', [$id])->fetch_row()[0];
    $check($storedDocument === $profile['documentUri'], 'The API must store the supplied document reference as-is.');
    $post('save_person', array_replace($profile, ['email' => $otherEmail]), StatusCode::Forbidden);
    $ward = $post('save_person', array_replace($profile, ['email' => $otherEmail, 'taxCode' => 'FIXTUREWARD', 'lastName' => 'Ward']));

    unset($profile['documentUri']);
    $profile['id'] = $id;
    $profile['firstName'] = 'Updated';
    $updated = $post('save_person', $profile);
    $check($updated['hasDocument'] === true && $updated['firstName'] === 'Updated', 'An update must retain the document reference.');
    $post('save_person', array_replace($profile, ['email' => $otherEmail]), StatusCode::Forbidden);

    $subscriptionFields = [
        'email' => $email, 'personId' => $id, 'editionId' => 1, 'parishId' => 1, 'shirtSize' => 'M',
        'certificateUri' => 'app:///certificati/fixture storage reference',
    ];
    $subscription = $post('subscribe', $subscriptionFields);
    $storedCertificate = $db->execute_query('SELECT `certificato_medico`
        FROM `iscritti`
        WHERE `id` = ?', [$subscription['id']])->fetch_row()[0];
    $check($storedCertificate === $subscriptionFields['certificateUri'], 'The API must store the supplied certificate reference as-is.');
    unset($subscriptionFields['certificateUri']);
    $check($subscription['hasCertificate'] === true && $post('subscribe', $subscriptionFields) === $subscription,
        'Retrying a subscription must preserve its ID and certificate.');

    $registration = new Iscrizioni($db, $email);
    $other = new Iscrizioni($db, $otherEmail);
    $check(!array_key_exists('documentNumber', $registration->people()[0]), 'API profile reads must omit document numbers.');
    $check($other->subscriptions() === [], 'An unrelated email must not see the subscription.');
    $db->execute_query('INSERT INTO `squadre_iscritti` (`squadra`, `iscritto`)
        VALUES (2, ?)', [$subscription['id']]);
    $check((new Sport($db))->teams($email)[0]['teamId'] === 2, 'The team lookup must follow current enrollment membership.');

    $wardRegistration = $post('subscribe', array_replace($subscriptionFields, ['email' => $otherEmail, 'personId' => $ward['id']]));
    $db->execute_query('UPDATE `iscritti`
        SET `tutore` = ?
        WHERE `id` = ?', [$id, $wardRegistration['id']]);
    $check(array_column($registration->people(), 'id') === [$id, $ward['id']], 'A current guardian must be able to discover the linked profile.');
    $check(count($registration->subscriptions()) === 2, 'Current guardian-linked registrations must be readable.');

    $db->query("UPDATE settings SET enabled = 0 WHERE name = 'subscriptions_enabled'");
    $check($registration->subscriptions()[0] === $subscription, 'Closed subscriptions must remain readable.');
    $post('subscribe', $subscriptionFields, StatusCode::Forbidden);

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

    $profiles = $post('people?email=unlinked@example.invalid', ['email' => $email]);
    $check(in_array($id, array_column($profiles, 'id'), true), 'POST must bind form body fields instead of query fields.');

    $router->AddController(SportController::class, '/sport');
    $postRouter->AddController(SportController::class, '/sport');
    $postRouter->SetUser(new User($adminId, 'Synthetic admin', time(), admin: true));
    $writeMatch = static function (string $action, array $fields, StatusCode $expected) use ($postRouter, $check): void {
        $_POST = $fields;
        ob_start();
        try {
            $check($postRouter->Dispatch('/staff/sport/' . $action) === $expected, "Unexpected status for $action.");
        } finally {
            ob_end_clean();
            $_POST = [];
        }
    };

    $matchSport = Partita::ById($db, 1)->getSport();
    $check($matchSport->Area === 'Calcio' && $matchSport->getIcon() === '⚽'
        && SportModel::All($db)[0]->Area === 'Calcio'
        && SportModel::ById($db, 1)?->Area === 'Calcio'
        && Squadra::ById($db, 1)?->Sport->Area === 'Calcio'
        && Torneo::ById($db, 1)?->Sport->Area === 'Calcio',
        'Sport area must be hydrated on sport, team, tournament, and match reads.');
    $board = $render('/staff/sport/match_results?match_id=1');
    $check(str_contains($board, 'id="scoreboard"') && str_contains($board, '⚽'), 'Football scoring must be available even with a customized sport name.');
    $check(!str_contains($board, 'name="winner_team_id"'), 'Forfeit entry must only be available from the match card.');
    $check(str_contains($board, 'id="scoreboard-view"')
        && str_contains($board, 'data-bs-theme="dark"')
        && str_contains($board, '/images/banner.png')
        && !str_contains($board, 'id="portal-header"')
        && substr_count($board, 'data-board-step="1"') === 2
        && !str_contains($board, '+1'),
        'The minimal dark scoreboard must use the two score cards to add one point.');
    $boardDocument = Dom\HTMLDocument::createFromString($board, LIBXML_NOERROR);
    $boardDate = new DateTimeImmutable('now', new DateTimeZone('Europe/Rome'));
    $check($boardDocument->querySelector('#scoreboard header time')?->getAttribute('datetime') === $boardDate->format('Y-m-d')
        && str_contains($boardDocument->querySelector('#scoreboard header')->textContent, 'Fixture sport')
        && str_contains($boardDocument->querySelector('#scoreboard footer')->textContent, Partita::ById($db, 1)->Campo->Nome),
        'The scoreboard must show its sport and current date by the logo and its field in the footer.');
    $staffUser = new User(200, 'Fixture staff user', time());
    $staff = new Staff(1, 'Fixture staff', 1, 'Fixture parish');
    foreach ([$router, $postRouter] as $sportRouter) {
        $sportRouter->SetUser($staffUser);
        $sportRouter->SetDefaultVariable('staff', $staff);
    }

    $db->query('UPDATE `partite` SET `data` = CURRENT_DATE WHERE `id` = 1');
    $db->query('UPDATE `partite` SET `data` = NULL WHERE `id` = 3');
    $db->query('UPDATE `partite` SET `data` = CURRENT_DATE + INTERVAL 1 DAY WHERE `id` = 4');
    $db->query('UPDATE `partite` SET `data` = CURRENT_DATE - INTERVAL 1 DAY WHERE `id` IN (2, 5)');
    $db->query("INSERT INTO `partite` (`torneo`, `data`, `squadra_casa`, `squadra_ospite`)
        VALUES (1, CURRENT_DATE - INTERVAL 2 DAY, 1, 2)");
    $blankScoreMatch = $db->insert_id;
    Partita::PunteggioVuoto($db, $blankScoreMatch);
    $staffCalendar = $render('/staff/sport/tournament?id=1');
    $calendarDocument = Dom\HTMLDocument::createFromString($staffCalendar, LIBXML_NOERROR);
    $todayCard = $calendarDocument->getElementById('match-1');
    $warningByMatch = [
        1 => false,
        2 => false,
        3 => false,
        4 => false,
        5 => true,
        $blankScoreMatch => true,
    ];
    foreach ($warningByMatch as $fixtureMatch => $warn) {
        $fixtureCard = $calendarDocument->getElementById('match-' . $fixtureMatch);
        $check($fixtureCard !== null && $fixtureCard->classList->contains('border-warning') === $warn,
            "Incorrect missing-result warning on match $fixtureMatch.");
    }
    $check($todayCard?->querySelector('header strong + .match-forfeit-trigger') !== null
        && $todayCard->querySelector('header + a.w-100[data-board-open]:not([hidden])')?->textContent !== null
        && trim($todayCard->querySelector('[data-board-open]')->textContent) === 'Segnapunti'
        && $todayCard->querySelector('[data-board-template]') !== null,
        'Today’s match must have a full-width Segnapunti link, preloaded content, and the forfeit action beside the sport.');
    foreach ([3, 4] as $otherMatch) {
        $otherCard = $calendarDocument->getElementById('match-' . $otherMatch);
        $check($otherCard !== null && $otherCard->querySelector('[data-board-open]:not([hidden]), [data-board-template]') === null,
            'Unscheduled and future matches must not show or preload Segnapunti.');
    }
    $check(str_contains($staffCalendar, 'class="form-control match-date-selector"')
        && str_contains($staffCalendar, '/sport/match_results?match_id=1')
        && str_contains($staffCalendar, 'data-board-open')
        && str_contains($staffCalendar, 'aria-controls="match-forfeit-1"')
        && str_contains($staffCalendar, 'id="match-forfeit-1"')
        && str_contains($staffCalendar, 'action="/staff/sport/match_results"')
        && preg_match('/data-single-result\s+disabled/', $staffCalendar)
        && !str_contains($staffCalendar, 'action="/staff/sport/match_delete"'),
        'Staff must have match editing controls while the delete action remains administrator-only.');
    $check(str_contains($render('/staff/sport/match_results?match_id=1'), 'id="scoreboard"'), 'A staff member must be able to operate the scoreboard.');
    $award = ['match_id' => '1', 'winner_team_id' => '2', 'csrf_token' => $_SESSION['account_csrf_token']];
    $schedule = ['match' => '3', 'csrf_token' => $award['csrf_token']];
    $writeMatch('match_date', $schedule + ['date' => '2026-12-01'], StatusCode::Ok);
    $writeMatch('match_time', $schedule + ['time' => '09:00'], StatusCode::Ok);
    $writeMatch('match_field', $schedule + ['field' => '1'], StatusCode::Ok);
    $writeMatch('match_add_score', $schedule, StatusCode::Ok);
    $scoreId = Partita::ById($db, 3)->Punteggi[0]->Id;
    $manualScore = ['score' => (string)$scoreId, 'csrf_token' => $award['csrf_token']];
    $writeMatch('match_edit_score', $manualScore + ['home' => '1', 'guest' => '2'], StatusCode::Ok);
    $writeMatch('match_remove_score', $manualScore, StatusCode::Ok);
    $rescheduled = Partita::ById($db, 3);
    $check($rescheduled->Data === '2026-12-01' && $rescheduled->Orario === '09:00:00'
        && $rescheduled->Campo?->Id === 1 && $rescheduled->Punteggi === [], 'Staff match changes must persist.');
    $writeMatch('match_results', array_replace($award, ['csrf_token' => 'wrong']), StatusCode::BadRequest);
    $writeMatch('match_results', array_replace($award, ['winner_team_id' => '99']), StatusCode::BadRequest);
    $writeMatch('match_results', $award, StatusCode::SeeOther);
    $forfeit = Partita::ById($db, 1);
    $check($forfeit->SquadraVincitriceATavolino()?->Id === 2 && $forfeit->Punteggi === [], 'An awarded forfeit must hide the normal scores.');
    $awardPage = $render('/staff/sport/match_results?match_id=1');
    $check(str_contains($awardPage, 'Partita assegnata a tavolino.') && !str_contains($awardPage, 'id="scoreboard"'), 'The scoreboard URL for a forfeited match must hide score entry.');
    $check(array_column($get('matches'), 'scores', 'id')[1] === [], 'An awarded forfeit must also hide the normal scores in the API.');
    $check(!Punteggio::SalvaTabellone($db, 1, 0, 1, ['3', '1'], ['4', '1'])
        && !Punteggio::Edit($db, 1, '4', '1') && !Punteggio::Delete($db, 1) && Partita::PunteggioVuoto($db, 1) === null,
        'Score writes from stale pages must leave awarded matches untouched.');
    $writeMatch('match_results', array_replace($award, ['winner_team_id' => '0']), StatusCode::SeeOther);
    $check(count(Partita::ById($db, 1)->Punteggi) === 2, 'Revoking the award must restore the original scores.');

    $db->query("INSERT INTO `sport` (`nome`, `area`) VALUES ('Fixture basketball', 'Basket')");
    $db->execute_query("UPDATE `tornei` SET `sport` = ? WHERE `id` = 1", [$db->insert_id]);
    $check(preg_match('/data-single-result\s+disabled/', $render('/staff/sport/tournament?id=1')) === 1,
        'Basketball match cards must disable adding a second result.');

    $db->query("INSERT INTO `sport` (`nome`, `area`) VALUES ('Fixture volleyball', 'Pallavolo')");
    $volleyball = $db->insert_id;
    $db->execute_query("UPDATE `tornei` SET `sport` = ? WHERE `id` = 1", [$volleyball]);
    $volleyballCalendar = $render('/staff/sport/tournament?id=1');
    $check(str_contains($volleyballCalendar, 'match-result-add') && !str_contains($volleyballCalendar, 'data-single-result'),
        'Volleyball match cards must allow multiple results.');
    $writeMatch('match_board_score', [
        'match_id' => '1',
        'score_change' => json_encode(['position' => 1, 'score_id' => 2, 'previous' => ['2', '0'], 'score' => ['2', '25']]),
        'csrf_token' => $award['csrf_token'],
    ], StatusCode::Ok);
    $check(Punteggio::SalvaTabellone($db, 1, 2, null, null, ['1', '0'])
        && Punteggio::SalvaTabellone($db, 1, 2, null, null, ['1', '0'])
        && !Punteggio::SalvaTabellone($db, 1, 2, null, null, ['0', '1'])
        && !Punteggio::SalvaTabellone($db, 1, 2, 2, ['1', '0'], ['2', '0']),
        'Retries must be idempotent and stale writes must fail.');
    $scores = array_map('strval', Partita::ById($db, 1)->Punteggi);
    $check($scores === ['3 - 1', '2 - 25', '1 - 0'], 'Volleyball scoring must preserve all sets in order.');

    $check(Token::Delete($db, $tokenId), 'The fixture token could not be revoked.');
    $get('event', StatusCode::NotAuthorized);
    $afterRevocation = Login::recent($db, 0, 1)[0];
    $check($afterRevocation['token_id'] === $tokenId && $afterRevocation['token_name'] === null, 'Revocation must preserve login history.');
} finally {
    $db->query("DROP DATABASE `$schema`");
}

echo "API checks passed.\n";
