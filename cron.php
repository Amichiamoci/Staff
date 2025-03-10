<?php

ini_set(option: 'display_errors', value: '1');
ini_set(option: 'display_startup_errors', value: '1');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Link;
use Amichiamoci\Utils\Security;

if (PHP_SAPI !== 'cli')
{
    define(constant_name: 'EOL', value: '<br>');
    echo 'Non cli (' . PHP_SAPI . ') request detected.' . EOL;

    $allow_http = Security::LoadEnvironmentOfFromFile(var: 'CRON_ENABLE_HTTP');
    if (!isset($allow_http) || !((bool)$allow_http))
    {
        echo 'Job not allowed.' . EOL;
        echo 'Activate them via setting the variable CRON_ENABLE_HTTP to 1' . EOL;
        exit;
    }
} else {
    define(constant_name: 'EOL', value: PHP_EOL);
    $_SERVER['HTTP_HOST'] = 'localhost';
}


echo 'CRON job started' . EOL . EOL;

$connection = new \mysqli(
    hostname: $MYSQL_HOST, 
    username: $MYSQL_USER, 
    password: $MYSQL_PASSWORD, 
    database: $MYSQL_DB,
    port: $MYSQL_PORT
);
unset($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB);
if (!$connection) {
    echo 'Connection to db host failed: all operations aborted.' . EOL;
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

    echo "Starting operation '$name'" . EOL;
    if (!file_exists(filename: CRON_LOG_DIR)) {
        mkdir(directory: CRON_LOG_DIR);
    }

    $file_path = CRON_LOG_DIR . DIRECTORY_SEPARATOR . $last_run_file;
    $curr_date = date(format: "Y-m-d");
    $file_just_created = false;
    if (!file_exists(filename: $file_path))
    {
        echo "File '$file_path' not found. Creating now..." . EOL;
        file_put_contents(filename: $file_path, data: $curr_date);
        $file_just_created = true;
    }

    $file_content = file_get_contents(filename: $file_path);
    $file_date = date_create(datetime: date(format: 'Y-m-d', timestamp: strtotime(datetime: $file_content)));
    if (!$file_date) {
        echo 
            "'$file_content' was not recognized as a valid date " . 
            "(required format Y-m-d): operation aborted." . EOL . EOL;
        return;
    }

    $diff = (int)date_diff(baseObject: $file_date, targetObject: new DateTime())->days;
    if ($diff < 1 && !$file_just_created)
    {
        $italian_date = $file_date->format(format: 'd/m/Y');
        echo "Operation skipped (last run on $italian_date: $diff days ago)." . EOL . EOL;
        return;
    }

    try {
        $function();
    } catch (\Throwable $e) {
        echo $e->getMessage() . EOL;
    }

    file_put_contents(filename: $file_path, data: $curr_date);
    echo EOL;
}


function birthday_emails(): void
{
    global $connection;
    if (!$connection) {
        echo 'Connection to db lost' . EOL;
        return;
    }

    $query = "SELECT `nome`, `email` FROM `compleanni_oggi` WHERE `email` IS NOT NULL";
    $result = $connection->query(query: $query);
    if (!$result) {
        echo 'Could not query the db' . EOL;
        return;
    }
    
    $people_to_write = array();
    while ($row = $result->fetch_assoc())
    {
        $people_to_write[] = array(
            'name' => $row["nome"],
            'email' => $row["email"],
        );
    }
    
    $sent_emails = 0;
    $emails_to_send = count(value: $people_to_write);
    
    foreach ($people_to_write as $person)
    {
        $email = $person['email'];
        $subject = "Buon compleanno " . htmlspecialchars(string: $person['name']) . '!';

        ob_start();
        ?>
            <h3>Tanti auguri a te</h3>
            <h3>Tanti auguri a te</h3>
            <h3>Tanti auguri a <?= htmlspecialchars(string: $person['name']) ?></h3>
            <h3>Tanti auguri a te!</h3>

            <p>
                Ciao <?= htmlspecialchars(string: $person['name']) ?>, lo staff di 
                <a href="<?= MAIN_SITE_URL ?>" target="_blank" class="link">Amichiamoci</a>
                ti augura un buon compleanno, passa questo giorno al meglio.
            </p>
        <?php
        $mail_text = ob_get_contents();
        ob_end_clean();

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
            echo "Could not send email to $email." . EOL;
            continue;
        }
        $sent_emails++;
    }

    echo "$sent_emails/$emails_to_send sent emails." . EOL;
}

