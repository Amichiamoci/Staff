<?php
$dir = dirname(__DIR__);
require_once "$dir/config.php";
require_once "$dir/cookie.php";
require_once "$dir/file_functions.php";
require_once "$dir/link.php";
require_once "$dir/security.php";
$user_names_regex = "/[A-Za-z0-9]{6,16}/";

function send_email(
    string $to, 
    string $subject, 
    string $body, 
    mysqli $connection = null, 
    bool $hide_output = false):bool
{
    $headers_array = array(
        'From: Amichiamoci <' . EMAIL_SOURCE . '>',
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/html; charset=UTF-8'
        //'MIME-Version: 1.0'
    );
    $add = "";
    $id = 0;
    $headers = join("\r\n", $headers_array);
    $sanitized_email = filter_var($to, FILTER_SANITIZE_EMAIL);
    if ($connection)
    {
        $sanitized_body = $body;
        if ($hide_output)
        {
            $output_regex = '/>[-!$@£%^&*()_+|~={}\[\]:;?,.\/A-Za-z0-9]+<\/output>/i';
            $sanitized_body = preg_replace($output_regex, "> ????<!--RIMOSSO--> </output>", $sanitized_body);
            $code_regex = '/>[-!$@£%^&*()_+|~={}\[\]:;?,.\/A-Za-z0-9]+<\/code>/i';
            $sanitized_body = preg_replace($code_regex, "> ????<!--RIMOSSO--> </code>", $sanitized_body);
        }
        $query = "CALL CreateEmail('$sanitized_email', '" . 
            $connection->real_escape_string($subject) . "', '" .
            $connection->real_escape_string($sanitized_body) ."')";
        try {
            $result = $connection->query($query);
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
            mysqli_next_result($connection);
        } catch(Exception $ex) {
            echo htmlspecialchars($ex->getMessage());
        }
    }
    $ret = mail($sanitized_email, $subject, $body . $add, $headers);
    if (!$ret && $connection)
    {
        $query = "UPDATE email
        SET email.ricevuta = FALSE
        WHERE email.id = $id";
        $connection->query($query);
    }
    return $ret;
}