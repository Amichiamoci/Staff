<?php
class raw_sport
{
    public int $id = 0;
    public string $label = "";
}
function tuttiGliSport($connection)
{
    $query = "SELECT id, nome FROM sport ORDER BY nome";
    $arr  = array();
    $result = mysqli_query($connection, $query);
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $sport = new raw_sport();
            $sport->id = (int)$row["id"];
            $sport->label = $row["nome"];
            $arr[] = $sport;
        }
    }
    return $arr;
}
function tipiTorneo($connection)
{
    $query = "SELECT id, nome FROM tipi_torneo ORDER BY nome";
    $arr  = array();
    $result = mysqli_query($connection, $query);
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $sport = new raw_sport();
            $sport->id = (int)$row["id"];
            $sport->label = $row["nome"];
            $arr[] = $sport;
        }
    }
    return $arr;
}
function getSport($connection, $id)
{
    if ($id == null || $id <= 0)
        return "";
    $query  = "SELECT nome FROM sport WHERE id = $id";

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
//
//  Gestione squadre
//

function crea_squadra($connection, string $nome, int $parrocchia, int $sport, string $membri, int $edizione)
{
    if (!$connection || $edizione == 0)
        return false;
    $nome_sano = sql_sanitize($nome);
    $membri_sano = sql_sanitize($membri);
    $query = "CALL CreaSquadra('$nome_sano', $parrocchia, $sport, '$membri_sano', $edizione)";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        return false;
    }
    $ret = false;
    if ($row = $result->fetch_assoc())
    {
        $ret = isset($row["id"]) && $row["id"] != "0";
    }
    $result->close();
    mysqli_next_result($connection);
    return $ret;
}
function modifica_squadra($connection, int $id, string $nome, int $parrocchia, int $sport, string $membri)
{
    if (!$connection || $id === 0)
        return false;
    $nome_sano = sql_sanitize($nome);
    $membri_sano = sql_sanitize($membri);
    $query = "CALL ModificaSquadra($id, '$nome_sano', $parrocchia, $sport, '$membri_sano')";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        return false;
    }
    $ret = false;
    if ($row = $result->fetch_assoc())
    {
        $ret = isset($row["Result"]) && $row["Result"] != "0";
    }
    $result->close();
    mysqli_next_result($connection);
    return $ret;
}

//
//
//  Squadre
//
//
class raw_squadra
{
    public int $id = 0;
    public string $nome = "";
    
    public string $membri = "";
    public string $id_iscr_membri = "";

    public string $parrocchia = "";
    public int $id_parrocchia = 0;
    
