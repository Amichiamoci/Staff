<?php
namespace Amichiamoci\Utils;
use PHPMailer\PHPMailer\PHPMailer;

/*
if (!defined(constant_name: 'SITE_NAME')) {
    throw new \Exception(message: 'SITE_NAME was not defined!');
}
*/

class Email
{
    public static function GetByUserName(\mysqli $connection, string $user) : string
    {
        if (!$connection || !isset($user) || empty($user))
        {
            return "";
        }
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
    public static function GetByUserId(\mysqli $connection, int $user) : string
    {
        if (!$connection || !isset($user))
        {
            return "";
        }
        $query = "CALL SelectAssociatedMail($user)";
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
        bool $hide_output = false
    ): bool {
        $sanitized_email = filter_var(value: $to, filter: FILTER_SANITIZE_EMAIL);
        $mail = new PHPMailer();
        $mail->addAddress($sanitized_email);

        $from_addr = Security::LoadEnvironmentOfFromFile(var: 'MAIL_OUTPUT_ADDRESS');
        if (empty($from_addr)) {
            throw new \Exception(
                message: 'The address to use for outgoing emails is null. Set the MAIL_OUTPUT_ADDRESS environment variable.');
        }

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

        $mail->setFrom($from_addr, SITE_NAME);
        $mail->clearReplyTos(); // Disable reply-to
        $mail->Subject = $subject;
        $mail->isSMTP();
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML();

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
            } catch (\Exception $ex) {
                // echo htmlspecialchars(string: $ex->getMessage());
            }
        }

        $mail->Body = self::Render(
            title: $subject, 
            content: $body, 
            id: $id
        );
        $email_sent = $mail->send();
        
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
            'site_url' => MAIN_SITE_URL
        ]);
        ob_start();
        require dirname(path: __DIR__) . "/Views/Shared/Email.php";
        $rendered_content = ob_get_contents();
        ob_end_clean();
        return $rendered_content;
    }
}