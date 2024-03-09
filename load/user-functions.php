<?php

function allUsers(mysqli $connection, bool $admin_priv = false)
{
    if (!$connection)
    {
        return "";
    }
    $query = "SELECT * FROM `users_extended`";
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
    return $str;
}

function getUsersActivity(mysqli $connection) 
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
            $str = htmlspecialchars(datefmt_format($italian_date_format, $start));
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
function emailList(mysqli $connection) 
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
        $sub = htmlspecialchars($row["oggetto"]);
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
function singleEmail(mysqli $connection, int $id) 
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
        $sub = htmlspecialchars($row["oggetto"]);
        $inviata = $row["inviata"];
        $aperta = $row["aperta"];
        $ricevuta = (int)$row["ricevuta"] == 1;
        $testo = $connection->real_escape_string($row["testo"]);
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
        $str .= htmlspecialchars($row["testo"]);
        $str .="\r\n</code>\r\n</pre>";
    }

    $result->close();
    
    mysqli_next_result($connection);
    return $str;
}
function creaMessaggio(mysqli $connection, string $testo, int $user_id)
{
    if (!$testo || $user_id === 0)
        return false;
    $testo_sano = $connection->real_escape_string($testo);
    $query = "CALL CreaMessaggio($user_id, '$testo_sano')";
    $result = (bool)mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return $result;
}