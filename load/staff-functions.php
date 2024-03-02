<?php
//
//
//   Cool lists
//
//
function getStaffList($connection, int $year = 0, bool $include_all = false) 
{
    $query  = "CALL StaffList(";
    if (isset($year) && $year != 0)
    {
        $query .= "$year,";
    } else {
        $query .= "NULL,";
    }
    if ($include_all) {
        $query .= "1";
    } else {
        $query .= "0";
    }
    $query .= ")";

    $result = mysqli_query($connection, $query);

    if ($result)
    {
        $staff_list  = "<h2>Elenco staffisti</h2>";
        $staff_list .= "<div class='tables flex wrap'>";

        while ($row = $result->fetch_assoc())
        {
            //$id               = $row["id_staffista"];
            $nome             = $row["nome"];
            $cognome          = $row["cognome"];
            $data_nascita     = $row["data_nascita"];
            $referente        = isset($row["referente"]) && (bool)$row["referente"];
            $parrocchia       = acc($row["parrocchia"]);

            $birth_date 	  = new DateTime($data_nascita);
            $current_date     = new DateTime(date("Y-m-d"));

            $data             = strtotime($data_nascita);
            $data_nascita     = date("d/m/Y", $data);

            $eta              = date_diff($current_date, $birth_date)->y;

            $commissioni = null;
            if (isset($row["lista_commissioni"]))
                $commissioni      = acc($row["lista_commissioni"]);

            $staff_list .= "<table>";
            $staff_list .= "<thead>";
            $staff_list .= "<tr>";
            $staff_list .= "<th data-label='Nome'>$nome $cognome</th>";
            $staff_list .= "</tr>";
            $staff_list .= "</thead>";
            $staff_list .= "<tbody>";
            $staff_list .= "<tr>";
            $staff_list .= "<td data-label='Et&agrave;'>$eta</td>";
            if (isset($row["telefono"]))
            {
                $telefono = $row["telefono"];
                $staff_list .= "<td data-label='Telefono'><a href='tel:$telefono'>$telefono</a></td>";
            }
            if (isset($row["email"]))
            {
                $email = $row["email"];
                $staff_list .= "<td data-label='E-mail'><a href='mailto:$email'>$email</a></td>";
            }
            $staff_list .= "<td data-label='Parrocchia'>$parrocchia</td>";
            if (isset($row["referente"]))
            {
                if ($referente)
                {
                    $staff_list .= "<td data-label='Ruolo'>Referente parrocchiale</td>";
                }
                else
                {
                    $staff_list .= "<td data-label='Ruolo'>Staffista</td>";
                }
            }

            if (isset($commissioni))
            {
                $staff_list .= "<td data-label='Commissioni'>$commissioni</td>";
            }
            if (isset($row["partecipazioni"]))
            {
                $partecipazioni = acc($row["partecipazioni"]);
                $staff_list .= "<td data-label='Partecipazioni'>$partecipazioni</td>";
            }
            $staff_list .= "</tr>";
        }

        $staff_list .= "</tbody>";
        $staff_list .= "</table>";
        $result->close();
    } else {
        $staff_list = "Errore";
    }
    
    mysqli_next_result($connection);
    return $staff_list;
}

