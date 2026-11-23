<?php
namespace Amichiamoci\Utils;

use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    public static function GetByUserName(
        \mysqli $connection, 
        string $user,
    ): string
    {
        if (!$connection || empty($user))
            return "";

        $query = "CALL GetAssociatedMailByUserName('" . $connection->real_escape_string(string: $user) . "')";
        $result = $connection->query(query: $query);
        $return = "";
        if ($result)
        {
            if ($row = $result->fetch_assoc())
            {
                if (isset($row["email"]))
                {
                    $return = $row["email"];
                }
                if (isset($row["id"]))
                {
                    $id = $row["id"];
                    $return .= ",$id";
                }
            }
            $result->close();
        }
        $connection->next_result();
        return $return;
    }

    public static function GetByUserId(
        \mysqli $connection, 
        int $user,
    ): string
    {
        if (!$connection || $user <= 0)
            return "";

        $query = "CALL SelectAssociatedMail($user)";
        $result = $connection->query(query: $query);
        $return = "";
        if ($result)
        {
            if ($row = $result->fetch_assoc())
            {
                if (isset($row["email"]))
                    $return = $row["email"];
            }
            $result->close();
        }
        $connection->next_result();
        return $return;
    }

    public static function Send(
        string $to, 
        string $subject, 
        string $body, 
        ?\mysqli $connection = null, 
        bool $hide_output = false,
    ): bool {
        $to = trim($to);
        if (!PHPMailer::validateAddress($to)) {
            return false;
        }
        $mail = new PHPMailer();
        $mail->addAddress(address: $to);

        $from_addr = Security::LoadEnvironmentOrFromFile(var: 'MAIL_OUTPUT_ADDRESS');
        if (empty($from_addr)) {
            throw new \Exception(
                message: 'The address to use for outgoing emails is null. Set the MAIL_OUTPUT_ADDRESS environment variable.');
        }
        $mail->isSMTP();

        $smtp_host = Security::LoadEnvironmentOrFromFile(var: 'SMTP_HOST');
        $smtp_port = Security::LoadEnvironmentOrFromFile(var: 'SMTP_PORT', default: '25');
        if (!empty($smtp_host)) {
            $mail->Host = $smtp_host;
            $mail->Port = (int)$smtp_port;
            
            $username = Security::LoadEnvironmentOrFromFile(var: 'SMTP_USER');
            $password = Security::LoadEnvironmentOrFromFile(var: 'SMTP_PASSWORD');
            if (!empty($username)) {
                $mail->Username = $username;
                $mail->SMTPAuth = true;
            }
            if (isset($password)) {
                $mail->Password = $password;
            }
        }

        if (!$mail->setFrom(address: $from_addr, name: SITE_NAME, auto: false)) {
            return false;
        }
        $mail->clearReplyTos(); // Disable reply-to
        $mail->Subject = $subject;
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML();

        if ((int)$mail->Port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($mail->SMTPAuth) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $id = null;
        if ($connection)
        {
            $storedBody = $hide_output ? '[Contenuto riservato]' : $body;
            try {
                $result = $connection->execute_query('CALL CreateEmail(?, ?, ?)', [$to, $subject, $storedBody]);
                if ($result instanceof \mysqli_result) {
                    $row = $result->fetch_assoc();
                    $id = isset($row['id']) ? (int)$row['id'] : null;
                    $result->close();
                }
                $connection->next_result();
            } catch (\mysqli_sql_exception) {
                error_log('Unable to record outgoing staff email.');
            }
        }

        $mail->Body = self::Render(title: $subject, content: $body, id: $id);
        $mail->AltBody = $mail->html2text($body);

        try {
            $email_sent = $mail->send();
        } catch (\Exception) {
            $email_sent = false;
        }
        
        if (!$email_sent && $connection && $id !== null)
        {
            $query = "UPDATE `email` SET `email`.`ricevuta` = FALSE WHERE `email`.`id` = $id";
            $connection->query(query: $query);
        }
        return $email_sent;
    }

    public static function OpenSignature(int $id): ?string
    {
        $key = Security::LoadEnvironmentOrFromFile(var: 'MAIL_OPEN_SIGNING_KEY');
        if ($key === null || strlen($key) < 32) {
            return null;
        }

        $database = Security::LoadEnvironmentOrFromFile(var: 'MYSQL_DB');
        return hash_hmac('sha256', "staff-email-open:$database:$id", $key);
    }

    public static function Render(
        string $title,
        string $content,
        ?int $id = null,
    ): string {
        extract(array: [
            'title' => $title,
            'content' => $content,
            'email_id' => $id,
            'open_signature' => $id === null ? null : self::OpenSignature($id),
            'site_name' => SITE_NAME,
            'site_url' => MAIN_SITE_URL,
            'P' => Link::InstallationPath(),
        ]);
        ob_start();
        require dirname(path: __DIR__) . "/Views/Shared/Email.php";
        $rendered_content = ob_get_contents();
        ob_end_clean();
        return $rendered_content;
    }

    public static function Birthday(
        string $name,
    ): string {
        extract(array: [
            'name' => $name,
            'site_name' => SITE_NAME,
            'site_url' => MAIN_SITE_URL,
            'P' => Link::InstallationPath(),
        ]);
        ob_start();
        require dirname(path: __DIR__) . "/Views/Email/birthday.php";
        $rendered_content = ob_get_contents();
        ob_end_clean();
        return $rendered_content;
    }
}