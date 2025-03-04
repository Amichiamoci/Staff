<?php

ini_set(option: 'display_errors', value: '1');
ini_set(option: 'display_startup_errors', value: '1');

echo 'CRON job started' . PHP_EOL;
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use Amichiamoci\Utils\Email;
use Amichiamoci\Utils\Link;

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

    echo "Starting operation $name" . PHP_EOL;
    if (!file_exists(filename: __DIR__ . CRON_LOG_DIR)) {
        mkdir(directory: __DIR__ . CRON_LOG_DIR);
    }

    $file_path = __DIR__ . CRON_LOG_DIR . DIRECTORY_SEPARATOR . $last_run_file;
    $curr_date = date(format: "d-m-Y");
    $file_created = false;
    if (!file_exists(filename: $file_path)) {
        echo "File '$file_path' not found. Creating now..." . PHP_EOL;
        file_put_contents(filename: $file_path, data: $curr_date);
        $file_created = true;
    }

    $file_content = file_get_contents(filename: $file_path);
    $file_date = date_create(datetime: date(format: 'd-m-Y', timestamp: strtotime(datetime: $file_content)));
    if (!$file_date) {
        echo "'$file_content' was not recognized as a valid date (required format d-m-Y): operation aborted." . PHP_EOL;
        return;
    }
    if (date_diff(baseObject: new DateTime(), targetObject: $file_date)->days < 1 || !$file_created) {
        echo "Operation skipped (last run on " . $file_date->format(format: 'd-m-Y') . ")." .PHP_EOL;
        return;
    }

    try {
        $function();
    } catch (\Throwable $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    file_put_contents(filename: $file_path, data: $curr_date);
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
        $name = htmlspecialchars(string: $person['name']);
        $email = htmlspecialchars(string: $person['email']);
        $mail_text = join(separator: PHP_EOL, array: array(
            "<h3>Tanti auguri a te</h3>",
            "<h3>Tanti auguri a te</h3>",
            "<h3>Tanti auguri a $name</h3>",
            "<h3>Tanti auguri a te!</h3>",
            "<p>Ciao $name, lo staff di <a href=\"" . MAIN_SITE_URL . "\" target=\"_blank\">Amichiamoci</a>",
            "ti augura un buon compleanno, passa questo giorno al meglio.</p>",
            "<br />",
            "<p><small>Ti preghiamo di non rispondere a questa email</small></p>"));
        $subject = "Buon compleanno";


        if (!Email::Send(
            to: $person['email'], 
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

function matches_emails(): void {
    global $connection;
    if (!$connection) {
        echo 'Connection to db lost' . PHP_EOL;
        return;
    }
    
    $query = "SELECT * FROM `chi_gioca_oggi` WHERE `email` IS NOT NULL";
    $result = $connection->query(query: $query);
    if (!$result)
    {
        echo 'Could not query the db' . PHP_EOL;
        return;
    }

    while ($row = $result->fetch_assoc())
    {
        $email = $row["email"];
        $name = htmlspecialchars(string: $row["nome"]);
        $certificate_problem = isset($row["necessita_certificato"]) && $row["necessita_certificato"] == "1";

        $tourneys = explode(separator: "|", string: $row["nomi_tornei_sport"]);

        $opponents_names = explode(separator: "|", string: $row["nomi_avversari"]);
        $matches_times = explode(separator: "|", string: $row["orari_partite"]);

        $fields_names = explode(separator: "|", string: $row["nomi_campi"]);
        $fields_addresses = explode(separator: "|", string: $row["indirizzi_campi"]);
        $fields_latitude = explode(separator: "|", string: $row["lat_campi"]);
        $fields_longitude = explode(separator: "|", string: $row["lon_campi"]);

        $matches_count = count(value: $tourneys);

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

        $content = "<h2>Ciao $name</h2>" . PHP_EOL;

        // Introduction
        $content .= "<p class=\"text\">" . PHP_EOL;
        $content .= "Ti scriviamo per ricordarti ";
        if ($matches_count === 1)
        {
            $content .= "della partita che dovrai disputare oggi.<br />" . PHP_EOL;
            $subject = "Partita di " . SITE_NAME . " oggi";
        } else {
            $content .= "delle $matches_count partite che dovrai disputare oggi.<br />" . PHP_EOL;
            $subject = "$matches_count partite di " . SITE_NAME . " oggi";
        }
        $content .= "</p>" . PHP_EOL;

        // Add each match
        for ($i = 0; $i < $matches_count; $i++)
        {
            $tourney = htmlspecialchars(string: $tourneys[$i]);
            $opponent = htmlspecialchars(string: $opponents_names[$i]);
            $time = htmlspecialchars(string: $matches_times[$i]);
            $field_name = htmlspecialchars(string: $fields_names[$i]);
            $field_lat = $fields_latitude[$i];
            $field_lon = $fields_longitude[$i];
            $field_addr = $fields_addresses[$i];

            $content .= "<dl>" . PHP_EOL;
            $content .= "    <dt>Torneo</dt>" . PHP_EOL;
            $content .= "    <dd><em>$tourney</em></dd>" . PHP_EOL;
            $content .= "    <dt>Avversari</dt>" . PHP_EOL;
            $content .= "    <dd><em>$opponent</em></dd>" . PHP_EOL;
            if (!empty($time) && $time !== "?")
            {
                $content .= "    <dt>Orario</dt>" . PHP_EOL;
                $content .= "    <dd><time>$time</time></dd>" . PHP_EOL;
            }
            if (!empty($field_name) && $field_name !== "?")
            {
                $content .= "    <dt>Luogo</dt>" . PHP_EOL;
                $content .= "    <dd>";
                if (!empty($field_lat) && $field_lat !== "?" && !empty($file_lon) && $field_lon !== "?")
                {
                    $content .= Link::Geo(lat: $field_lat, lon: $file_lon, text: $field_name);
                } else {
                    $content .= "<em>$field_name</em>";
                }
                $content .= "    </dd>" . PHP_EOL;
            }
            if (!empty($field_addr) && $field_addr !== '?')
            {
                $link = Link::Address2Maps(addr: $field_addr);
                $content .= "    <dt>Indirizzo</dt>" . PHP_EOL;
                $content .= "    <dd>$link</dd>" . PHP_EOL;
            }

            $content .= "</dl>" . PHP_EOL;
        }
        
        // Check subscritpion problems
        if ($certificate_problem)
        {
            $content .= "<p class=\"text\">" . PHP_EOL;
            $content .= "    Sembra che tu non abbia ancora consegnato il certificato medico sportivo.<br />" . PHP_EOL;
            $content .= "    <strong>NON si pu&ograve; scendere in campo senza!</strong><br />" . PHP_EOL;
            $content .= "    Invialo <strong>tempestivamente</strong> al tuo referente parrocchiale." . PHP_EOL;
            $content .= "</p>" . PHP_EOL;

            $content .= "<hr />" . PHP_EOL;
        }

        // Last part of the email
        $content .= "<p class=\"text\">" . PHP_EOL;
        $content .= "    <strong>In bocca al lupo!</strong>" . PHP_EOL;
        $content .= "</p>" . PHP_EOL;

        $players_and_emails[] = array(
            'email' => $email, 
            'subject' => $subject, 
            'content' => $content
        );
    }
    $result->close();

    $emails_sent = 0;

    foreach($players_and_emails as $mail_to_send)
    {
        if (!Email::Send(
            to: $mail_to_send['email'], 
            subject: $mail_to_send['subject'], 
            body: $mail_to_send['content'], 
            connection: $connection)
        ) {
            echo 'Could not send email to ' . $mail_to_send['email'] . PHP_EOL;
        }
        $emails_sent++;
    }
    echo "$emails_sent/$matches_count mails sent." . PHP_EOL;
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

foreach ($operations as $operation) {
    run_daily_operation(
        name: $operation['name'], 
        function: $operation['function'], 
        last_run_file: $operation['last_run_file']
    );
}
echo 'Cron ended.' . PHP_EOL;