<?php
function our_hash(string $str):string
{
    $options = [
		'cost' => 8,
	];
	
	return password_hash($str, PASSWORD_BCRYPT, $options);
    //return sha1($str);
}
function our_hash_comparison($password, $hash):bool
{
    return password_verify($password, $hash);
    //return sha1($password) == $hash;
}
/*
if (!our_hash_comparison("password", our_hash("password")))
{
    echo "Internal error happened!";
    die();
}
*/
$user_names_regex = "/[A-Za-z0-9]{6,16}/";
function acc($str):string
{
    if (!isset($str))
        return "";
    $html = htmlentities($str, 0, 'UTF-8');
    if ($html === '') {
        $html = htmlentities(utf8_encode($str), 0, 'UTF-8');
    }
    return $html;
}
function replace_vocals($str):string
{
    $out = $str;
    $out = str_replace("à", "&agrave;", $out);
    $out = str_replace("á", "&aacute;", $out);
    $out = str_replace("À", "&Agrave;", $out);
    $out = str_replace("Á", "&Aacute;", $out);

    $out = str_replace("è", "&egrave;", $out);
    $out = str_replace("é", "&eacute;", $out);
    $out = str_replace("È", "&Egrave;", $out);
    $out = str_replace("É", "&Eacute;", $out);

    $out = str_replace("ì", "&igrave;", $out);
    $out = str_replace("í", "&iacute;", $out);
    $out = str_replace("Ì", "&Igrave;", $out);
    $out = str_replace("Í", "&Iacute;", $out);
    
    $out = str_replace("ò", "&ograve;", $out);
    $out = str_replace("ó", "&oacute;", $out);
    $out = str_replace("Ò", "&Ograve;", $out);
    $out = str_replace("Ó", "&Oacute;", $out);
    
    $out = str_replace("ù", "&ugrave;", $out);
    $out = str_replace("ú", "&uacute;", $out);
    $out = str_replace("Ù", "&Ugrave;", $out);
    $out = str_replace("Ú", "&Uacute;", $out);
    return $out;
}
function accent_to_vocals(string $str):string
{
    $s = $str;

    $s = str_replace(array("à", "á"), "a", $s);
    $s = str_replace(array("è", "é"), "e", $s);
    $s = str_replace(array("ì", "í"), "i", $s);
    $s = str_replace(array("ò", "ó"), "o", $s);
    $s = str_replace(array("ù", "ú"), "u", $s);

    
    $s = str_replace(array("À", "Á"), "A", $s);
    $s = str_replace(array("È", "É"), "E", $s);
    $s = str_replace(array("Ì", "Í"), "I", $s);
    $s = str_replace(array("Ò", "Ó"), "O", $s);
    $s = str_replace(array("Ù", "Ú"), "U", $s);
    return $s;
}
function getMimeType(string $filename):string {
    try {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo)
        {
            throw new Exception("Invalid file");
        }
        $mime = finfo_file($finfo, $filename);
        if (!$mime)
        {
            throw new Exception("Invalid result");
        }
        finfo_close($finfo);
        return $mime;
    } catch (Exception $ex) {
        return "application/octet-stream";
    }
}
function send_email(string $to, string $subject, string $body, $connection = null, bool $hide_output = false):bool
{
    $headers_array = array(
        'From: Amichiamoci <dev@amichiamoci.it>',
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
            sql_sanitize($subject) . "', '" .
            sql_sanitize($sanitized_body) ."')";
        try {
            $result = mysqli_query($connection, $query);
            if ($result) {
                if ($row = $result->fetch_assoc())
                {
                    if (isset($row["id"]))
                    {
                        $id = (int)$row["id"];
                        $add = "\r\n<img src=\"https://www.amichiamoci.it/admin/view_email.php?id=$id\" 
                            width=\"1\" height=\"1\" style=\"width:1px;height:1px;\" loading=\"eager\" />";
                    }
                }
                $result->close();
            }
            mysqli_next_result($connection);
        } catch(Exception $ex) {
            echo acc($ex->getMessage());
        }
    }
    $ret = mail($sanitized_email, $subject, $body . $add, $headers);
    if (!$ret && $connection)
    {
        $query = "UPDATE email
        SET email.ricevuta = FALSE
        WHERE email.id = $id";
        mysqli_query($connection, $query);
    }
    return $ret;
}

