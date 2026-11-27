<?php

declare(strict_types=1);

use Amichiamoci\Utils\Database;

// ====================================================================
// Command-line setup
// ====================================================================

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$database = $argv[1] ?? 'amichiamoci';
if ($argc > 2 || !preg_match('/^[A-Za-z0-9_][A-Za-z0-9_-]{0,63}$/D', $database)) {
    fwrite(STDERR, "Usage: php script/mock-database.php [database-name] > /tmp/portale-mock.sql\n");
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';

// ====================================================================
// Tax code generation
// ====================================================================

/** Computes a codice fiscale for the synthetic ASCII names and Italian birthplaces below. */
function codiceFiscale(string $first, string $last, DateTimeImmutable $birth, bool $female, string $placeCode): string
{
    $nameCode = static function (string $name, bool $isFirstName): string {
        $name = strtoupper($name);
        $consonants = preg_replace('/[AEIOU]/', '', $name);
        $vowels = preg_replace('/[^AEIOU]/', '', $name);
        if ($isFirstName && strlen($consonants) >= 4) {
            return $consonants[0] . $consonants[2] . $consonants[3];
        }
        return substr($consonants . $vowels . 'XXX', 0, 3);
    };

    $monthCode = 'ABCDEHLMPRST'[(int)$birth->format('n') - 1];
    $dayCode = sprintf('%02d', (int)$birth->format('j') + ($female ? 40 : 0));
    $body = $nameCode($last, false) . $nameCode($first, true)
        . $birth->format('y') . $monthCode . $dayCode . $placeCode;

    $oddValues = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];
    $sum = 0;
    foreach (str_split($body) as $index => $character) {
        $value = ctype_digit($character) ? (int)$character : ord($character) - ord('A');
        $sum += $index % 2 === 0 ? $oddValues[$value] : $value;
    }
    return $body . chr(ord('A') + $sum % 26);
}

// ====================================================================
// SQL insert formatting
// ====================================================================

/** Builds inserts from fixed schema identifiers and fixture values. */
function insert(string $table, array $row): string
{
    $columns = array_map(static fn(string $column): string => "`$column`", array_keys($row));
    $values = array_map(static fn(null|int|string $value): string => match (true) {
        $value === null => 'NULL',
        is_int($value) => (string)$value,
        default => "CONVERT(X'" . bin2hex($value) . "' USING utf8mb4)",
    }, $row);
    return 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
}

// ====================================================================
// Database setup and current edition
// ====================================================================

$now = new DateTimeImmutable('now', new DateTimeZone('Europe/Rome'));
$today = $now->setTime(0, 0);
$year = (int)$now->format('Y');
$statements = [
    "SET time_zone = '" . $now->format('P') . "';",
    'START TRANSACTION;',
    "UPDATE `edizioni` SET `anno` = $year, `motto` = 'Edizione di prova' WHERE `id` = 1;",
    "UPDATE `parrocchie` SET `nome` = 'Parrocchia1' WHERE `id` = 1;",
    "UPDATE `settings` SET `enabled` = TRUE WHERE `name` = 'tournaments_enabled';",
];

// ====================================================================
// Parishes
// ====================================================================

for ($parish = 2; $parish <= 12; $parish++) {
    $statements[] = insert('parrocchie', ['id' => $parish, 'nome' => "Parrocchia$parish"]);
}

// ====================================================================
// Test accounts and credentials
// ====================================================================

$accounts = [
    ['mock_admin', 'admin attivo'],
    ['mock_staff', 'staff attivo'],
    ['mock_staff_incompleto', 'staff senza partecipazione'],
    ['mock_bloccato', 'staff bloccato'],
    ['mock_utente', 'utente senza profilo staff'],
];
$credentials = [];
foreach ($accounts as $index => [$username, $status]) {
    $id = $index + 1;
    $password = bin2hex(random_bytes(12));
    $statements[] = insert('utenti', [
        'id' => $id,
        'user_name' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'is_admin' => (int)($id === 1),
        'is_blocked' => (int)($id === 4),
    ]);
    $credentials[] = "$username\t$password\t$status";
}

// ====================================================================
// Synthetic profiles and staff
// ====================================================================

