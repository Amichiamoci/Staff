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
        $sanitized_email = filter_var(value: $to, filter: FILTER_SANITIZE_EMAIL);
        $mail = new PHPMailer();
        $mail->addAddress(address: $sanitized_email);

        $from_addr = Security::LoadEnvironmentOfFromFile(var: 'MAIL_OUTPUT_ADDRESS');
        if (empty($from_addr)) {
            throw new \Exception(
                message: 'The address to use for outgoing emails is null. Set the MAIL_OUTPUT_ADDRESS environment variable.');
        }
        $mail->isSMTP();

        $smtp_host = Security::LoadEnvironmentOfFromFile(var: 'SMTP_HOST');
        $smtp_port = Security::LoadEnvironmentOfFromFile(var: 'SMTP_PORT', default: '25');
        if (!empty($smtp_host)) {
            $mail->Host = $smtp_host;
            $mail->Port = (int)$smtp_port;
            
            $username = Security::LoadEnvironmentOfFromFile(var: 'SMTP_USER');
            $password = Security::LoadEnvironmentOfFromFile(var: 'SMTP_PASSWORD');
            if (!empty($username)) {
                $mail->Username = $username;
                $mail->SMTPAuth = true;
            }
            if (isset($password)) {
                $mail->Password = $password;
            }
        }

        $mail->setFrom(address: $from_addr, name: SITE_NAME, auto: false);
        $mail->clearReplyTos(); // Disable reply-to
        $mail->Subject = $subject;
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML();

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        $id = null;
        if ($connection)
        {
            $sanitized_body = $body;
            if ($hide_output)
            {
                $output_regex = '/>[-!$@£%^&*()_+|~={}\[\]:;?,.\/A-Za-z0-9]+<\/output>/i';
                $sanitized_body = preg_replace(pattern: $output_regex, replacement: "> ????<!--RIMOSSO--> </output>", subject: $sanitized_body);
                
                $code_regex = '/>[-!$@£%^&*()_+|~={}\[\]:;?,.\/A-Za-z0-9]+<\/code>/i';
                $sanitized_body = preg_replace(pattern: $code_regex, replacement: "> ????<!--RIMOSSO--> </code>", subject: $sanitized_body);
                
                $secret_regex = '/&secret=[A-Za-z0-9]+/i';
                $sanitized_body = preg_replace(pattern: $secret_regex, replacement: "&secret=RIMOSSO", subject: $sanitized_body);
            }
            $query = "CALL CreateEmail('" . 
                $connection->real_escape_string(string: $sanitized_email) . "', '" . 
                $connection->real_escape_string(string: $subject) . "', '" .
                $connection->real_escape_string(string: $sanitized_body) ."')";
            try {
                $result = $connection->query(query: $query);
                if ($result && $result->num_rows > 0) {
                    if (($row = $result->fetch_assoc()) && array_key_exists(key: 'id', array: $row))
                    {
                        $id = (int)$row["id"];
                    }
                    $result->close();
                }
                $connection->next_result();
            } catch (\Throwable) {
                // echo htmlspecialchars(string: $ex->getMessage());
            }
        }

        $mail->msgHTML(message: self::Render(
            title: $subject, 
            content: $body, 
            id: $id
        ));

        try {
            $email_sent = $mail->send();
        } catch (\Exception) {
            $email_sent = false;
        }
        
        if (!$email_sent && $connection)
        {
            $query = "UPDATE `email` SET `email`.`ricevuta` = FALSE WHERE `email`.`id` = $id";
            $connection->query(query: $query);
        }
        return $email_sent;
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
            'site_name' => SITE_NAME,
            'site_url' => MAIN_SITE_URL,
            'P' => File::getInstallationPath(),
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
            'P' => File::getInstallationPath(),
        ]);
        ob_start();
        require dirname(path: __DIR__) . "/Views/Email/birthday.php";
        $rendered_content = ob_get_contents();
        ob_end_clean();
        return $rendered_content;
    }
}