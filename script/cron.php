<?php

ini_set(option: 'display_errors', value: '1');
ini_set(option: 'display_startup_errors', value: '1');

require_once dirname(path: __DIR__) . '/config.php';

use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Security;
use Amichiamoci\Models\Cron;
use Amichiamoci\Utils\File;

use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;

if (PHP_SAPI !== 'cli' && 
    !((bool)Security::LoadEnvironmentOfFromFile(var: 'CRON_ENABLE_HTTP', default: 0))
) {
    echo 'Job not allowed.' . PHP_EOL;
    echo 'Activate them by setting the variable CRON_ENABLE_HTTP to 1' . PHP_EOL;
    exit;
}

echo 'CRON job started' . PHP_EOL . PHP_EOL;

if (!$connection)
{
    echo 'Connection to db host failed: all operations aborted.' . PHP_EOL;
    exit;
}

$cron_logger = new Logger(name: 'Cron logger');
$cron_logger->pushHandler(
    handler: new StreamHandler(
        stream: File::getLogDir() . DIRECTORY_SEPARATOR . 'cron.log', 
        level: Level::Info,
    )
);

function run_daily_operation(Cron $defaultCron): void {
    global $connection, $cron_logger;

    $actualCron = Cron::fetchFromDb(
        connection: $connection,
        name: $defaultCron->Name,
    );

    if ($actualCron === null)
    {
        $defaultCron->createInDB(connection: $connection);
        $actualCron = $defaultCron;
    }

    if (!$actualCron->isDue())
    {
        $nextRun = clone $actualCron->LastRun;
        $nextRun->add(interval: $actualCron->Interval);
        $cron_logger->info(message: 
            "Operation '" . $actualCron->Name . "' skipped: " .
            "next run scheduled at " . $nextRun->format(format: 'd/m/Y H:i:s')
        );
        return;
    }
    echo "Running operation '" . $actualCron->Name . "'..." . PHP_EOL;

    $f = $actualCron->FunctionName;
    try {
        $f();
    } catch (\Throwable $e) {
        $cron_logger->error(message: $e->getMessage());
    }

    $actualCron->LastRun = new \DateTime();
    $actualCron->updateLastRunInDb(connection: $connection);
}


function birthday_emails(): void
{
    global $connection, $cron_logger;
    if (!$connection)
    {
        $cron_logger->error(message: 'Connection to db lost');
        return;
    }

    $query = "SELECT `nome`, `email` FROM `compleanni_oggi` WHERE `email` IS NOT NULL";
    $result = $connection->query(query: $query);
    if (!$result)
    {
        $cron_logger->error(message: 'Could not query the db for birthday emails');
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
        $email_cron_override = Security::LoadEnvironmentOfFromFile(
            var: 'CRON_CAPTURE_OUTGOING_ADDRESS',
        );
        if (!empty($email_cron_override))
            $email = $email_cron_override;

        if (!Email::Send(
            to: $email, 
            subject: $subject, 
            body: $mail_text, 
            connection: $connection)
        ) {
            $cron_logger->warning(message: "Could not send birthday email to $email");
            continue;
        }
        $sent_emails++;
    }

    $cron_logger->info(message: "Sent $sent_emails/$emails_to_send birthday emails.");
}


$operations = [
    new Cron(
        name: 'Email compleanni',
        functionName: 'birthday_emails',
        lastRun: new DateTime(datetime: '2025-01-01'),
        interval: new DateInterval(duration: 'P1D'),
    )
];

foreach ($operations as $operation)
{
    try {
        run_daily_operation(defaultCron: $operation);
    } catch (\Throwable $ex) {
        echo "Job interruped by exception: " . $ex->getMessage();
        echo PHP_EOL . PHP_EOL;
    }
}
echo 'Cron ended.' . PHP_EOL;