function upload_file($file, $allowed_ext, &$future_file_name, &$error): bool
{
    if ($file == null)
        return false;
    $file_name = $file["name"];
    $file_size = $file["size"];

    // Verify file extension
    $ext = pathinfo($file_name, PATHINFO_EXTENSION);

    if (!in_array($ext, $allowed_ext)) {
        $error = "Tipo di file non valido!";
        return false;
    }    
        
    $maxsize = 10 * 1024 * 1024;
          
    if ($file_size > $maxsize) {
        $error = "File troppo grande!";
        return false;
    }            
    
    
    $actual_path = $_SERVER["DOCUMENT_ROOT"] . "$future_file_name.$ext";
    //$actual_path = "/$future_file_name.$ext";
    // Check whether file exists before uploading it
    while (file_exists($actual_path))
    {
        $ext_index = strrpos($actual_path, $ext) - 1;
        $actual_path = substr($actual_path, 0, $ext_index). "_nuovo." . $ext;
    }        
    if (move_uploaded_file($file["tmp_name"], $actual_path))
    {
        $future_file_name = substr($actual_path, strlen($_SERVER["DOCUMENT_ROOT"]));
        return true;
    } 
    $error = "Impossibile uploadare il file!<!--Path: $actual_path -->";
    return false;
}
function server_file_path($db_path) : string
{
    if (!isset($db_path) || is_array($db_path) || str_contains($db_path, "..")) //.. characters colud be used to steal files
        return "";
    $file_path = $_SERVER["DOCUMENT_ROOT"];
    if (str_starts_with($db_path, "/"))
    {
        $db_path = substr($db_path, 1);
    }
    if (!str_starts_with($db_path, "upload/"))
    {
        $file_path .= "upload/";
    } 
    return $file_path . $db_path;
}
function we_have_file($path):bool
{
    return file_exists(server_file_path($path));
}
function get_file_export_url($path)
{
    return "/admin/get_file.php?target=$path";
}
function our_cookie(string $name, $value, $exp):bool
{
    if (!isset($DOMAIN))
    {
        $DOMAIN = "https://www.amichiamoci.it";
    }
    $_domain =  substr($DOMAIN, strlen("https://"));
    /*
    if (str_starts_with($_domain, "www."))
    {
        $_domain = substr($_domain, strlen("www"));//Leave the starting '.'
    }
    */
    return setcookie($name, $value, time() + $exp, "/admin", $_domain, false, true);
}
function our_delete_cookie(string $name):bool
{
    if (!isset($DOMAIN))
    {
        $DOMAIN = "https://www.amichiamoci.it";
    }
    $_domain =  substr($DOMAIN, strlen("https://"));
    /*
    if (str_starts_with($_domain, "www."))
    {
        $_domain = substr($_domain, strlen("www"));//Leave the starting '.'
    }
    */
    return setcookie($name, "", time() - 3600, "/admin", $_domain);
}

function is_viewing_from_app() : bool
{
    return isset($_COOKIE['AppVersion']) && !is_array($_COOKIE['AppVersion']);
}

function sql_sanitize($str):string
{
    if (!isset($str) || is_array($str))
        return "";
    $copy = str_replace("\\", "\\\\", $str);
    $copy = str_replace("'", "\\'", $str);
    //$copy = str_replace("\"", "\\\"", $copy);
    return $copy;
}
function geo_link($lat, $lon, $text = null):string
{
    if (!isset($lat) || !isset($lon) || is_array($lat) || is_array($lon))
        return "";
    if (!isset($text) || is_array($text) || strlen($text) === 0)
        $text = "Apri in Mappe";
    //$link = "geo:0,0?q=$lat,$lon";
    $link = "geo:$lat,$lon";
    return "<a href=\"$link\" title=\"Apri in Mappe\" class=\"link\">$text</a>";
}
function indirizzo_to_maps_link($indirizzo):string
{
    if (!isset($indirizzo)|| is_array($indirizzo))
        return "";
    $display = acc($indirizzo);
    $maps = "https://www.google.com/maps/place/" . str_replace(" ", "+", $indirizzo);
    return "<a class=\"link\" href=\"$maps\" target=\"_blank\" title=\"Apri in Google Maps\">$display</a>";
}
function number_to_whatsapp_link($number):string
{
    if (!isset($number) || is_array($number))
        return "";
    return "<a href=\"https://wa.me/$number\" class=\"link\">$number</a>";
}
function safe_query(&$connection, string $query, bool $log_errors = false)
{
    if (!$connection || empty($query) || strlen($query) == 0)
        return false;
    try {
        return mysqli_query($connection, $query);
    } catch (mysqli_sql_exception $ex) {
        if ($log_errors)
        {
            $message = acc($ex->getMessage());
            echo "<!-- mysqli_sql_exception: $message -->";
        }
    } catch (Exception $ex)
    {
        if ($log_errors)
        {
            $message = acc($ex->getMessage());
            echo "<!-- Exception: $message -->";
        }
    }
    $connection = false;
    return false;
}