<?php
namespace Amichiamoci\Utils;

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
    public static function Send(string $to, 
        string $subject, 
        string $body, 
        \mysqli $connection = null, 
        bool $hide_output = false) : bool
    {
        $headers_array = array(
            'From: Amichiamoci <' . EMAIL_SOURCE . '>',
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/html; charset=UTF-8'
        );
        $add = "";
        $id = 0;
        $headers = join(separator: "\r\n", array: $headers_array);
        $sanitized_email = filter_var(value: $to, filter: FILTER_SANITIZE_EMAIL);
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
            $query = "CALL CreateEmail('$sanitized_email', '" . 
                $connection->real_escape_string(string: $subject) . "', '" .
                $connection->real_escape_string(string: $sanitized_body) ."')";
            try {
                $result = $connection->query(query: $query);
                if ($result) {
                    if ($row = $result->fetch_assoc())
                    {
                        if (isset($row["id"]))
                        {
                            $id = (int)$row["id"];
                            $add = "\r\n<img src=\"" . ADMIN_URL . "/view_email.php?id=$id\" 
                                width=\"1\" height=\"1\" style=\"width:1px;height:1px;\" loading=\"eager\" />";
                        }
                    }
                    $result->close();
                }
                $connection->next_result();
            } catch(\Exception $ex) {
                echo htmlspecialchars(string: $ex->getMessage());
            }
        }

        $ret = mail(to: $sanitized_email, subject: $subject, message: $body . $add, additional_headers: $headers);
        
        if (!$ret && $connection)
        {
            $query = "UPDATE email
            SET email.ricevuta = FALSE
            WHERE email.id = $id";
            $connection->query(query: $query);
        }
        return $ret;
    }
}