function matches_emails(): void {
    global $connection;
    if (!$connection) {
        echo 'Connection to db lost' . EOL;
        return;
    }
    
    $query = "SELECT * FROM `chi_gioca_oggi`";
    $result = $connection->query(query: $query);
    if (!$result)
    {
        echo 'Could not query the db' . EOL;
        return;
    }

    $players_and_emails = [];

    while ($row = $result->fetch_assoc())
    {
        $email = $row["email"];
        $name = $row["nome"];
        $certificate_problem = isset($row["necessita_certificato"]) && $row["necessita_certificato"] == "1";

        $tournaments = explode(separator: "|", string: $row["nomi_tornei_sport"]);

        $opponents_names = explode(separator: "|", string: $row["nomi_avversari"]);
        $matches_times = explode(separator: "|", string: $row["orari_partite"]);

        $fields_names = explode(separator: "|", string: $row["nomi_campi"]);
        $fields_addresses = explode(separator: "|", string: $row["indirizzi_campi"]);
        $fields_latitude = explode(separator: "|", string: $row["lat_campi"]);
        $fields_longitude = explode(separator: "|", string: $row["lon_campi"]);

        $matches_count = count(value: $tournaments);

        if (
            $matches_count !== count(value: $opponents_names)  ||
            $matches_count !== count(value: $matches_times)    ||
            $matches_count !== count(value: $fields_names)     ||
            $matches_count !== count(value: $fields_addresses) ||
            $matches_count !== count(value: $fields_latitude)  ||
            $matches_count !== count(value: $fields_longitude) ||
            $matches_count === 0
        ) {
            // Data not aligned
            continue;
        }

        ob_start();
        ?>
            <h2>
                Ciao <?= htmlspecialchars(string: $name) ?>
            </h2>

            <p class="text">
                Ti scriviamo per ricordarti 
                <?= ($matches_count === 1) ? 
                    'della partita che dovrai disputare oggi.' : 
                    "delle $matches_count partite che dovrai disputare oggi." 
                ?>
            </p>
            <br>

            <?php for ($i = 0; $i < $matches_count; $i++) { ?>
                <dl>
                    <dt>Torneo</dt>
                    <dd><em><?= htmlspecialchars(string: $tournaments[$i]) ?></em></dd>

                    <dt>Avversari</dt>
                    <dd><em><?= htmlspecialchars(string: $opponents_names[$i]) ?></em></dd>

                    <?php if (isset($matches_times[$i]) && $matches_times[$i] !== '?') { ?>
                        <dt>Orario</dt>
                        <dd><time><?=htmlspecialchars(string: $matches_times[$i])?></time></dd>
                    <?php } ?>

                    <?php if (isset($fields_names[$i]) && $fields_names[$i] !== '?') { ?>
                        <dt>Luogo</dt>
                        <dd>
                            <?php if (isset($fields_latitude[$i]) && $fields_latitude[$i] !== '?' && isset($fields_longitude[$i]) && $fields_longitude[$i] !== '?') { ?>
                                <a href="<?= Link::Geo(lat: $fields_latitude[$i], lon: $fields_longitude[$i]) ?>"
                                    class="link"
                                    title="Apri in Mappe"
                                >
                                    <?= htmlspecialchars(string: $fields_names[$i]) ?>
                                </a>
                            <?php } ?>
                        </dd>
                    <?php } ?>

                    <?php if (isset($fields_addresses[$i]) && $fields_addresses[$i] !== '?') { ?>
                        <dt>Indirizzo</dt>
                        <dd>
                            <a href="<?= Link::Address2Maps(addr: $fields_addresses[$i])?>"
                                class="link"
                                title="Apri in Google Maps">
                                <?= htmlspecialchars(string: $fields_addresses[$i]) ?>
                            </a>
                        </dd>
                    <?php } ?>
                </dl>
            <?php } ?>

            <?php if ($certificate_problem) { ?>
                <p class="text">
                    Sembra che tu non abbia ancora consegnato il certificato medico sportivo.<br />
                    <strong>NON si può scendere in campo senza!</strong><br />
                    Invialo <strong>tempestivamente</strong> al tuo referente parrocchiale.
                </p>
                <hr>
            <?php } ?>

            <p class="text">
                <strong>In bocca al lupo!</strong>
            </p>
        <?php
        $mail_text = ob_get_contents();
        ob_end_clean();

        if ($matches_count === 1)
        {
            $subject = "Partita di " . SITE_NAME . " oggi";
        } else {
            $subject = "$matches_count partite di " . SITE_NAME . " oggi";
        }
        $players_and_emails[] = array(
            'email' => $email, 
            'subject' => $subject, 
            'content' => $mail_text
        );
    }
    $result->close();

    $emails_sent = 0;
    $email_to_send_total = count(value: $players_and_emails);

    foreach($players_and_emails as $mail_to_send)
    {
        $to = $mail_to_send['email'];

        // For debugging: we don't want to send real emails when testing
        $email_cron_override = Security::LoadEnvironmentOfFromFile(var: 'CRON_CAPTURE_OUTGOING_ADDRESS');
        if (isset($email_cron_override))
        {
            $to = $email_cron_override;
        }

        if (!Email::Send(
            to: $to, 
            subject: $mail_to_send['subject'], 
            body: $mail_to_send['content'], 
            connection: $connection)
        ) {
            echo 'Could not send email to ' . $to . EOL;
        }
        $emails_sent++;
    }

    echo "$emails_sent/$email_to_send_total mails sent." . EOL;
}

$operations = [
    [
        'name' => 'birthdays',
        'function' => 'birthday_emails',
        'last_run_file' => 'birthdays.txt',
    ],
    [
        'name' => 'matches_reminder',
        'function' => 'matches_emails',
        'last_run_file' => 'matches.txt',
    ]
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
        echo EOL . EOL;
    }
}
echo 'Cron ended.' . EOL;
echo 'See sent emails at http://' . DOMAIN . INSTALLATION_PATH . '/emails'. EOL;