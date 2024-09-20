<?php

require_once __DIR__ . "/basic_functions.php";
include_once __DIR__ . "/models/anagrafica.php";
include_once __DIR__ . "/models/edizione.php";
include_once __DIR__ . "/models/iscrizione.php";
include_once __DIR__ . "/models/parrocchia.php";
include_once __DIR__ . "/models/sport.php";
include_once __DIR__ . "/models/squadra.php";
include_once __DIR__ . "/models/staff.php";
include_once __DIR__ . "/models/torneo.php";
require_once __DIR__ . "/models/user.php";
require_once __DIR__ . "/models/punteggio_parrocchia.php";

$connection = new mysqli(MYSQL_HOST, MYSQL_USER, empty(MYSQL_PASSWORD) ? null : MYSQL_PASSWORD, MYSQL_DB);
$connection->set_charset("utf8");
if (!$connection)
{
    die(500);
}

function listaMaglie(
    mysqli $connection, 
    bool $group = true, 
    string $table_id = "", 
    bool $editable = true) : string
{
    if (!$connection)
        return "";
    if (empty($table_id))
        $table_id = "id-tabella-" . rand();
    $query = "CALL ListaMaglie(YEAR(CURRENT_DATE), ";
    if ($group)
    {
        $query .= "TRUE";
    } else {
        $query .= "FALSE";
    }
    $query .= ")";
    $result = $connection->query($query);
    if (!$result)
    {
        $connection->next_result();
        return "";
    }
    $str = "";
    $get_name = function($obj) : string {
        if (isset($obj) && isset($obj->name))
            return $obj->name;
        return "";
    };
    $cols = array_map($get_name, $result->fetch_fields());
    $cmp = function($l, $r) : int {
        $column_score = function($str) : int {
            $expected_cols = array(
                0 => "cognome", 
                1 => "nome", 
                2 => "parrocchia", 
                //3 => "colore",
                4 => "taglia", 
                5 => "XS", 
                6 => "S", 
                7 => "M", 
                8 => "L", 
                9 => "XL", 
                10 => "XXL",
                11 => "3XL");
            if ($lower_index = array_search(strtolower($str), $expected_cols))
            {
                return (int)$lower_index;
            }
            if ($upper_index = array_search(strtoupper($str), $expected_cols))
            {
                return (int)$upper_index;
            }
            return -strlen($str) - 1;
        };
        return $column_score($l) - $column_score($r);
    };
    uasort($cols, $cmp);
    $cols_join = join("</th><th>", $cols);
    $str .= "<table id='$table_id' class='default-table center'><thead><tr><th>$cols_join</th></tr></thead><tbody>";

    while ($row = $result->fetch_assoc())
    {
        $str .= "<tr>";
        $data_ref = "";
        foreach ($cols as $col)
        {
            if (!isset($row[$col]))
                continue;
            $elem = $row[$col];
            $str .= "<td";
            if ($editable)
            {
                $str .= " spellcheck='false' contenteditable='true'";
            }
            if (is_numeric($elem))
            {
                //E' un numero
                $str .= "pattern='[0-9]+' data-min='$elem' ";
                if (strlen($data_ref) > 0)
                {
                    $data = htmlspecialchars($data_ref . "|" . $col);
                    $str .= " data-ref='$data'";
                }
            } else {
                $data_ref = $elem;
            }
            $str .= ">" . htmlspecialchars($elem) . "</td>";
        }
        $str .= "</tr>";
    }
    $str .= "</tbody></table>";
    $result->close();
    $connection->next_result();
    return $str;
}
function listaIscrizioni(mysqli $connection, string $table) : string
{
    if (!$connection)
        return "";
    $blank_row = "<tr><td colspan=\"8\"></td></tr>\n";
    $str = "<table id=\"$table\" class=\"default-table center\">\n";
    $str .= "<thead>\n";
    $str .= $blank_row;
    $str .= "<tr><td colspan=\"8\"> CENTRO SPORTIVO ITALIANO </td></tr>\n";
    $str .= $blank_row;
    $str .= 
        "<tr>
        <td colspan=\"2\"> <b>Comitato di:</b> </td>
        <td colspan=\"2\" contenteditable=\"true\"></td>
        <td colspan=\"2\"> <b>Codice Comitato:</b> </td>
        <td colspan=\"2\" contenteditable=\"true\"></td>
        </tr>\n";
    $str .= $blank_row;
    $str .= 
        "<tr>
        <td colspan=\"2\"> <b>Società sportiva:</b> </td>
        <td colspan=\"2\" contenteditable=\"true\">Amichiamoci A.S.D.</td>
        <td colspan=\"2\"> <b>Codice Società:</b> </td>
        <td colspan=\"2\" contenteditable=\"true\"></td>
        </tr>\n";
    $str .= $blank_row;
    $str .= 
        "<tr>
        <td> <b>N°</b> </td>
        <td> <b>COGNOME</b> </td>
        <td> <b>NOME</b> </td>
        <td> <b>SESSO</b> </td>
        <td> <b>NATO IL</b> </td>
        <td> <b>LUOGO NASCITA</b> </td>
        <td> <b>Telefono</b> </td>
        <td> <b>Email</b> </td>
        </tr>\n";
    $str .= "</thead>\n";
    $str .= "<tbody>\n";
    
    $query = "SELECT * FROM iscrizioni_per_csi";

    $result = mysqli_query($connection, $query);

    if ($result)
    {
        $i = 1;
        while ($row = $result->fetch_assoc())
        {
            $cognome = htmlspecialchars($row["cognome"]);
            $nome = htmlspecialchars($row["nome"]);
            $sesso = $row["sesso"];
            $luogo = htmlspecialchars($row["luogo_nascita"]);
            $data = htmlspecialchars($row["data_nascita_americana"]);
            $tel = $row["telefono"];
            if (strlen($tel) > 0)
            {
                $tel = "<a href=\"tel:$tel\">$tel</a>";
            }
            $email = $row["email"];
            if (strlen($email) > 0)
            {
                $email = "<a href=\"mailto:$email\">$email</a>";
            }

            $str .= "<tr><td>$i</td><td>$cognome</td><td>$nome</td><td>$sesso</td>";
            $str .= "<td>$data</td><td>$luogo</td><td>$tel</td><td>$email</td></tr>\n";
            $i++;
        }
    }
    $str .= $blank_row;
    $str .= "<tr><td> <b>Data:</b> </td><td colspan=\"2\">" . date("d/m/Y") . "</td>";
    $str .= "<td colspan=\"2\"> <b>Il Presidente:</b> </td><td colspan=\"3\">________________________</td></tr>";
    $str .= "</tbody>";
    $str .= "</table>";
    return $str;
}

//
//  Altro
//
include_once __DIR__ . "/sport-functions.php";
include_once __DIR__ . "/system-functions.php";
include_once __DIR__ . "/user-functions.php";
include_once __DIR__ . "/staff-functions.php";