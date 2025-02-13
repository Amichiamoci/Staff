<?php

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
