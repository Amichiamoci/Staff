<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Richie314\SimpleMvc\Http\StatusCode;
use Richie314\SimpleMvc\Routers\Router;
use Richie314\SimpleVirtualFS\VirtualFilesystem;

use Amichiamoci\Controllers\FileController;
use Amichiamoci\Models\{Staff, User};

define('SITE_NAME', 'Staff fixture');
define('MAIN_SITE_URL', 'https://example.invalid');
define('POWERED_BY', 'https://example.invalid');
$_SERVER['REQUEST_METHOD'] = 'GET';

$db = new mysqli('127.0.0.1', 'root', '', 'fixture', (int)($argv[1] ?? 0));
$db->query("CREATE TEMPORARY TABLE anagrafiche_espanse AS
    SELECT 11 AS id, 'Sample' AS nome, 'Person' AS cognome, 25 AS eta, 'SYNTHETIC' AS cf,
    '01/01/2000' AS data_nascita, 'Fixture' AS luogo_nascita, 1 AS tipo_documento,
    'drive:///document.pdf' AS documento");
$db->query('CREATE TEMPORARY TABLE edizioni (id INT, anno INT)');
$db->query('CREATE TEMPORARY TABLE iscritti (id INT, dati_anagrafici INT, edizione INT, certificato_medico VARCHAR(255))');
$db->query('INSERT INTO edizioni VALUES (1, YEAR(CURRENT_DATE))');
$db->query("INSERT INTO iscritti VALUES (91, 11, 1, 'drive:///certificate.pdf'), (92, 11, 1, NULL)");
$db->query("CREATE PROCEDURE SingolaIscrizione(IN enrollment INT)
    SELECT i.id AS id_iscrizione, 'Sample' AS nome, 'Person' AS cognome, 1 AS id_parrocchia,
    'Fixture' AS parrocchia, e.anno, 'M' AS maglia, NULL AS id_tutore, i.certificato_medico
    FROM iscritti i JOIN edizioni e ON e.id = i.edizione WHERE i.id = enrollment");

$directory = sys_get_temp_dir() . '/staff-access-' . bin2hex(random_bytes(12));
mkdir($directory, 0700);
try {
    $files = new VirtualFilesystem();
    $files->mountLocal('drive', $directory, key: random_bytes(32));
    foreach (['document', 'certificate'] as $name) {
        $uri = "drive:///$name.pdf";
        $files->write($uri, "$name fixture");
    }

    $router = new Router(applicationInstallationPath: dirname(__DIR__));
    $router->AddController(FileController::class, '/file');
    $router->SetDbConnection($db);
    $router->SetDefaultVariable('files', $files);

    $user = new User(101, 'User fixture', time());
    $user->IdStaff = 1;
    $router->SetUser($user);
    $enrolled = new Staff(1, 'Staff fixture', 1, 'Fixture', taglia: 'M', codice_fiscale: 'SYNTHETIC');
    $otherParish = new Staff(1, 'Staff fixture', 2, 'Other', taglia: 'M', codice_fiscale: 'SYNTHETIC');
    $otherAccount = new Staff(2, 'Other staff', 1, 'Fixture', taglia: 'M', codice_fiscale: 'SYNTHETIC');
    $incomplete = new Staff(1, 'Staff fixture', 1, 'Fixture');

    $check = static function (string $path, StatusCode $expected, ?string $body = null) use ($router): void {
        ob_start();
        $status = $router->Dispatch($path);
        $response = ob_get_clean();
        if ($status !== $expected || ($body !== null && $response !== $body)) {
            throw new RuntimeException("Unexpected response for $path: {$status->name}");
        }
    };

    foreach ([$incomplete, $otherParish, $otherAccount, null] as $staff) {
        $router->SetDefaultVariable('staff', $staff);
        $check('/file/certificate?id=91', StatusCode::NotFound);
    }
    $router->SetDefaultVariable('staff', $enrolled);
    $check('/file/certificate?id=91', StatusCode::Ok, 'certificate fixture');
    $check('/file/document?id=11', StatusCode::NotFound);

    $router->SetUser(new User(103, 'Admin fixture', time(), admin: true));
    $router->SetDefaultVariable('staff', null);
    $check('/file/certificate?id=91', StatusCode::Ok, 'certificate fixture');
    $check('/file/certificate?id=92', StatusCode::NotFound);
    $check('/file/document?id=11', StatusCode::Ok, 'document fixture');
    $check('/file/document?id=999', StatusCode::NotFound);
    $check('/file/download?name=drive%3A%2F%2F%2Fdocument.pdf', StatusCode::Ok, 'document fixture');
} finally {
    foreach (glob($directory . '/*') as $file) {
        unlink($file);
    }
    rmdir($directory);
    $db->query('DROP PROCEDURE SingolaIscrizione');
}

echo "File authorization checks passed.\n";