function getAnagraficheList($connection, $year = null, int $id_parrocchia = 0, $add_link = true)
{
    $year_p = "NULL";
    if (isset($year))
    {
        if ($year === "0")
        {
            $year_p = "YEAR(CURRENT_DATE)";
        } else {
            $year_p = sql_sanitize($year);
        }
    }
    $parrocchia_p = "NULL";
    if (isset($id_parrocchia) && $id_parrocchia !== 0)
    {
        $parrocchia_p = "$id_parrocchia";
    }
    $query = "CALL IscrizioniList($year_p, $parrocchia_p);";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
        return "<h3>&Egrave; avvenuto un errore, ci scusiamo per il disagio.</h3>\n";
    }
    if ($result->num_rows == 0)
    {
        $result->close();
        mysqli_next_result($connection);
        return "<h3>Nessuna anagrafica</h3>\n";
    }
    $str = "<h3>Numero anagrafiche: <output>$result->num_rows</output></h3>\n";
    $str .= "<div class=\"tables flex wrap\">\n";
    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $sesso = "unknown";
        if (isset($row["sesso"]))
            $sesso = strtolower($row["sesso"]);
        $str .= "<table data-id=\"$id\" class=\"gender-$sesso\">\n<thead>\n<tr>\n";
        $str .= "<th data-label='Nome'>" . acc($row["nome"]) . "</th>\n";
        $str .= "<th data-label='Cognome'>" . acc($row["cognome"]) . "</th>\n";
        $str .= "</tr>\n</thead>\n<tbody>\n<tr>\n";
        
        $eta  = $row["eta"];
        $str .= "<td data-label='Data di nascita'>" . $row["data_nascita"] . " ($eta)</td>\n";
        $cf = $row["cf"];
        $str .= "<td data-label='Codice Fiscale'><output>$cf</output></td>\n";
        if (isset($row["telefono"]))
        {
            $number = $row["telefono"];
            if (str_starts_with($number, "+39"))
            {
                $number = substr($number, 3);
            }
            if (str_starts_with($number, "0"))
            {
                //E' un fisso probabilmente
                $str .= "<td data-label='Telefono'><a href=\"tel:$number\" class=\"link\">$number</a></td>\n";
            } else {
                //Non e' un fisso, usiamo link WhatsApp
                $wa = Link::Number2WhatsApp($number);
                $str .= "<td data-label='WhatsApp'>$wa</td>\n";
            }
        }
        if (isset($row["email"]))
        {
            $email = $row["email"];
            $str .= "<td data-label='Email'><a href=\"mailto:$email\" class=\"link\">$email</a></td>\n";
        }
        $label = acc($row["label"]);
        if (isset($row["codice_documento"]) && strlen($row["codice_documento"]) > 3)
        {
            $doc_code = $row["codice_documento"];
        } else {
            $doc_code = "Codice mancante!";
        }
        if (isset($row["documento"]) && strlen($row["documento"]) > 1)
        {
            if (we_have_file($row["documento"]))
            {
                $doc_url = get_file_export_url($row["documento"]);
                $str .= "<td data-label=\"$label\"><a href='$doc_url' download class=\"link\">$doc_code</a></td>\n";
            } else {
                $str .= "<td data-label=\"$label\"><a href=\"javascript:alert('File mancante!')\" class=\"link\">$doc_code</a></td>\n";
            }
        } else {
            $str .= "<td data-label=\"$label\"><strong>Mancante</strong>: <a href=\"./crea-anagrafica.php?cf=$cf\" class=\"link\">Inserisci</a></td>\n";
        }
        if (isset($row["codice_iscrizione"]))
        {
            $str .= "<td data-label='Codice iscrizione'><output>" . $row["codice_iscrizione"] . "</output></td>\n";
        }
        if (isset($row["id_iscrizione"]))
        {
            $iscrizione = (int)$row["id_iscrizione"];
            $str .= "<td data-label='Parrocchia'>" . $row["anno"]. ": Iscritto per " . acc($row["parrocchia"]) ."</td>\n";
            
            $inserisci_certificato = "<a href=\"./iscrivi.php?iscrizione=$iscrizione&id=$id\" class=\"link\">Inserisci/Modifica</a>";
            if (isset($row["certificato_medico"]))
            {
                if (we_have_file($row["certificato_medico"]))
                {
                    $doc_url = get_file_export_url($row["certificato_medico"]);
                    $link1 = "<a href=\"$doc_url\" download class=\"link\">Scarica</a>";
                } else {
                    $link1 = "<a href=\"javascript:alert('File mancante!')\" class=\"link\">Scarica</a>";
                }
                if ($add_link)
                {
                    $str .= "<td data-label=\"Certificato Medico\">$link1 - $inserisci_certificato</td>\n";
                } else {
                    $str .= "<td data-label=\"Certificato Medico\">$link1</td>\n";
                }
            } else {
                if ($add_link)
                {
                    $str .= "<td data-label=\"Certificato Medico\"><strong>Mancante:</strong> $inserisci_certificato</td>\n";
                } else {
                    $str .= "<td data-label=\"Certificato Medico\"><strong>Mancante!</strong></td>\n";
                }
            }
        }
        if (isset($row["iscrivi"]) && $add_link)
        {
            $str .= "<td data-label='Iscrivi'><a href=\"iscrivi.php?id=$id\" class=\"link\"><strong>&rarr; " . $row["iscrivi"] ." &larr;</strong></a></td>\n";
        }
        if (isset($row["tutore"]))
        {
            $tutore = acc($row["tutore"]);
            $str .= "<td data-label='Genitore o tutore'>$tutore</td>\n";
        }
        if (isset($row["creatore_dati"]))
        {
            $str .= "<td data-label='Dati inseriti da'>" . $row["creatore_dati"] . "</td>\n";
        }
        $str .= "</tr>\n</tbody>\n</table>\n\n";
    }
    $result->close();
    mysqli_next_result($connection);
    $str .= "</div>";
    return $str;
}

