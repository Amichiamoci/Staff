<?php

require_once __DIR__ . '/vendor/autoload.php';

// TODO: make inaccessible via http requests

$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/config.php';

use Amichiamoci\Utils\Security;
use Amichiamoci\Models\User;

echo "Testing db connection...\n";
$connection = new \mysqli(
    hostname: $MYSQL_HOST, 
    username: $MYSQL_USER, 
    password: $MYSQL_PASSWORD, 
    database: $MYSQL_DB,
    port: $MYSQL_PORT,
    socket: null,
);

if (!$connection) {
    echo "Could not establish connection with the db\n";
    exit;
}
echo "Connection to host '$MYSQL_HOST:$MYSQL_PORT' successfull!\n";

function rebuild_db(): void {
    global $MYSQL_HOST, $MYSQL_DB, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_PORT;

    echo "Rebuilding db...\n";
    $params = [
        '--database' => $MYSQL_DB,
        '--host' => $MYSQL_HOST,
        '--port' => $MYSQL_PORT,
        '--user' => $MYSQL_USER,
        # '--default-charset-set' => 'uf8mb4'
    ];
    if (!empty($MYSQL_PASSWORD)) {
        $params['--password'] = $MYSQL_PASSWORD;
    }
    $init_file = __DIR__ . '/starting-db.tmp.sql';
    $joined_params = implode(
        separator: ' ', 
        array: array_map(function(string $k, string $v): string {
            return "$k=$v";
        }, array_keys($params), array_values(array: $params))
    );
    $result = passthru(command: "mysql --skip-ssl $joined_params < $init_file");

    if ($result === false) {
        echo "Rebuild process failed!\n";
        return;
    }

    add_first_user();
}

function add_first_user(): void {
    global $connection;
    $admin = Security::LoadEnvironmentOfFromFile(var: 'ADMIN_USERNAME');
    $admin_password = Security::LoadEnvironmentOfFromFile(var: 'ADMIN_PASSWORD');
    
    if (empty($admin) || empty($admin_password)) {
        return;
    }

    echo "Adding admin with username '$admin'";
    $user = User::Create(
        connection: $connection, 
        username: $admin, 
        password: $admin_password, 
        is_admin: true
    );
    if (!isset($user)) {
        echo "Creation of user account failed.";
        return;
    }
    $id = $user->Id;
    echo "User created with id $id";
}

try {
    $res = $connection->query(query: "SELECT * FROM `utenti` LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        throw new \Error(message: 'Exit the block');
    }
} catch (\Throwable) {
    echo "Problems found with the database: rebuilding process will start now...\n";
    rebuild_db();
}
