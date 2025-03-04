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


//
//  Altro
//
include_once __DIR__ . "/sport-functions.php";
include_once __DIR__ . "/system-functions.php";
include_once __DIR__ . "/user-functions.php";
include_once __DIR__ . "/staff-functions.php";