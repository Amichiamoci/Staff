<?php

//
//
//  Squadre
//
//

function getSquadreList($connection, $year = null, $sport = null, bool $show_inks = true)
{
    $arr = Squadra::List($connection, $year, $sport);
    $str = "<div class=\"tables flex wrap\">\n";
    if (count($arr) === 0)
    {
        $str .= "<h3>Nessuna squadra (ancora) per l'anno corrente</h3>\n";
    }
    foreach ($arr as $a)
    {
        $nome = htmlentities($a->nome);
        $parrocchia = htmlentities($a->parrocchia);
        $sport = htmlentities($a->sport);
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
                $str .= htmlentities($membro) . "<br>\n";
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


//
//
//  Tornei
//
//


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