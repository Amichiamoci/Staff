<?php
//
//
//   Cool lists
//
//
function getStaffList(mysqli $connection, int $year = 0, bool $include_all = false) 
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
            $parrocchia       = htmlspecialchars($row["parrocchia"]);

            $data             = strtotime($data_nascita);
            $data_nascita     = date("d/m/Y", $data);

            $commissioni = null;
            if (isset($row["lista_commissioni"]))
                $commissioni      = htmlspecialchars($row["lista_commissioni"]);

            $staff_list .= "<table>";
            $staff_list .= "<thead>";
            $staff_list .= "<tr>";
            $staff_list .= "<th data-label='Nome'>$nome $cognome</th>";
            $staff_list .= "</tr>";
            $staff_list .= "</thead>";
            $staff_list .= "<tbody>";
            $staff_list .= "<tr>";
            if (isset($row["eta"]))
            {
                $eta = (int)$row["eta"];
                $staff_list .= "<td data-label='Et&agrave;'>$eta</td>";
            }
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
                $partecipazioni = htmlspecialchars($row["partecipazioni"]);
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

function getAnagraficheList(mysqli $connection, $year = null, int $id_parrocchia = 0, $add_link = true)
{
    $year_p = "NULL";
    if (isset($year))
    {
        if ($year === "0")
        {
            $year_p = "YEAR(CURRENT_DATE)";
        } else {
            $year_p = $connection->real_escape_string($year);
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
        $str .= "<th data-label='Nome'>" . htmlspecialchars($row["nome"]) . "</th>\n";
        $str .= "<th data-label='Cognome'>" . htmlspecialchars($row["cognome"]) . "</th>\n";
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
        $label = htmlspecialchars($row["label"]);
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
            $str .= "<td data-label='Parrocchia'>" . $row["anno"]. ": Iscritto per " . htmlspecialchars($row["parrocchia"]) ."</td>\n";
            
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
            $tutore = htmlspecialchars($row["tutore"]);
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

function getNonPartecipantiList(mysqli $connection, $year = null)
{
    $year_p = "NULL";
    if (isset($year))
    {
        if ($year === "0")
        {
            $year_p = "YEAR(CURRENT_DATE)";
        } else {
            $year_p = $connection->real_escape_string($year);
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
        $str .= "<th data-label='Nome'>" . htmlspecialchars($row["nome"]) . "</th>\n";
        $str .= "<th data-label='Cognome'>" . htmlspecialchars($row["cognome"]) . "</th>\n";
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
        $label = htmlspecialchars($row["label"]);
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
function getIscrizioniSbagliate(mysqli $connection, $year = null, int $id_parrocchia = 0, $add_link = true)
{
    $year_p = "NULL";
    if (isset($year))
    {
        if ($year === "0")
        {
            $year_p = "YEAR(CURRENT_DATE)";
        } else {
            $year_p = $connection->real_escape_string($year);
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
        $str .= "<th data-label='Chi'>" . htmlspecialchars($row["chi"]) . "</th>\n";
        $str .= "</tr>\n</thead>\n<tbody>\n<tr>\n";
        if (isset($row["email"]))
        {
            $str .= "<td data-label='Email'>" . htmlspecialchars($row["email"]) . "</td>\n";
        }
        if (isset($row["email_verify"]))
        {
            $str .= "<td data-label='Email'>" . htmlspecialchars($row["email_verify"]) . "</td>\n";
        }
        if (isset($row["telefono"]))
        {
            $str .= "<td data-label='Telefono'>" . htmlspecialchars($row["telefono"]) . "</td>\n";
        }
        if (isset($row["doc_code"]))
        {
            $str .= "<td data-label='Codice Documento'>" . htmlspecialchars($row["doc_code"]) . "</td>\n";
        }
        if (isset($row["doc"]))
        {
            if ($add_link)
            {
                $str .= "<td data-label='Documento'><strong>" . htmlspecialchars($row["doc"]) . "</strong> <a href=\"./crea-anagrafica.php?cf=$cf\" class=\"link\">Inserisci</a></td>\n";
            } else {
                $str .= "<td data-label='Documento'>" . htmlspecialchars($row["doc"]) . "</td>\n";
            }
            $str .= "<td data-label='Documento'>" . htmlspecialchars($row["doc"]) . "</td>\n";
        }
        if (isset($row["scadenza"]))
        {
            $str .= "<td data-label='Scadenza Documento'>" . htmlspecialchars($row["scadenza"]) . "</td>\n";
        }
        if (isset($row["certificato"]))
        {
            if ($add_link)
            {
                $str .= "<td data-label='Certificato Medico'><strong>" . 
                    htmlspecialchars($row["certificato"]) . 
                    "</strong> <a href=\"./iscrivi.php?iscrizione=$iscrizione&id=$id\" class=\"link\">Inserisci</a></td>\n";
            } else {
                $str .= "<td data-label='Certificato'>" . htmlspecialchars($row["certificato"]) . "</td>\n";
            }
        }
        if (isset($row["tutore"]))
        {
            if ($add_link)
            {
                $str .= "<td data-label='Tutore'><strong>" . 
                    htmlspecialchars($row["tutore"]) . 
                    "</strong> <a href=\"./iscrivi.php?iscrizione=$iscrizione&id=$id\" class=\"link\">Inserisci/Cambia</a></td>\n";
            } else {
                $str .= "<td data-label='Tutore'>" . htmlspecialchars($row["tutore"]) . "</td>\n";
            }
            
        }
        if (isset($row["eta"]))
        {
            $str .= "<td data-label='Età'>" . htmlspecialchars($row["eta"]) . "</td>\n";
        }
        if (isset($row["maglia"]))
        {
            $str .= "<td data-label='Maglia'>" . htmlspecialchars($row["maglia"]) . "</td>\n";
        }
        $str .= "</tr>\n</tbody>\n</table>\n\n";
    }
    $result->close();
    mysqli_next_result($connection);
    $str .= "</div>";
    return $str;
}
