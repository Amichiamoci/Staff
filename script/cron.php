<?php

ini_set(option: 'display_errors', value: '1');
ini_set(option: 'display_startup_errors', value: '1');

require_once dirname(path: __DIR__) . '/vendor/autoload.php';
require_once dirname(path: __DIR__) . '/config.php';

use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Security;

if (PHP_SAPI !== 'cli' && 
    !((bool)Security::LoadEnvironmentOfFromFile(var: 'CRON_ENABLE_HTTP', default: 0))
) {
    echo 'Job not allowed.' . PHP_EOL;
    echo 'Activate them by setting the variable CRON_ENABLE_HTTP to 1' . PHP_EOL;
    exit;
}

echo 'CRON job started' . PHP_EOL . PHP_EOL;

$connection = new \mysqli(
    hostname: $MYSQL_HOST, 
    username: $MYSQL_USER, 
    password: $MYSQL_PASSWORD, 
    database: $MYSQL_DB,
    port: $MYSQL_PORT
);
unset($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);
if (!$connection) {
    echo 'Connection to db host failed: all operations aborted.' . PHP_EOL;
    exit;
}

function run_daily_operation(
    string $name,
    callable $function,
    string $last_run_file,
): void {
    if (empty($name) || empty($last_run_file)) {
        throw new \Exception(message: "Invalid paramters");
    }

    echo "Starting operation '$name'" . PHP_EOL;
    if (!file_exists(filename: CRON_LOG_DIR)) {
        mkdir(directory: CRON_LOG_DIR);
    }

    $file_path = CRON_LOG_DIR . DIRECTORY_SEPARATOR . $last_run_file;
    $curr_date = date(format: "Y-m-d");
    $file_just_created = false;
    if (!file_exists(filename: $file_path))
    {
        echo "File '$file_path' not found. Creating now..." . PHP_EOL;
        file_put_contents(filename: $file_path, data: $curr_date);
        $file_just_created = true;
    }

    $file_content = file_get_contents(filename: $file_path);
    $file_date = date_create(datetime: date(format: 'Y-m-d', timestamp: strtotime(datetime: $file_content)));
    if (!$file_date) {
        echo 
            "'$file_content' was not recognized as a valid date " . 
            "(required format Y-m-d): operation aborted." . PHP_EOL . PHP_EOL;
        return;
    }

    $diff = (int)date_diff(baseObject: $file_date, targetObject: new DateTime())->days;
    if ($diff < 1 && !$file_just_created)
    {
        $italian_date = $file_date->format(format: 'd/m/Y');
        echo "Operation skipped (last run on $italian_date: $diff days ago)." . PHP_EOL . PHP_EOL;
        return;
    }

    try {
        $function();
    } catch (\Throwable $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    file_put_contents(filename: $file_path, data: $curr_date);
    echo PHP_EOL;
}


function birthday_emails(): void
{
    global $connection;
    if (!$connection) {
        echo 'Connection to db lost' . PHP_EOL;
        return;
    }

    $query = "SELECT `nome`, `email` FROM `compleanni_oggi` WHERE `email` IS NOT NULL";
    $result = $connection->query(query: $query);
    if (!$result) {
        echo 'Could not query the db' . PHP_EOL;
        return;
    }
    
    $people_to_write = [];
    while ($row = $result->fetch_assoc())
    {
        $people_to_write[] = [
            'name' => $row["nome"],
            'email' => $row["email"],
        ];
    }
    
    $sent_emails = 0;
    $emails_to_send = count(value: $people_to_write);
    
    foreach ($people_to_write as $person)
    {
        $email = $person['email'];
        $subject = "Buon compleanno " . htmlspecialchars(string: $person['name']) . '!';

        $mail_text = Email::Birthday(name: $person['name']);

        // For debugging: we don't want to send real emails when testing
        $email_cron_override = Security::LoadEnvironmentOfFromFile(var: 'CRON_CAPTURE_OUTGOING_ADDRESS');
        if (isset($email_cron_override))
        {
            $email = $email_cron_override;
        }

        if (!Email::Send(
            to: $email, 
            subject: $subject, 
            body: $mail_text, 
            connection: $connection)
        ) {
            echo "Could not send email to $email." . PHP_EOL;
            continue;
        }
        $sent_emails++;
    }

    echo "$sent_emails/$emails_to_send sent emails." . PHP_EOL;
}


$operations = [
    [
        'name' => 'birthdays',
        'function' => 'birthday_emails',
        'last_run_file' => 'birthdays.txt',
    ],
];

foreach ($operations as $operation)
{
    try {
        run_daily_operation(
            name: $operation['name'], 
            function: $operation['function'], 
            last_run_file: $operation['last_run_file']
        );
    } catch (\Throwable $ex) {
        echo "Job interruped by exception: " . $ex->getMessage();
        echo PHP_EOL . PHP_EOL;
    }
}
echo 'Cron ended.' . PHP_EOL;
echo 'See sent emails at http://' . DOMAIN . INSTALLATION_PATH . '/emails'. PHP_EOL;