<?php

require_once dirname(path: __DIR__) . '/config.php';

use Amichiamoci\Utils\Security;
use Amichiamoci\Utils\Database;
use Amichiamoci\Models\User;

echo "Testing db connection..." . PHP_EOL;
try {
    $connection = new \mysqli(
        hostname: $MYSQL_HOST, 
        username: $MYSQL_USER, 
        password: $MYSQL_PASSWORD, 
        database: $MYSQL_DB,
        port: $MYSQL_PORT
    );
} catch (\Throwable) {
    $connection = false;
}

if (!$connection) {
    echo "⚠️ Could not establish connection with the db" . PHP_EOL;
    exit(1);
}
echo "✅ Connection to host '$MYSQL_HOST:$MYSQL_PORT' successfull!" . PHP_EOL;

$init_file = dirname(path: __DIR__) . '/starting-db.tmp.sql';

function rebuild_db(): void {
    global $MYSQL_HOST, $MYSQL_DB, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_PORT, $init_file;

    echo "Rebuilding db..." . PHP_EOL;

    $schema = Database::GetSchema();
    file_put_contents(filename: $init_file, data: $schema);

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
    $joined_params = implode(
        separator: ' ', 
        array: array_map(function(string $k, string $v): string {
            return "$k=$v";
        }, array_keys($params), array_values(array: $params))
    );
    $result = passthru(command: "mariadb --skip-ssl $joined_params < $init_file");

    if ($result === false) {
        echo "⚠️ Rebuild process failed!" . PHP_EOL;
        exit(1);
    }

    add_first_user();

    unlink(filename: $init_file);
    echo "✅ Rebuild process completed successfully." . PHP_EOL;
}

function add_first_user(): void {
    global $connection;
    $admin = Security::LoadEnvironmentOfFromFile(var: 'ADMIN_USERNAME');
    $admin_password = Security::LoadEnvironmentOfFromFile(var: 'ADMIN_PASSWORD');
    
    if (empty($admin) || empty($admin_password)) {
        return;
    }

    echo "Adding admin with username '$admin'" . PHP_EOL;
    $user = User::Create(
        connection: $connection, 
        username: $admin, 
        password: $admin_password, 
        is_admin: true
    );
    if (!isset($user)) {
        echo "⚠️ Creation of user account failed." . PHP_EOL;
        exit(1);
    }
    $id = $user->Id;
    echo "User created with id $id" . PHP_EOL;
}

try {
    $res = $connection->query(query: "SELECT * FROM `utenti` LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        throw new \Error(message: 'Exit the block');
    }
} catch (\Throwable) {
    echo "Problems found with the database: rebuilding process will start now..." . PHP_EOL;
    rebuild_db();
}