    public string $sport = "";
    public int $id_sport = 0;
}
function raw_squadre_list($connection, $year = null, $sport = null)
{
    if (!$connection)
        return array();
    if (!isset($year) || $year == null || $year == "0")
    {
        $year = "NULL";
    }
    if (!isset($sport) || $sport == null || $sport == "0")
    {
        $sport = "NULL";
    }
    $year = sql_sanitize($year);
    $sport = sql_sanitize($sport);
    $query = "CALL SquadreList($year, $sport);";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
        return array();
    }
    $arr = array();
    
    while ($row = $result->fetch_assoc())
    {
        $s = new raw_squadra();
        $s->nome = $row["nome"];
        $s->parrocchia = $row["parrocchia"];
        $s->id = (int)$row["id_squadra"];
        $s->sport = $row["nome_sport"];
        if (isset($row["lista_membri"]))
        {
            $s->membri = $row["lista_membri"];
        }
        $arr[] = $s;
    }
    $result->close();
    mysqli_next_result($connection);
    return $arr;
}
function getSquadreList($connection, $year = null, $sport = null, bool $show_inks = true)
{
    $arr = raw_squadre_list($connection, $year, $sport);
    $str = "<div class=\"tables flex wrap\">\n";
    if (count($arr) === 0)
    {
        $str .= "<h3>Nessuna squadra (ancora) per l'anno corrente</h3>\n";
    }
    foreach ($arr as $a)
    {
        $nome = acc($a->nome);
        $parrocchia = acc($a->parrocchia);
        $sport = acc($a->sport);
        $id = $a->id;

        $str .= "<table>\n";
        $str .= "<thead>\n";
        $str .= "<tr>\n";
        $str .= "<th data-label='Nome'>$nome</th>\n";
        $str .= "</tr>\n";
        $str .= "</thead>\n";
        $str .= "<tbody>\n";
        $str .= "<tr>\n";
        
        $str .= "<td data-label='Sport'>$sport</td>\n";
        $str .= "<td data-label='Parrocchia'>$parrocchia</td>\n";
        if (strlen($a->membri) > 0)
        {
            $str .= "<td data-label='Membri'>";
            $membri = explode(', ', $a->membri);
            foreach ($membri as $membro)
            {
                $str .= acc($membro) . "<br>\n";
            }
            $str .= "</td>\n";
        } else {
            $str .= "<td data-label='Membri'>Nessuno registrato</td>\n";
        }
        if ($show_inks)
        {
            $str .= "<td data-label='Gestisci'><a href='cancella.php?id=$id' class='link'>Elimina</a></td>\n";
            $str .= "<td data-label='Gestisci'><a href='crea.php?id=$id' class='link'>Modifica</a></td>\n";
        }
        
        $str .= "</tr>\n";
        $str .= "</tbody>\n";
        $str .= "</table>\n";
    }
    $str .= "</div>\n";
    return $str;
}
function getNomeSquadra($connection, int $id = 0)
{
    if (!$connection || $id == 0)
        return "";
    $query = "CALL GetNomeSquadra($id)";
    $result = mysqli_query($connection, $query);
    $ret = "";
    if ($result)
    {
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["nome"]))
            {
                $ret = $row["nome"];
            }
        }
        $result->close();
    }
    mysqli_next_result($connection);
    return $ret;
}
function cancellaSquadra($connection, int $id = 0)
{
    if (!$connection || $id == 0)
        return "";
    $query = "CALL CancellaSquadra($id);";
    $result = (bool)mysqli_query($connection, $query);
    $ret = "";
    if (!$result)
    {
        $ret = mysqli_error($connection);
    }
    if (mysqli_affected_rows($connection) !== 1 && $ret != "")
    {
        $ret = "La squadra selezionata non esiste.";
    }
    mysqli_next_result($connection);
    return $ret;
}
function raw_squadra($connection, int $id = 0)
{
    if (!$connection || $id === 0)
    {
        return new raw_squadra();
    }
    $squadra = new raw_squadra();
    $query = "CALL GetSquadra($id);";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
        return $squadra;
    }
    if ($row = $result->fetch_assoc())
    {
        $squadra->id = (int)$row["id"];
        $squadra->nome = $row["nome"];

        if (isset($row["id_iscr_membri"]))
            $squadra->id_iscr_membri = $row["id_iscr_membri"];
        if (isset($row["membri"]))
            $squadra->membri = $row["membri"];
        
        $squadra->parrocchia = $row["parrocchia"];
        $squadra->id_parrocchia = (int)$row["id_parrocchia"];
        
        $squadra->sport = $row["sport"];
        $squadra->id_sport = (int)$row["id_sport"];
    }
    mysqli_next_result($connection);
    return $squadra;
}
//
//
//  Tornei
//
//
function crea_torneo($connection, int $sport, string $nome, int $tipo)
{
    if (!$connection)
        return false;
    $query = "INSERT INTO tornei (edizione, nome, sport, tipo_torneo) VALUES (" . getCurrentEdition($connection)->id .
        ", '" . sql_sanitize($nome) . "', $sport, $tipo)";
    $result = mysqli_query($connection, $query);
    return (bool)$result;
}
class raw_torneo
{
    public int $id = 0;
    public string $nome = "";
    public string $tipo = "";
    public int $id_tipo = 0;
    public string $sport = "";
    public int $id_sport = 0;
    public int $numero_squadre = 0;
}
function lista_tornei($connection)
{
    if (!$connection)
        return array();
    $query = "SELECT * FROM tornei_attivi";
    $result = mysqli_query($connection, $query);
    if (!$result)
        return array();
    $arr = array();
    while ($row = $result->fetch_assoc())
    {
        $t = new raw_torneo();

        $t->id = (int)$row["id"];
        $t->nome = $row["nome"];

        $t->id_sport = (int)$row["codice_sport"];
        $t->sport = $row["sport"];

        $t->id_tipo = (int)$row["tipo_id"];
        $t->tipo = $row["tipo"];
        
        $t->numero_squadre = (int)$row["numero_squadre"];
        $arr[] = $t;
    }
    return $arr;
}
function iscrivi_a_torneo($connection, int $torneo, int $squadra):bool
{
    if (!$connection)
        return false;
    try {
        $query = "REPLACE INTO partecipaz_squad_torneo (torneo, squadra) VALUES ($torneo, $squadra)";
        $res = mysqli_query($connection, $query);
        return (bool)$res;
    } catch (Exception $ex) {
        return false;
    }
}
function getTornei($connection):string
{
    if (!$connection)
        return "&Egrave; avvenuto un errore.";
    $query = "SELECT * FROM tornei_attivi";
    $result = mysqli_query($connection, $query);
    if (!$result)
        return "Impossibile interrogare il DB.";
    if ($result->num_rows === 0)
    {
        return "Nessun torneo (ancora) per quest'anno";
    }
    $str = "<div class='tables flex wrap'>";
    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $nome = acc($row["nome"]);
        $sport = acc($row["sport"]);
        $tipo = acc($row["tipo"]);
        $squadre = acc($row["squadre"]);
        $numero_squadre = (int)$row["numero_squadre"];
        $calendario = acc($row["calendario"]);
        $numero_partite = (int)$row["partite"];

        $str .= "<table>";

        $str .= "<thead>";
        $str .= "<tr>";
        $str .= "<th data-label='Nome'>$nome</th>";
        $str .= "<th data-label='Sport'>$sport</th>";
        $str .= "</tr>";
        $str .= "</thead>";

        $str .= "<tbody>";
        $str .= "<tr>";
        $str .= "<td data-label='Modalità'>$tipo</td>";
        $str .= "<td data-label='Squadre ($numero_squadre)'>$squadre</td>";
        $str .= "<td data-label='Calendario'>$calendario</td>";
        if ($numero_partite === 0)
        {
            $str .= "<td data-label='Azioni'><a href='./crea-partite.php?id=$id' class='link'>Crea Calendario</a></td>";
        } else {
            $str .= "<td data-label='Azioni'><a href='./modifica-partite.php?id=$id' class='link'>Modifica date partite</a></td>";
        }
        $str .= "</tr>";
        $str .= "</tbody>";

        $str .= "</table>";
    }
    $str .= '</div>';
    return $str;
}

