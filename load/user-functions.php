<?php
function logUserOut($id)
{
    if ($id == null || (int)$id == 0)
        return false;
    our_delete_cookie("user_id");
    return true;
}
class AnagraficaResult
{
    public string $nome = "";
    public int $id = 0;
    public string $username = "";
    public int $staff_id = 0;
    public bool $is_admin = false;
    public int $session = 0;
    public function label():string
    {
        if ($this->nome != "")
            return $this->nome;
        return $this->username;
    }
}
function isUserLogged($connection, int $id, $user_agent, $user_ip, AnagraficaResult $obj_anagrafica):bool
{
    if ($id == null || $user_agent == null || $user_ip == null)
    {
        return false;
    }

    $user_flag = sha1($user_agent . $user_ip);
    $find_session_query = "CALL IsUserLogged($id, '$user_flag')";
    $result = mysqli_query($connection, $find_session_query);
    if (!$result)
    {
        mysqli_next_result($connection);
        return false;
    }
    $row = $result->fetch_assoc();
    if (!$row || !isset($row["id"]))
    {
        $result->close();
        mysqli_next_result($connection);
        return false;
    }
    $obj_anagrafica->session = (int)$row["session_id"];
    $obj_anagrafica->username = $row["user_name"];
    $is_admin = $row["is_admin"];
    $obj_anagrafica->is_admin = $is_admin == 1 || $is_admin == "1";
    $result->close();
    mysqli_next_result($connection);

    $query2 = "CALL UpdateSession($obj_anagrafica->session)";
    $result2 = (bool)mysqli_query($connection, $query2);
    if (!$result2)
    {
        mysqli_next_result($connection);
        return false;
    }
    mysqli_next_result($connection);

    $get_anagrafica_query = "CALL GetStaffFromUserId($id)";
    $result3 = mysqli_query($connection, $get_anagrafica_query);
    if ($result3)
    {
        if ($anagrafica = $result3->fetch_assoc())
        {
            $obj_anagrafica->nome = $anagrafica["nome"];
            $obj_anagrafica->id = (int)$anagrafica["id_anagrafica"];
            $obj_anagrafica->staff_id = (int)$anagrafica["staffista"];
        }
        $result3->close();
    }
    mysqli_next_result($connection);
    return true;
}
function logUserIn($connection, $username, $password, $user_agent, $user_ip):bool
{
    if ($username == null || $password == null || $user_agent == null || $user_ip == null)
    {
        return false;
    }
    $user_flag = sha1($user_agent . $user_ip);
    $query = "CALL GetUserPassword('$username');";
    $result = mysqli_query($connection, $query);
    $ret = false;
    $id = 0;
    if (!$result)
    {
        mysqli_next_result($connection);
        return false;
    }
    if ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $hash = $row["password"];
        $ret = $id != 0 && our_hash_comparison($password, $hash);
        if ($ret)
        {
            our_cookie("user_id", $id, 3600 * 24 * 14);
        }
    }
    $result->close();
    mysqli_next_result($connection);
    if ($ret)
    {
        $query2 = "CALL StartSession($id, '$user_flag', '$user_ip');";
        $result2 = mysqli_query($connection, $query2);
        if (!$result2)
        {
            $ret = false;
        } else {
            if ($row2 = $result2->fetch_assoc())
            {
                $ret = isset($row2["session_id"]) && (int)$row2["session_id"] != 0;
            }
            $result2->close();
        }
        mysqli_next_result($connection);
    }
    return $ret;
}
function changeUserPassword($connection, $id, $password, $new_password):bool
{
    if ($connection == null || !isset($password) || !isset($new_password))
    {
        return false;
    }
    $query = "CALL GetUserPasswordFromId($id)";
    $result = mysqli_query($connection, $query);
    $ret = false;
    if (!$result)
    {
        mysqli_next_result($connection);
        return false;
    }
    if ($row = $result->fetch_assoc())
    {
        $hash = $row["password"];
        $ret = our_hash_comparison($password, $hash);
    }
    $result->close();
    mysqli_next_result($connection);
    if (!$ret)
    {
        return false;
    }
    $new_hash = our_hash($new_password);
    $query2 = "CALL SetUserPassword($id, '$new_hash')";
    $result2 = mysqli_query($connection, $query2);
    $ret = (bool)$result2;
    mysqli_next_result($connection);
    return $ret;
}
function changeUserName($connection, $id, $password, $new_username):bool
{
    if ($connection == null || !isset($password) || !isset($new_username))
    {
        return false;
    }
    $query = "CALL GetUserPasswordFromId($id)";
    $result = mysqli_query($connection, $query);
    $ret = false;
    if (!$result)
    {
        mysqli_next_result($connection);
        return false;
    }
    if ($row = $result->fetch_assoc())
    {
        $hash = $row["password"];
        $ret = our_hash_comparison($password, $hash);
    }
    $result->close();
    mysqli_next_result($connection);
    if (!$ret)
    {
        return false;
    }
    $query2 = "CALL SetUserName($id, '$new_username')";
    $result2 = mysqli_query($connection, $query2);
    if ($result2)
    {
        if ($row2 = $result2->fetch_assoc())
        {
            $ret =isset($row2["result"]) && (int)$row2["result"] != 0;
        }
        $result2->close();
    }
    mysqli_next_result($connection);
    return $ret;
}
function createUser($connection, $username, $hashed_password, bool $is_admin):int
{
    if (!$connection)
    {
        return 0;
    }
    $query = "INSERT INTO utenti(user_name, password, is_admin) VALUES ('$username', '$hashed_password', ";
    if ($is_admin) {
        $query .= "1";
    } else {
        $query .= "0";
    }
    $query .= ")";
    if (!mysqli_query($connection, $query))
        return 0;
    $result = mysqli_query($connection, "SELECT LAST_INSERT_ID() AS id");
    if ($result)
    {
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
            {
                return (int)$row["id"];
            }
        }
    }
    return 0;
}
function allUsers($connection, bool $admin_priv = false)
{
    if (!$connection)
    {
        return "";
    }
    $query = "CALL AllUsers()";
    $result = mysqli_query($connection, $query);
    $str = "<div class='tables flex wrap'>";
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $username = $row["user_name"];
            $id = (int)$row["id"];
            $is_admin = $row["is_admin"] == "1" || $row["is_admin"] == 1;
            $is_blocked = $row["is_blocked"] == "1" || $row["is_blocked"] == 1;
            $last_seen = $row["last_seen"];
            $str .= "<table>";
            $str .= "<thead>";
            $str .= "<tr><th data-label='User Name'>$username</th></tr>";
            $str .= "</thead>";
            $str .= "<tbody><tr>";
            $str .= "<td data-label='ID'>$id</td>";
            if ($is_admin)
            {  
                $str .= "<td data-label='Admin'>S&igrave;</td>";
            }
            if ($admin_priv)
            {
                $str .= "<td data-label='Elimina'><a href='javascript:DeleteUser($id)'>Elimina $username</a></td>";
                $str .= "<td data-label='Resetta'><a href='javascript:ResetUser($id)'>Cambia la password di $username</a></td>";
                if ($is_blocked)
                {
                    $str .= "<td data-label='Utente Bloccato'><a href='javascript:RestoreUser($id)'>Annulla il ban</a></td>";
                } else {
                    $str .= "<td data-label='Utente Abilitato'><a href='javascript:BlockUser($id)'>Blocca $username</a></td>";
                }
            }
            $str .= "<td data-label='Status'>$last_seen</td>";
            $str .= "</tr></tbody>";
            $str .= "</table>";
            
        }
        $result->close();
        $str .= "<script>";
        $str .= "function DeleteUser(id) {";
        $str .= "   if (!confirm('Sei sicuro di voler eliminare l\'utente?'))";
        $str .= "       return;";
        $str .= "   post('delete-user.php', { delete_user: 'yes', target_id: id });";
        $str .= "};";
        $str .= "function ResetUser(id) {";
        $str .= "   if (!confirm('Sei sicuro di voler resettare la password all\'utente?'))";
        $str .= "       return;";
        $str .= "   post('reset-user.php', { reset_user: 'yes', target_id: id });";
        $str .= "}";
        $str .= "function BlockUser(id) {";
        $str .= "   if (!confirm('Sei sicuro di voler disconnettere l\'utente da tutti i suoi dispositivi?'))";
        $str .= "       return;";
        $str .= "   post('log-away-user.php', { block_user: 'yes', target_id: id });";
        $str .= "}";
        $str .= "function RestoreUser(id) {";
        $str .= "   if (!confirm('Sei sicuro di voler riabilitare l\'utente?'))";
        $str .= "       return;";
        $str .= "   post('log-away-user.php', { restore_user: 'yes', target_id: id });";
        $str .= "}";
        $str .= "</script>";
    }
    $str .= "</div>";
    mysqli_next_result($connection);
    return $str;
}
function banUser($connection, $id)
{
    if (!$connection)
        return false;
    $target = (int)$id;
    if ($target == 0)
        return false;
    $query = "CALL BanUser($target)";
    $result = (bool)mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return $result;
}
function restoreUser($connection, $id)
{
    if (!$connection)
        return false;
    $target = (int)$id;
    if ($target == 0)
        return false;
    $query = "CALL RestoreUser($target)";
    $result = (bool)mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return $result;
}
function generatePassword()
{
    $alphabet = str_split("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!?/@;*+-$%&=^_");
    $password = "";
    for ($i = 0; $i < 10; $i++)
    {
        $password .= $alphabet[rand(0, count($alphabet) - 1)];
    }
    return $password;
}
function resetUserPassword($connection, int $target = 0)
{
    if (!$connection || $target == 0)
        return "";
    $new_password = generatePassword();
    $hashed = our_hash($new_password);
    $result = mysqli_query($connection, "CALL SetUserPassword($target, '$hashed')");
    if (!$result)
    {
        $new_password = "";
    }
    mysqli_next_result($connection);
    if ($new_password != "")
    {
        $email = getAssociatedMailById($connection, $target);
        if (strlen($email) == 0)
            return $new_password;
        if (send_email($email, "Cambio password", 
            "Ciao, &egrave; appena stata cambiata la tua password.<br>\n" .
            "<span style=\"user-select: none;\">Da adesso per accedere utilizza: </span>" . 
            "<output style=\"font-family: monospace\">$new_password</output><br>\n" .
            "<hr>\n".
            "Ti preghiamo di non rispondere a questa email.<br>" . 
            "Buona giornata", 
            $connection, true))
            return "Inviata per email a <a href=\"mailto:$email\" class=\"link\">$email</a>";
        return "Errore durante invio mail";
    }
    return $new_password;
}
function deleteUser($connection, int $target = 0)
{
    if (!$connection || $target == 0)
        return "";
    $ret = "";
    try {
        
        $result = mysqli_query($connection, "CALL DeleteUser($target)");
        if ($result)
        {
            $ret = (string)mysqli_affected_rows($connection);
        }
        mysqli_next_result($connection);
    } catch (Exception $ex) {

    }
    return $ret;
}
function getUserIP():string {
    $ipaddress = "127.0.0.1";
    if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $ipaddress;
}
function getAssociatedMail($connection, string $user)
{
    if (!$connection || $user == 0)
    {
        return "";
    }
    $query = "CALL GetAssociatedMailByUserName('$user')";
    $result = mysqli_query($connection, $query);
    $return = "";
    if ($result)
    {
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["email"]))
            {
                $result = $row["email"];
            }
            if (isset($row["id"]))
            {
                $id = $row["id"];
                $result .= ",$id";
            }
        }
        $result->close();
    }
    mysqli_next_result($connection);
    return $return;
}
function getAssociatedMailById($connection, int $user)
{
    if (!$connection || $user == 0)
    {
        return "";
    }
    $query = "CALL SelectAssociatedMail($user)";
    $result = mysqli_query($connection, $query);
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
    mysqli_next_result($connection);
    return $return;
}

