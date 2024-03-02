<?php
//Do not add the ./ in the paths
require_once 'config.php';
require_once 'basic_functions.php';
include "staff-functions.php";
function listaMaglie($connection, bool $group = true, string $table_id = "", bool $editable = true):string
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
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
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
                    $data = acc($data_ref . "|" . $col);
                    $str .= " data-ref='$data'";
                }
            } else {
                $data_ref = $elem;
            }
            $str .= ">" . acc($elem) . "</td>";
        }
        $str .= "</tr>";
    }
    $str .= "</tbody></table>";
    $result->close();
    mysqli_next_result($connection);
    return $str;
}
function listaIscrizioni($connection, string $table):string
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
        <td colspan=\"2\"> <b>Societ&agrave; sportiva:</b> </td>
        <td colspan=\"2\" contenteditable=\"true\">Amichiamoci A.S.D.</td>
        <td colspan=\"2\"> <b>Codice Societ&agrave;:</b> </td>
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
            $cognome = acc($row["cognome"]);
            $nome = acc($row["nome"]);
            $sesso = $row["sesso"];
            $luogo = acc($row["luogo_nascita"]);
            $data = acc($row["data_nascita"]);
            $tel = $row["telefono"];
            if (strlen($tel) > 0)
            {
                $tel = "<a href= \"tel:$tel\">$tel</a>";
            }
            $email = $row["email"];
            if (strlen($email) > 0)
            {
                $email = "<a href= \"mailto:$email\">$email</a>";
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
class rawedizione
{
    public int $id = 0;
    public int $year = 0;
    public $motto = "";
    public string $imgpath = "";
    public function ok():bool
    {
        return $this->id != 0;
    }
}
function tutteLeEdizioni($connection)
{
    $query = "SELECT id, anno, motto, path_immagine FROM edizioni ORDER BY anno DESC";
    $ret = array();
    $result = mysqli_query($connection, $query);
    if ($result)
    {
        while($row = $result->fetch_assoc())
        {
            $edizione = new rawedizione();
            if (isset($row["id"]))
            {
                $edizione->id = (int) $row["id"];
            }
            if (isset($row["anno"]))
            {
                $edizione->year = (int) $row["anno"];
            }
            if (isset($row["motto"]))
            {
                $edizione->motto = $row["motto"];
            }
            if (isset($row["path_immagine"]))
            {
                $edizione->imgpath = $row["path_immagine"];
            }
            $ret[] = $edizione;
        }
    }
    return $ret;
}

function edizioneFromId($connection, int $id)
{
    $query = "SELECT id, anno, motto, path_immagine FROM edizioni WHERE id = $id";
    $result = mysqli_query($connection, $query);
    $edizione = new rawedizione();
    if ($result)
    {
        while($row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
            {
                $edizione->id = (int) $row["id"];
            }
            if (isset($row["anno"]))
            {
                $edizione->year = (int) $row["anno"];
            }
            if (isset($row["motto"]))
            {
                $edizione->motto = $row["motto"];
            }
            if (isset($row["path_immagine"]))
            {
                $edizione->imgpath = $row["path_immagine"];
            }
        }
    }
    return $edizione;
}
function getCurrentEdition($connection)
{
    $query = "SELECT * FROM edizioni WHERE anno = YEAR(CURRENT_DATE)";
    $result = mysqli_query($connection, $query);
    $edizione = new rawedizione();
    if ($result)
    {
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
            {
                $edizione->id = (int) $row["id"];
            }
            if (isset($row["anno"]))
            {
                $edizione->year = (int) $row["anno"];
            }
            if (isset($row["motto"]))
            {
                $edizione->motto = $row["motto"];
            }
            if (isset($row["path_immagine"]))
            {
                $edizione->imgpath = $row["path_immagine"];
            }
        }
    }
    return $edizione;
}
function isIscritto($connection, int $id_anagrafica, int $edizione)
{
    $query = "SELECT * FROM iscritti WHERE dati_anagrafici = $id_anagrafica AND edizione = $edizione";
    $result = mysqli_query($connection, $query);
    if (!$result)
        return false;
    return (bool)$result->fetch_assoc();
}
function iscrivi($connection, int $id_anagrafica, int $tutore, $certificato, int $parrocchia, string $taglia, int $edizione)
{
    if (!$connection)
        return false;
    $maglia_sana = sql_sanitize($taglia);
    $query = "INSERT INTO iscritti (id, dati_anagrafici, edizione, tutore, certificato_medico, parrocchia, taglia_maglietta) VALUES (NULL, $id_anagrafica, $edizione, ";
    if ($tutore <= 0)
    {
        $query .= "NULL, ";
    } else {
        $query .= "$tutore, ";
    }
    if (isset($certificato) && strlen($certificato) > 0)
    {
        $query .= "'$certificato', ";
    } else {
        $query .= "NULL, ";
    }
    $query .= "$parrocchia, '$maglia_sana')";
    $result = mysqli_query($connection, $query);
    return (bool)$result;
}
function aggiorna_certificato_iscrizione($connection, int $id, string $certificato)
{
    if (!$connection)
        return false;
    $certificato = sql_sanitize($certificato);
    $query = "UPDATE iscritti SET certificato_medico = '$certificato' WHERE id = $id";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        return false;
    }
    return mysqli_affected_rows($connection) === 1;
}
function aggiorna_iscrizione($connection, raw_iscrizione $i)
{
    if (!$connection)
        return false;
    $taglia = sql_sanitize($i->taglia);
    $tutore = $i->id_tutore;
    if ($tutore === 0)
    {
        $tutore = "NULL";
    }
    $parrocchia = $i->id_parrocchia;

    $query = "UPDATE iscritti SET taglia_maglietta = '$taglia', tutore = $tutore, parrocchia = $parrocchia WHERE id = $i->id";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        return false;
    }
    return mysqli_affected_rows($connection) === 1;
}
function crea_anagrafica($connection, string $nome, string $cognome, 
    string $compleanno, string $provenienza, string $tel, 
    string $email, string $cf, int $doc_type, 
    string $doc_code, string $doc_expires, string $nome_file, bool $is_extern = false)
{
    if (!$connection)
        return 0;
    //Input is already sanitized by the caller
    $query = "CALL CreaAnagrafica('$nome', '$cognome', '$compleanno', '$provenienza', ";
    if ($tel == "")
    {
        $query .= "NULL, ";
    } else {
        $query .= "'$tel', ";
    }
    if ($email == "")
    {
        $query .= "NULL, ";
    } else {
        $query .= "'$email', ";
    }
    $query .= "'$cf', $doc_type, '$doc_code', '$doc_expires', '$nome_file', ";
    if ($is_extern)
    {
        $query .= "1";
    } else {
        $query .= "0";
    } $query .= ");";
    $result = mysqli_query($connection, $query);
    $id = 0;
    if ($result && $row = $result->fetch_assoc())
    {
        if (isset($row["id"]))
        {
            $id = (int)$row["id"];
        }
        $result->close();
    }
    mysqli_next_result($connection);
    return $id;
}