$firstNames = ['Maria', 'Giovanni', 'Marta', 'Pietro', 'Elisabetta', 'Matteo'];
$surnames = ['Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi'];
$birthplaces = [['Livorno', 'E625'], ['Pisa', 'G702'], ['Cecina', 'C415']];
$shirts = ['S', 'M', 'L', 'XL'];
for ($id = 1; $id <= 41; $id++) {
    $first = $firstNames[($id - 1) % count($firstNames)];
    $last = $surnames[($id - 1) % count($surnames)];
    [$city, $placeCode] = $birthplaces[($id - 1) % count($birthplaces)];
    $birth = $today->modify($id <= 5 ? '-32 years' : '-18 years')->modify('-' . ($id - 1) . ' days');
    $email = $id % 7 === 0 ? null : "profilo$id@example.invalid";
    $statements[] = insert('anagrafiche', [
        'id' => $id,
        'nome' => $first,
        'cognome' => $last,
        'data_nascita' => $birth->format('Y-m-d'),
        'luogo_nascita' => $city,
        'email' => $email,
        'codice_fiscale' => codiceFiscale($first, $last, $birth, $id % 2 === 1, $placeCode),
        'tipo_documento' => 1,
        'documento' => '',
    ]);
    if ($id <= 4) {
        $statements[] = insert('staffisti', ['id' => $id, 'dati_anagrafici' => $id, 'id_utente' => $id, 'parrocchia' => $id]);
        if ($id !== 3) {
            $statements[] = insert('partecipaz_staff_ediz', ['staff' => $id, 'edizione' => 1, 'maglia' => $shirts[$id - 1]]);
        }
    }
}

// ====================================================================
// Staff roles
// ====================================================================

$statements[] = insert('commissioni', ['id' => 1, 'nome' => 'Tornei']);
$statements[] = insert('ruoli_staff', ['commissione' => 1, 'staffista' => 2, 'edizione' => 1]);

// ====================================================================
// Sports, teams and match fixtures
// ====================================================================

foreach ([1 => 'Calcio', 2 => 'Pallavolo', 3 => 'Basket'] as $sport => $label) {
    $statements[] = insert('sport', ['id' => $sport, 'nome' => $label, 'area' => $label]);
    $statements[] = insert('tornei', ['id' => $sport, 'edizione' => 1, 'nome' => "Torneo $label", 'sport' => $sport, 'tipo_torneo' => 1]);
    $statements[] = insert('campi', ['id' => $sport, 'nome' => "Campo $label", 'indirizzo' => 'Indirizzo di prova']);

    // ----------------------------------------------------------------
    // Teams and registrations
    // ----------------------------------------------------------------

    $firstTeam = ($sport - 1) * 4 + 1;
    for ($team = $firstTeam; $team < $firstTeam + 4; $team++) {
        $statements[] = insert('squadre', ['id' => $team, 'nome' => "Parrocchia$team $label", 'parrocchia' => $team, 'sport' => $sport, 'edizione' => 1]);
        $statements[] = insert('partecipaz_squad_torneo', ['torneo' => $sport, 'squadra' => $team]);
        $firstMember = 6 + ($team - 1) * 3;
        for ($member = $firstMember; $member < $firstMember + 3; $member++) {
            $statements[] = insert('iscritti', [
                'id' => $member,
                'dati_anagrafici' => $member,
                'edizione' => 1,
                'parrocchia' => $team,
                'taglia_maglietta' => $shirts[$member % count($shirts)],
            ]);
            $statements[] = insert('squadre_iscritti', ['squadra' => $team, 'iscritto' => $member]);
        }
    }

    // ----------------------------------------------------------------
    // Match schedule and scores
    // ----------------------------------------------------------------

    $played = match ($label) {
        'Calcio' => [['2', '1']],
        'Pallavolo' => [['25', '19'], ['21', '25'], ['15', '12']],
        'Basket' => [['61', '58']],
    };
    $fixtures = [
        [$now, 0, 1, [], false],
        [$today->setTime(15, 30), 2, 3, [], false],
        [$today->setTime(17, 0), 0, 2, [], true],
        [$today->modify('-1 day')->setTime(18, 30), 1, 3, $played, false],
        [$today->modify('+1 day')->setTime(16, 30), 0, 3, [], false],
    ];
    foreach ($fixtures as $index => [$kickoff, $home, $away, $scores, $forfeit]) {
        $id = ($sport - 1) * count($fixtures) + $index + 1;
        $statements[] = insert('partite', [
            'id' => $id,
            'torneo' => $sport,
            'data' => $kickoff->format('Y-m-d'),
            'orario' => $kickoff->format('H:i:s'),
            'campo' => $sport,
            'squadra_casa' => $firstTeam + $home,
            'squadra_ospite' => $firstTeam + $away,
            'a_tavolino' => $forfeit ? $firstTeam + $home : null,
        ]);
        foreach ($scores as [$homeScore, $awayScore]) {
            $statements[] = insert('punteggi', ['partita' => $id, 'home' => $homeScore, 'guest' => $awayScore]);
        }
    }
}

// ====================================================================
// SQL output and login details
// ====================================================================

echo "CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;\nUSE `$database`;\n";
echo Database::GetSchema(), "\n", implode("\n", $statements), "\nCOMMIT;\n";
fwrite(STDERR, "Database: $database\nUsername\tPassword\tStato\n" . implode("\n", $credentials) . "\n");
