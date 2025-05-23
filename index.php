<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Routes.php';

use Amichiamoci\Models\User;
use Amichiamoci\Models\Staff;

set_error_handler(callback: function(\Throwable|int $ex, ?string $msg = null): void {
    if ($ex instanceof \Throwable) {
    ?>
    <pre class="text-error"><?= htmlspecialchars(string: $ex->getMessage())?></pre>
    <pre class="text-warning"><?= htmlspecialchars(string: $ex->getTraceAsString())?></pre>
    <?php } else { ?>
        <span class="user-select-none font-monospace text-error">
            <strong> Errore 0x<?= dechex(num: $ex) ?> </strong> (<?= $ex ?>)
        </span>
        <?php if (isset($msg)) { ?>
            <code class="text-error">
                <?= htmlspecialchars(string: $msg) ?>
            </code>
        <?php } ?>
    <?php }
});
set_exception_handler(callback: function(\Throwable $ex): void {
    ?>
    <pre><?= htmlspecialchars(string: $ex->getMessage())?></pre>
    <pre><?= htmlspecialchars(string: $ex->getTraceAsString())?></pre>
    <?php
});

$uri = $_SERVER['REQUEST_URI'];

if (!isset($router))
{
    throw new \Exception(message: 'Could not find the router instance!');
}

// Establish db connection
$connection = new \mysqli(
    hostname: $MYSQL_HOST, 
    username: $MYSQL_USER, 
    password: $MYSQL_PASSWORD, 
    database: $MYSQL_DB,
    port: $MYSQL_PORT
);
$router->SetDbConnection(connection: $connection);

// Delete variables once used for security
unset($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);

// Load user data
$user = User::LoadFromSession();
if (isset($user))
{
    $router->SetUser(user: $user);
    $user->UpdateLogTs();
    $user->PutLogTsInSession();
    $user->UploadDbLog(connection: $connection);

    if (!$user->HasAdditionalData())
    {
        if ($user->LoadAdditionalData(connection: $connection))
        {
            $user->PutAdditionalInSession();
        }
    }

    // Load staff data
    if ($user->HasAdditionalData() && isset($user->IdStaff))
    {
        $staff = Staff::ById(connection: $connection, id: $user->IdStaff);
        if (isset($staff))
        {
            $router->SetStaff(staff: $staff);
        }
    }
}

$router->dispatch(uri: $uri);