function GeneraCalendario($connection, int $torneo)
{
    if (!$connection)
        return false;
    $query = "CALL CreaCalendario($torneo);";
    $result = mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return (bool)$result;    
}
function calendarioTorneo($connection, int $torneo):string
{
    if (!$connection)
        return "&Egrave; avvenuto un errore.";
    $query_campi = "SELECT * FROM campi";
    $result_campi = mysqli_query($connection, $query_campi);
    $campi = array();
    $campi[] = array(0, "Nessun campo selezionato");
    if ($result_campi)
    {
        while($row = $result_campi->fetch_assoc())
        {
            $campi[] = array((int)$row["id"], acc($row["nome"]));
        }
    }
    $query = "SELECT * FROM partite_tornei_attivi WHERE torneo = $torneo";
    $result = mysqli_query($connection, $query);
    if (!$result)
        return "Impossibile interrogare il DB.";
    $str = "<div class='tables flex wrap'>";
    
    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $casa = acc($row["casa"]);
        $ospiti = acc($row["ospiti"]);
        $data = "";
        $orario = "";
        $dove = 0;

        if (isset($row["data"]))
        {
            $data = $row["data"];
        }
        if (isset($row["orario"]))
        {
            $orario = $row["orario"];
        }
        if (isset($row["campo"]))
        {
            $dove = (int)$row["campo"];
        }

        $str .= "<table>";

        $str .= "<thead>";
        $str .= "<tr>";
        $str .= "<th>$casa - $ospiti</th>";
        $str .= "</tr>";
        $str .= "</thead>";

        $str .= "<tbody>";
        $str .= "<tr>";
        $str .= "<td data-label=\"Data\"><input type=\"date\" id=\"date-$id\" value=\"$data\" onchange=\"SyncMatchDate($id)\"></td>";
        $str .= "<td data-label=\"Ora\"><input type=\"time\" id=\"time-$id\" value=\"$orario\" onchange=\"SyncMatchTime($id)\"></td>";
        $str .= "<td data-label=\"Luogo\"><select id=\"where-$id\" onchange=\"SyncMatchWhere($id)\">";
        foreach ($campi as $campo)
        {
            $id_campo = $campo[0];
            $nome_campo = $campo[1];
            if ($id_campo === $dove)
            {
                $str .= "<option selected=\"selected\" value=\"$id_campo\">$nome_campo</option>";
            } else {
                $str .= "<option value=\"$id_campo\">$nome_campo</option>";
            }
        }
        $str .= "</select></td>";
        $str .= "</tr>";
        $str .= "</tbody>";

        $str .= "</table>";
    }
    $str .= '</div>';

    return $str;
}
function inserisciPunteggioPartite($connection):string
{
    if (!$connection)
    {
        return "";
    }
    $query = "SELECT * FROM partite_settimana";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        return "";
    }
    if ($result->num_rows === 0)
    {
        return "Nessuna partita questa settimana";
    }
    $str = "<div class='tables flex wrap'>";
    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $torneo = acc($row["nome_torneo"]);
        $sport = acc($row["sport"]);
        $data_ora = acc($row["data_ora_italiana"]);

        $squadra_casa = acc($row["casa"]);
        $squadra_ospite = acc($row["ospiti"]);

        $id_punteggi = array();
        if (isset($row["id_punteggi"]))
        {
            $id_punteggi = explode("|", $row["id_punteggi"]);
        }
        $punteggi_casa = array();
        if (isset($row["punteggi_casa"]))
        {
            $punteggi_casa = explode("|", $row["punteggi_casa"]);
        }
        $punteggi_ospiti = array();
        if (isset($row["punteggi_ospiti"]))
        {
            $punteggi_ospiti = explode("|", $row["punteggi_ospiti"]);
        }

        $str .= "<table>\n";

        $str .= "<thead>\n";
        $str .= "<tr>\n";
        $str .= "<th data-label='Torneo'>$torneo | $sport</th>\n";
        $str .= "<th data-label='Partita'>$squadra_casa - $squadra_ospite</th>\n";
        $str .= "</tr>\n";
        $str .= "</thead>\n";

        $str .= "<tbody>\n";
        $str .= "<tr id='scores-row-$id'>\n";
        $str .= "<td data-label='Data'>$data_ora</td>\n";

        if (
            count($id_punteggi) !== count($punteggi_casa) || 
            count($id_punteggi) !== count($punteggi_ospiti))
        {
            $str .= "<td data-label='Errore'><strong>Dati sui punteggi incoerenti!</strong></td>\n";
        } else {
            if (count($id_punteggi) === 0)
            {
                $str .= "<td data-label='Non disputata'><strong>&darr;Aggiungi punteggio&darr;</strong></td>\n";
            }
            for ($i = 0; $i < count($id_punteggi); $i++)
            {
                $id_punteggio = (int)$id_punteggi[$i];
                $punteggio = acc($punteggi_casa[$i] . " - " . $punteggi_ospiti[$i]);
                $i_1 = $i + 1;
                
                $str .= "<td data-label='Match $i_1' data-match='$id_punteggio'>";
                $str .= "<input type=\"text\" pattern=\"[0-9]+\s{0,}-\s{0,}[0-9]+\" placeholder=\"1 - 1\" id=\"match-$id_punteggio\" value=\"$punteggio\" oninput=\"SyncScore($id_punteggio)\">\n";
                $str .= "<button type=\"button\" title=\"Rimuovi Match\" style=\"color:var(--cl-red);\" onclick=\"RemoveScore($id_punteggio)\">X</button>";
                $str .= "</td>\n";
            }
            $str .= "<td data-label='Azioni' id=\"add-score-btn-td-$id\"><button type=\"button\" onclick=\"AddScore($id)\" id=\"add-score-btn-$id\">Aggiungi punteggio</button></td>";
        }

        $str .= "</tr>\n";
        $str .= "</tbody>\n";

        $str .= "</table>\n";
    }
    $str .= "</div>";
    return $str;
}