//
//  Parrocchie
//

class parrocchia
{
    public int $id = 0;
    public string $nome = "";
}
function getParrocchia($connection, int $id)
{
    if ($connection == null || $id <= 0)
        return "";
    $query  = "SELECT nome FROM parrocchie WHERE id = $id";

    $result = mysqli_query($connection, $query);

    if (!$result)
    {
        return "";
    }
    if ($row = $result->fetch_assoc())
    {
        return $row["nome"];
    }
    return "";
}
function getParrocchiaDaUserName($connection, int $id)
{
    if ($connection == null || $id <= 0)
        return 0;
    $query  = "SELECT DISTINCT parrocchia FROM staffisti WHERE id_utente = $id";

    $result = mysqli_query($connection, $query);

    if (!$result)
    {
        return 0;
    }
    if ($row = $result->fetch_assoc())
    {
        return (int)$row["parrocchia"];
    }
    return 0;
}
function parrocchie($connection)
{
    if (!$connection)
        return array();
    $query  = "SELECT id, nome FROM parrocchie";

    $result = mysqli_query($connection, $query);

    if (!$result)
    {
        return array();
    }
    $parr = array();
    while ($row = $result->fetch_assoc())
    {
        $curr = new parrocchia();
        $curr->id = (int)$row["id"];
        $curr->nome = $row["nome"];
        $parr[] = $curr;
    }
    return $parr;
}

//
//  Altro
//
include "sport-functions.php";
include "system-functions.php";
include "user-functions.php";