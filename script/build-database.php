<?php

require_once dirname(path: __DIR__) . '/config.php';

use Amichiamoci\Utils\{Security, Database};
use Amichiamoci\Models\User;

echo "Testing db connection..." . PHP_EOL;

if (!$connection) {
    echo "⚠️ Could not establish connection with the db" . PHP_EOL;
    exit(1);
}

echo "✅ Connection to host '$connection->host_info' successfull!" . PHP_EOL;

$init_file = dirname(path: __DIR__) . '/starting-db.tmp.sql';

function rebuild_db(): void {
    global $init_file;

    $host =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_HOST', default: 'localhost');
    $port = (int)Security::LoadEnvironmentOrFromFile(var: 'MYSQL_PORT', default: '3306');
    $user =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_USER', default: 'amichiamoci');
    $pass =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_PASSWORD');
    $name =      Security::LoadEnvironmentOrFromFile(var: 'MYSQL_DB');

    echo "Rebuilding db..." . PHP_EOL;

    $schema = Database::GetSchema();
    file_put_contents(filename: $init_file, data: $schema);

    $params = [
        '--database' => $name,
        '--host' => $host,
        '--port' => $port,
        '--user' => $user,
        # '--default-charset-set' => 'uf8mb4'
    ];
    if (!empty($pass)) {
        $params['--password'] = $pass;
    }
    $joined_params = implode(
        separator: ' ', 
        array: array_map(function(string $k, string $v): string {
            return "$k=$v";
        }, array_keys($params), array_values(array: $params))
    );
    unset($host, $port, $user, $pass, $name);
    
    $result = passthru(command: "mariadb --skip-ssl $joined_params < $init_file");
    unset($joined_params);

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

    $admin =          Security::LoadEnvironmentOrFromFile(var: 'ADMIN_USERNAME');
    $admin_password = Security::LoadEnvironmentOrFromFile(var: 'ADMIN_PASSWORD');
    
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