function getUsersActivity($connection) 
{
    $query  = "CALL UsersActivity(NULL);";

    $result = mysqli_query($connection, $query);

    if ($result)
    {

        $table  = "<table><tbody>";
        $italian_date_format = new IntlDateFormatter(
            'it_IT',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Rome',
            IntlDateFormatter::GREGORIAN,
            'EEE, dd/MM/yy HH:mm:ss'
        );

        while ($row = $result->fetch_assoc())
        {
            $user = $row["user_name"];
            $start = new DateTime($row["time_start"]);
            $end = new DateTime($row["time_log"]);
            $flag = $row["user_flag"];
            $ip = $row["device_ip"];
            
            $duration = date_diff($end, $start);

            $table .= "<tr>";
            $table .= "<th data-label='Utente'>$user</th>";
            $str = acc(datefmt_format($italian_date_format, $start));
            $table .= "<td data-label='Orario'>$str</td>";
            $table .= "<td data-label='Durata'>$duration->i minuti</td>";
            $table .= "<td data-label='Flag'>$flag</td>";
            if (isset($ip))
            {
                $table .= "<td data-label='Indirizzo IP'><a href='https://www.infobyip.com/ip-$ip.html' target='_blank'>$ip</a></td>";
            }
            $table .= "</tr>";
            
        }

        $table .= "</tbody>";
        $table .= "</table>";
        $result->close();
    }
    else
    {
        $table = "&Egrave; avvenuto un errore.";
    }
    
    mysqli_next_result($connection);
    return $table;
}
function emailList($connection) 
{
    $query  = "CALL ListEmail();";
    if (!$connection)
    {
        return "";
    }
    $result = mysqli_query($connection, $query);

    if (!$result)
    {
        return "";
    }
    
    $table  = "<table><tbody>";

    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $dest = $row["destinatario"];
        $sub = acc($row["oggetto"]);
        $inviata = $row["inviata"];
        $aperta = $row["aperta"];
        $ricevuta = (int)$row["ricevuta"] == 1;

        $table .= "<tr>";
        $table .= "<th data-label='ID'><a href=\"./see_email.php?id=$id\" class=\"link\" title=\"Vedi contenuto\">#$id</a></th>";
        $table .= "<th data-label='Destinatario'><a href=\"mailto:$dest\" class=\"link\">$dest</a></th>";
        $table .= "<td data-label='Oggetto'>$sub</td>";
        $table .= "<td data-label='Invio'>$inviata</td>";
        $table .= "<td data-label='Aperta'>$aperta</td>";
        if (!$ricevuta)
        {
            $table .= "<td data-label='Errore'>Email non consegnata!</td>";
        }
        $table .= "</tr>";
        
    }

    $table .= "</tbody>";
    $table .= "</table>";
    $result->close();
    
    mysqli_next_result($connection);
    return $table;
}
function singleEmail($connection, int $id) 
{
    $query  = "CALL ViewEmail($id)";
    if (!$connection)
    {
        return "";
    }
    $result = mysqli_query($connection, $query);

    if (!$result)
    {
        return "";
    }
    
    $str  = "";

    if ($row = $result->fetch_assoc())
    {
        $dest = $row["destinatario"];
        $sub = acc($row["oggetto"]);
        $inviata = $row["inviata"];
        $aperta = $row["aperta"];
        $ricevuta = (int)$row["ricevuta"] == 1;
        $testo = sql_sanitize($row["testo"]);
        $testo = str_replace(array("\n", "\r"), "", $testo);
        $str .= "<h2>Email #$id</h2>";
        $str .= "<h4>Destinatario:&nbsp;<a href=\"mailto:$dest\" class=\"link\">$dest</a></h4>";
        $str .= "<h4>Oggetto:&nbsp;$sub</h4>";
        $str .= "<p>$inviata</p>";
        $str .= "<p>$aperta</p>";
        if (!$ricevuta)
        {
            $str .= "<p><strong>Email non consegnata!</strong></p>";
        }
        //Prevent XSS
        $str .= "<h4>Contentuo email:</h4>";
        $str .= "<iframe style='border: 2px solid var(--cl-main);width: 100%;height: auto;min-height: 50vh;' id='iframe'></iframe>";
        $str .= "<script type=\"text/javascript\">\r\n";
        $str .= "const iframe = document.getElementById('iframe');\r\n";
        $str .= "const iframeDoc = iframe.contentDocument;\r\n";
        $str .= "iframe.setAttribute('sandbox', 'allow-forms');\r\n";
        $str .= "iframeDoc.open(); iframeDoc.write('$testo'); iframeDoc.close();\r\n";
        $str .= "</script>";

        $str .= "<h4>HTML sorgente email:</h4>";
        $str .= "<pre style='width: 100%;height: fit-content;'>\r\n<code style='width: 100%;display: block;overflow-x: auto'>\r\n";
        $str .= acc($row["testo"]);
        $str .="\r\n</code>\r\n</pre>";
    }

    $result->close();
    
    mysqli_next_result($connection);
    return $str;
}
function creaMessaggio($connection, string $testo, int $user_id)
{
    if (!$testo || $user_id === 0)
        return false;
    $testo_sano = sql_sanitize($testo);
    $query = "CALL CreaMessaggio($user_id, '$testo_sano')";
    $result = (bool)mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return $result;
}