function getNonPartecipantiList($connection, $year = null)
{
    $year_p = "NULL";
    if (isset($year))
    {
        if ($year === "0")
        {
            $year_p = "YEAR(CURRENT_DATE)";
        } else {
            $year_p = sql_sanitize($year);
        }
    }
    $query = "CALL NonIscrittiNonStaff($year_p);";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
        return "<h3>&Egrave; avvenuto un errore, ci scusiamo per il disagio.</h3>\n";
    }
    if ($result->num_rows == 0)
    {
        $result->close();
        mysqli_next_result($connection);
        return "<h3>Nessuna anagrafica</h3>\n";
    }
    $str = "<h3>Numero anagrafiche: <output>$result->num_rows</output></h3>\n";
    $str .= "<div class='tables flex wrap'>\n";
    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $sesso = "unknown";
        if (isset($row["sesso"]))
            $sesso = strtolower($row["sesso"]);
        $str .= "<table data-id=\"$id\" class=\"gender-$sesso\">\n<thead>\n<tr>\n";
        $str .= "<th data-label='Nome'>" . acc($row["nome"]) . "</th>\n";
        $str .= "<th data-label='Cognome'>" . acc($row["cognome"]) . "</th>\n";
        $str .= "</tr>\n</thead>\n<tbody>\n<tr>\n";
        
        $eta  = $row["eta"];
        $str .= "<td data-label='Data di nascita'>" . $row["data_nascita"] . " ($eta)</td>\n";
        $cf = $row["cf"];
        $str .= "<td data-label='Codice Fiscale'><output>$cf</output></td>\n";
        if (isset($row["telefono"]))
        {
            $number = $row["telefono"];
            if (str_starts_with($number, "+39"))
            {
                $number = substr($number, 3);
            }
            if (str_starts_with($number, "0"))
            {
                //E' un fisso probabilmente
                $str .= "<td data-label='Telefono'><a href=\"tel:$number\" class=\"link\">$number</a></td>\n";
            } else {
                //Non e' un fisso, usiamo link WhatsApp
                $str .= "<td data-label='WhatsApp'><a href=\"https://wa.me/$number\" class=\"link\">$number</a></td>\n";
            }
        }
        if (isset($row["email"]))
        {
            $email = $row["email"];
            $str .= "<td data-label='Email'><a href=\"mailto:$email\" class=\"link\">$email</a></td>\n";
        }
        $label = acc($row["label"]);
        if (isset($row["codice_documento"]) && strlen($row["codice_documento"]) > 3)
        {
            $doc_code = $row["codice_documento"];
        } else {
            $doc_code = "Codice mancante!";
        }
        if (isset($row["documento"]) && strlen($row["documento"]) > 1)
        {
            if (we_have_file($row["documento"]))
            {
                $doc_url = get_file_export_url($row["documento"]);
                $str .= "<td data-label=\"$label\"><a href='$doc_url' download class=\"link\">$doc_code</a></td>\n";
            } else {
                $str .= "<td data-label=\"$label\"><a href=\"javascript:alert('File mancante!')\" class=\"link\">$doc_code</a></td>\n";
            }
        } else {
            $str .= "<td data-label=\"$label\"><strong>Mancante</strong>: <a href=\"./crea-anagrafica.php?cf=$cf\" class=\"link\">Inserisci</a></td>\n";
        }
        if (isset($row["creatore_dati"]))
        {
            $str .= "<td data-label='Dati inseriti da'>" . $row["creatore_dati"] . "</td>\n";
        }
        $str .= "</tr>\n</tbody>\n</table>\n\n";
    }
    $result->close();
    mysqli_next_result($connection);
    $str .= "</div>";
    return $str;
}
function getIscrizioniSbagliate($connection, $year = null, int $id_parrocchia = 0, $add_link = true)
{
    $year_p = "NULL";
    if (isset($year))
    {
        if ($year === "0")
        {
            $year_p = "YEAR(CURRENT_DATE)";
        } else {
            $year_p = sql_sanitize($year);
        }
    }
    $parrocchia_p = "NULL";
    if (isset($id_parrocchia) && $id_parrocchia !== 0)
    {
        $parrocchia_p = "$id_parrocchia";
    }
    $query = "CALL ProblemiParrocchia($parrocchia_p, $year_p);";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
        return "<h3>&Egrave; avvenuto un errore, ci scusiamo per il disagio.</h3>\n";
    }
    if ($result->num_rows == 0)
    {
        $result->close();
        mysqli_next_result($connection);
        return "<h3>Nessuna iscrizione da correggere</h3>\n";
    }
    $str = "<h3>Numer errori: <output>$result->num_rows</output></h3>\n";
    $str .= "<div class=\"tables flex wrap\">\n";
    while ($row = $result->fetch_assoc())
    {
        $id = (int)$row["id"];
        $iscrizione = (int)$row["iscrizione"];
        $cf = $row["cf"];
        $sesso = "unknown";
        if (isset($row["sesso"]))
            $sesso = strtolower($row["sesso"]);
        $str .= "<table data-id=\"$id\" class=\"gender-$sesso\">\n<thead>\n<tr>\n";
        $str .= "<th data-label='Chi'>" . acc($row["chi"]) . "</th>\n";
        $str .= "</tr>\n</thead>\n<tbody>\n<tr>\n";
        if (isset($row["email"]))
        {
            $str .= "<td data-label='Email'>" . acc($row["email"]) . "</td>\n";
        }
        if (isset($row["email_verify"]))
        {
            $str .= "<td data-label='Email'>" . acc($row["email_verify"]) . "</td>\n";
        }
        if (isset($row["telefono"]))
        {
            $str .= "<td data-label='Telefono'>" . acc($row["telefono"]) . "</td>\n";
        }
        if (isset($row["doc_code"]))
        {
            $str .= "<td data-label='Codice Documento'>" . acc($row["doc_code"]) . "</td>\n";
        }
        if (isset($row["doc"]))
        {
            if ($add_link)
            {
                $str .= "<td data-label='Documento'><strong>" . acc($row["doc"]) . "</strong> <a href=\"./crea-anagrafica.php?cf=$cf\" class=\"link\">Inserisci</a></td>\n";
            } else {
                $str .= "<td data-label='Documento'>" . acc($row["doc"]) . "</td>\n";
            }
            $str .= "<td data-label='Documento'>" . acc($row["doc"]) . "</td>\n";
        }
        if (isset($row["scadenza"]))
        {
            $str .= "<td data-label='Scadenza Documento'>" . acc($row["scadenza"]) . "</td>\n";
        }
        if (isset($row["certificato"]))
        {
            if ($add_link)
            {
                $str .= "<td data-label='Certificato Medico'><strong>" . 
                    acc($row["certificato"]) . 
                    "</strong> <a href=\"./iscrivi.php?iscrizione=$iscrizione&id=$id\" class=\"link\">Inserisci</a></td>\n";
            } else {
                $str .= "<td data-label='Certificato'>" . acc($row["certificato"]) . "</td>\n";
            }
        }
        if (isset($row["tutore"]))
        {
            if ($add_link)
            {
                $str .= "<td data-label='Tutore'><strong>" . 
                    acc($row["tutore"]) . 
                    "</strong> <a href=\"./iscrivi.php?iscrizione=$iscrizione&id=$id\" class=\"link\">Inserisci/Cambia</a></td>\n";
            } else {
                $str .= "<td data-label='Tutore'>" . acc($row["tutore"]) . "</td>\n";
            }
            
        }
        if (isset($row["eta"]))
        {
            $str .= "<td data-label='Età'>" . acc($row["eta"]) . "</td>\n";
        }
        if (isset($row["maglia"]))
        {
            $str .= "<td data-label='Maglia'>" . acc($row["maglia"]) . "</td>\n";
        }
        $str .= "</tr>\n</tbody>\n</table>\n\n";
    }
    $result->close();
    mysqli_next_result($connection);
    $str .= "</div>";
    return $str;
}
class raw_staff
{
    public int $id = 0;
    public string $name = "";
}
class raw_iscrizione
{
}
class raw_anagrafica
{
}
class tipo_documento
{
}
class raw_commissione
{
    public int $id = 0;
    public string $nome = "";
}
//
//
//   Raw data
//
//
function getRawStaffList($connection)
{
    $query  = "CALL RawStaffList();";
    $result = mysqli_query($connection, $query);
    $arr = array();
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $staff = new raw_staff();
            $staff->id = (int)$row["staff"];
            $staff->name = $row["nome_completo"];
            $arr[] = $staff;
        }
        $result->close();
    }
    mysqli_next_result($connection);
    return $arr;
}
function tutteLeCommissioni($connection)
{
    $arr = array();
    $query = "SELECT id, nome FROM commissioni ORDER BY nome, id";
    $result = mysqli_query($connection, $query);
    if ($result)
    {
        while ($row = $result->fetch_assoc())
        {
            $commissione = new raw_commissione();
            $commissione->id = (int)$row["id"];
            $commissione->nome = $row["nome"];
            $arr[] = $commissione;
        }
    }
    return $arr;
}
//
//  Simple query
//
function crea_staff($connection, int $id_anagrafica, int $user, int $parrocchia)
{
    if (!$connection)
        return 0;
    $query = "INSERT INTO staffisti (dati_anagrafici, id_utente, parrocchia) VALUES ($id_anagrafica, $user, $parrocchia)";
    $result = mysqli_query($connection, $query);
    if (!$result)
        return 0;
    $result2 = mysqli_query($connection, "SELECT LAST_INSERT_ID() AS id");
    if ($result2)
    {
        if ($row = $result2->fetch_assoc())
        {
            if (isset($row["id"]))
                return (int)$row["id"];
        }
    }
    return 0;
}
function cambia_parrocchia_staff($connection, int $staff, int $parrocchia)
{
    if (!$connection || $staff == 0 || $parrocchia == 0)
        return false;
    $query = "UPDATE staffisti SET parrocchia = $parrocchia WHERE id = $staff";
    return (bool)mysqli_query($connection, $query);
}
function partecipa_staff($connection, int $staff, int $edizione, string $maglia, $commissioni, bool $is_referente = false)
{
    if (!$connection)
        return false;
    $maglia_sana = sql_sanitize($maglia);
    $query = "CALL PartecipaStaff($staff, $edizione, '$maglia_sana', '";
    for ($i = 0; $i < count($commissioni); $i++)
    {
        $commissione = $commissioni[$i];
        $query .= "$commissione";
        if ($i < count($commissioni) - 1)
        {
            $query .= ",";
        }
    }
    $query .= "', ";
    if ($is_referente) {
        $query .= "1";
    } else {
        $query .= "0";
    }
    $query .= ");";
    $result = (bool)mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return $result;
}
class staff_data
{
    public string $commissioni = "";
    public string $maglia = "";
    public string $parrocchia = "";
    public int $id_parrocchia = 0;
    public bool $is_referente = false;
    public string $cf = "";
    public function is_subscribed():bool
    {
        return $this->maglia != "Non scelta" && $this->maglia != "";
    }
    public function valid_cf():bool
    {
        return isset($this->cf) && !is_array($this->cf) && strlen($this->cf);
    }
    public function is_in(string $commissione): bool
    {
        if (!$commissione || !$this->commissioni)
            return false;
        $comm = explode(",", $this->commissioni);
        for ($i = 0; $i < count($comm); $i++)
        {
            if (strtolower($comm[$i]) == strtolower($commissione))
                return true;
        }
        return false;
    }
};
function getCurrentYearStaffData($connection, int $staff)
{
    if (!$connection || $staff == 0)
    {
        return new staff_data();
    }
    $query = "CALL StaffData($staff, YEAR(CURRENT_DATE))";
    $result = mysqli_query($connection, $query);
    $data = new staff_data();
    if ($result)
    {
        if ($row = $result->fetch_assoc())
        {
            $data->commissioni = $row["commissioni"];
            $data->parrocchia = $row["parrocchia"];
            $data->id_parrocchia = (int)$row["id_parrocchia"];
            $data->maglia = $row["maglia"];
            $data->cf = $row["cf"];
            $data->is_referente = (bool)$row["referente"];
        }
        $result->close();
    }
    mysqli_next_result($connection);
    return $data;
}