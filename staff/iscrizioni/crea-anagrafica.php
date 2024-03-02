<?php
    $is_extern = isset($_GET["form"]) || isset($_COOKIE["form"]);
    include "../../check_login.php";
    if ($is_extern)
    {
        Cookie::Set("form", date("Y"), 3600);
    }

    Cookie::DeleteIfExists("id_anagrafica");
    
    if (isset($_GET["success"]) && !is_array($_GET["success"]))
    {
        Cookie::Set("success", $_GET["success"], 3600);
    }

    $allowed_ext = array(
        "jpg",        "jpeg",        "gif",
        "png",        "bmp",        "avif",
        "tif",        "tiff",        "webp",
        "heic",        "heif",        "pdf",        
        "doc",        "docx",         "ppt",
        "pptx");
    function addDot($str)
    {
        return ".$str";
    }
    function PrettyNames($str)
    {
        return strtoupper(substr($str, 0, 1)) . strtolower(substr($str, 1));
    }
    function Capitalize($str)
    {
        $parts = explode(" ", $str);
        $parts = array_filter($parts, function (string $s){
            return strlen($s) > 0;
        });
        $parts = array_map("PrettyNames", $parts);
        return join(" ", $parts);
    }
    $file_accept = join(", ", array_map("addDot", $allowed_ext));
    $errore = "";
    $nome = "";
    $cognome = "";
    $compleanno = "";
    $provenienza = "";
    $tel = "";
    $email = "";
    $cf = "";
    $is_editing = false;
    if (isset($_GET["cf"]) && !is_array($_GET["cf"]))
    {
        $cf = $_GET["cf"];
        $is_editing = true;
    }
    $doc_type = 1;
    $doc_code = "";
    $doc_expires = "";
    if (isset($_POST["anagrafica_submit"]))
    {
        //Dati form
        $nome = sql_sanitize($_POST["nome"]);
        $nome = str_replace("/", "", $nome);
        $nome = Capitalize($nome);

        $cognome = sql_sanitize($_POST["cognome"]);
        $cognome = str_replace("/", "", $cognome);
        $cognome = Capitalize($cognome);

        if ($is_editing && $nome == "" && $cognome == "")
        {
            $query = "SELECT a.nome, a.cognome FROM anagrafiche a WHERE LOWER(a.codice_fiscale) = LOWER('$cf')";
            $res = mysqli_query($connection, $query);
            if ($res)
            {
                if ($row = $res->fetch_assoc())
                {
                    $nome = $row["nome"];
                    $cognome = $row["cognome"];
                }
            }
        }

        $nome_file = "upload/documenti/documento $nome $cognome";
        $nome_file = str_replace(
            array(" ", ".", ",", ":", ";", "<", ">", "?", "'", "\"", "\\"), "_", $nome_file);
        
        $compleanno = sql_sanitize($_POST["compleanno"]);

        $provenienza = sql_sanitize($_POST["provenienza"]);

        $tel = sql_sanitize($_POST["tel"]);

        $email = sql_sanitize($_POST["email"]);

        $cf = sql_sanitize($_POST["cf"]);
        
        if (isset($_POST["doc_type"]) && is_numeric($_POST["doc_type"]))
        {
            $doc_type = (int)$_POST["doc_type"];
        }

        $doc_code = sql_sanitize($_POST["doc_code"]);

        $doc_expires = sql_sanitize($_POST["doc_expires"]);

        if (isset($_FILES["doc_file"]) &&
            upload_file($_FILES["doc_file"], $allowed_ext, $nome_file, $errore))
        {

            $id_anagrafica = crea_anagrafica(
                $connection, $nome, $cognome, 
                $compleanno, $provenienza, $tel, 
                $email, $cf, $doc_type, 
                $doc_code, $doc_expires, 
                $nome_file, $is_extern);
            if ($id_anagrafica == 0)
            {
                $errore = "<strong>Impossibile inserire anagrafica nel sistema!</strong>";
                if ($is_extern)
                {
                    $errore .= "<br>Una causa pu&ograve; essere che sei gi&agrave; stato registrato."; 
                }
            } else {
                if ($email != "" && $is_extern)
                {
                    $nome = acc($nome);
                    $cognome = acc($cognome);
                    $tel = acc($tel);
                    $email = strtolower(acc($email));
                    $cf = strtoupper(acc($cf));
                    $provenienza = acc($provenienza);
                    $doc_code = strtoupper(acc($doc_code));
                    $text = 
                        "<p>\r\n" .
                        "   &Egrave; appena stato compilato il form di inserimento digitale dei dati di Amichiamoci.<br />\r\n" . 
                        "   I dati inseriti sono:\r\n" . 
                        "</p>\r\n" .
                        "<ul>\r\n" .
                        "   <li>Nome: $nome</li>\r\n".
                        "   <li>Cognome: $cognome</li>\r\n".
                        "   <li>Data di nascita: $compleanno</li>\r\n".
                        "   <li>Numero di telefono: <a href=\"tel:$tel\" class=\"link\">$tel</a></li>\r\n".
                        "   <li>Email: <a href=\"mailto:$email\" class=\"link\">$email</a></li>\r\n".
                        "   <li>Codice fiscale: $cf</li>\r\n".
                        "   <li>Luogo di nascita: $provenienza</li>\r\n".
                        "   <li>Codice del documento: $doc_code</li>\r\n".
                        "   <li>Data di scadenza del documento: $doc_expires</li>\r\n".
                        "   <li>Codice dei dati inseriti: $id_anagrafica</li>\r\n".
                        "</ul>\r\n" .
                        "<br />\r\n" .
                        "<p>\r\n" . 
                        "   Ricordiamo che per i minorenni questo stesso form deve essere compilato <strong>anche da un genitore</strong>.\r\n" .
                        "</p>\r\n" .
                        "<hr />\r\n" .
                        "Augurandovi una buona giornata vi preghiamo di non rispondere a questa email ma di conservarla.";
                    $subject = "Dati anagrafici inseriti correttamente";
                    send_email($email, $subject, $text, $connection);
                }
                Cookie::Set("id_anagrafica", "$id_anagrafica", 3600 * 2);
                Cookie::Set("esit", "Dati inseriti correttamente.", 3600);
                $url = "../index.php";
                if (isset($_COOKIE["success"]))
                {
                    $url = $_COOKIE["success"] . ".php?id=$id_anagrafica";
                    Cookie::Delete("success");
                }
                header("Location: $url");
                exit;
            }
        }
    }
?>
<!--
    Perché stai guardando qui?
    Non c'è nulla di interessante.
    Visto che hai tempo libero puoi andare al mare, uscire con gli amici, andare al cinema, sfrutta quest'occasione!
-->
<!DOCTYPE html>
<html lang="it-it">

<head>
    <?php include "../../parts/head.php";?>
    <?php if (!isset($is_extern) || !$is_extern) { ?>
	    <title>Amichiamoci | Crea Anagrafica</title>
    <?php } else { ?>
	    <title>Inserimento dati personali | Amichiamoci</title>
    <?php } ?>
    <link rel="canonical" href="<?= "$DOMAIN/admin/form-iscrizione.php" ?>">
	<meta name="description" content="Form di inserimento dati personali | Amichiamoci">

	<meta property="og:title" content="Inserimento dati personali | Amichiamoci">
	<meta property="og:type" content="website">
	<meta property="og:url" content="https://www.amichiamoci.it/admin/form-iscrizione.php">
	<meta property="og:description" content="Form di inserimento dati personali | Amichiamoci">
	<meta property="og:image" content="../../../assets/icons/favicon.png">

	<link rel="icon" href="../../../assets/icons/favicon.png">
	<link rel="apple-touch-icon" href="../../../assets/icons/favicon.png">
    <?php if ($is_extern || !$is_editing) { ?>
        <script src="../../assets/js/codice.fiscale.var.js" type="text/javascript" defer></script>
        <script src="../../assets/js/anagrafica.js" type="text/javascript" defer></script>
    <?php } ?>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Form ----------------------------------------------------------------- -->

<section class="flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="crea-anagrafica.php" method="post" enctype="multipart/form-data">
                
                <?php if ($is_extern) { ?>
                    <h3>Stai creando la tua scheda anagrafica</h3>
                    <h4>
                        Attenzione: il form utilizza dei cookie per funzionare internamente
                    </h4>
                    <h5>
                        Una volta conclusa l'operazione saranno tutti cancellati in automatico.
                    </h5>
                    <p class="text" style="text-align: justify;">
                        Una volta registrato i tuoi dati rimarranno permanentemente nel sistema, 
                        in modo che tu non abbia bisogno di compilare questo form pi&ugrave; volte.<br>
                        In caso tu abbia inserito i tuoi dati in passato non c'&egrave; necessit&agrave;
                        di compilare questo form: comunica semplicemente agli staffisti di riferimento
                        la tua volont&agrave; di partecipare a questa edizione, dando loro:
                    </p>
                    <ul>
                        <li>
                            Taglia della maglia
                        </li>
                        <li>
                            Soldi dell'iscrizione
                        </li>
                        <li>
                            Certificato medico almeno non agonistico (invia loro foto o pdf)
                        </li>
                        <li>
                            Lista delle attivit&agrave; a cui vuoi partecipare: Tornei, Maratona, etc.
                        </li>
                    </ul>
                <?php } else { ?>

                    <?php if ($anagrafica->staff_id != 0) { ?>
                        <h3>
                            Stai creando una scheda anagrafica
                        </h3>
                        <p class="text">
                            <strong>ATTENZIONE:</strong> In caso un utente col medesimo codice fiscale esista gi&agrave; nel sistema,
                            i dati parziali che inserirai sovrascriveranno quelli esistenti. Questa funzionalit&agrave;
                            &egrave; utile per aggiornare un documento scaduto o correggere eventuali dati scorretti.
                        </p>
                        <?php if (isset($is_editing) && $is_editing) { ?>
                            <h3>
                                Stai modificando i dati (gi&agrave;) presenti di
                                <output style="user-select: none; text-transform: uppercase">
                                    <?= $cf ?>
                                </output>
                            </h3>
                            <p class="text">
                                Non abusare di questa funzionalit&agrave;.<br>
                                Inserisci solo i dati che vuoi modificare, lascia gli altri bianchi e rimarranno invariati.
                            </p>
                        <?php } ?>
                    <?php } else { ?>
                        <h3>
                            <?= acc($anagrafica->username) ?>, stai creando la TUA scheda anagrafica
                        </h3>
                    <?php } ?>
                <?php } ?>
                <h4>
                    Altre due raccomandazioni
                </h4>
                <ul>
                    <li>
                        Il file che invii del documento deve avere sia fronte che retro,
                        consigliamo di fare una foto/scansione a entrambe le facce e di concatenarle in un unico WORD/PDF
                    </li>
                    <li>
                        <strong>
                            La tessera sanitaria non &egrave; un documento di riconoscimento!
                        </strong>
                    </li>
                </ul>
                <?php
                    if ($errore != "")
                        echo "<p class='error'>$errore</p>"
                ?>
                <div class="input-box flex v-center wrap">

                    <label for="nome">Nome</label>
                    <input type="text" <?= $is_editing ? "" : "required"?> name="nome" id="nome" value="<?= $nome ?>" placeholder="Mario">

                    <label for="cognome">Cognome</label>
                    <input type="text" <?= $is_editing ? "" : "required"?> name="cognome" id="cognome" value="<?= $cognome ?>" placeholder="Rossi">

                    <label for="compleanno">Data di nascita</label>
                    <input type="date" <?= $is_editing ? "" : "required"?> name="compleanno" id="compleanno" value="<?= $compleanno ?>">

                    <label for="provenienza">Luogo di nascita</label>
                    <input type="text" <?= $is_editing ? "" : "required"?> name="provenienza" value="<?= $provenienza ?>" id="provenienza"
                        list="suggerimenti-provenienza" pattern="[A-Za-z'\-\s]+,\s[A-Z]{2}"
                        title="Inserisci più informazioni che hai" placeholder="Comune, Codice Provincia (due lettere). Es: Livorno, LI">
                    <datalist id="suggerimenti-provenienza">

                        <optgroup label="Provincia di Livorno">
                            <option value="Livorno, LI">Livorno, LI</option>
                            <option value="Cecina, LI">Cecina, LI</option>
                            <option value="Collesalvetti, LI">Collesalvetti, LI</option>
                            <option value="Rosignano Marittimo, LI">Rosignano Marittimo, LI</option>
                            <option value="Castagneto Carducci, LI">Castagneto Carducci, LI</option>
                            <option value="Campiglia Marittima, LI">Campiglia Marittima, LI</option>
                            <option value="San Vincenzo, LI">San Vincenzo, LI</option>
                            <option value="Piombino, LI">Piombino, LI</option>
                            <option value="Portoferraio, LI">Portoferraio, LI</option>
                            <option value="Bibbona, LI">Bibbona, LI</option>
                        </optgroup>

                        <optgroup label="Provincia di Pisa">
                            <option value="Pisa, PI">Pisa, PI</option>
                            <option value="Cascina, PI">Cascina, PI</option>
                            <option value="San Giuliano Terme, PI">San Giuliano Terme, PI</option>
                            <option value="Pontedera, PI">Pontedera, PI</option>
                            <option value="San Miniato, PI">San Miniato, PI</option>
                            <option value="Ponsacco, PI">Ponsacco, PI</option>
                        </optgroup>
                        
                        <optgroup label="Provincia di Lucca">
                            <option value="Lucca, LU">Lucca, LU</option>
                            <option value="Viareggio, LU">Viareggio, LU</option>
                            <option value="Capannori, LU">Capannori, LU</option>
                            <option value="Forte dei Marmi, LU">Forte dei Marmi, LU</option>
                            <option value="Camaiore, LU">Camaiore, LU</option>
                            <option value="Pietrasanta, LU">Pietrasanta, LU</option>
                            <option value="Massarosa, LU">Massarosa, LU</option>
                            <option value="Altopascio, LU">Altopascio, LU</option>
                        </optgroup>

                        <optgroup label="Provincia di Massa-Carrara">
                            <option value="Massa, MS">Massa, MS</option>
                            <option value="Carrara, MS">Carrara, MS</option>
                            <option value="Aulla, MS">Aulla, MS</option>
                            <option value="Montignoso, MS">Montignoso, MS</option>
                            <option value="Fivizzano, MS">Fivizzano, MS</option>
                            <option value="Pontremoli, MS">Pontremoli, MS</option>
                        </optgroup>

                        <optgroup label="Altre province toscane">
                            <option value="Firenze, FI">Firenze, FI</option>
                            <option value="Arezzo, AR">Arezzo, AR</option>
                            <option value="Siena, SI">Siena, SI</option>
                            <option value="Prato, PO">Prato, PO</option>
                            <option value="Pistoia, PT">Pistoia, PT</option>
                            <option value="Grosseto, GR">Grosseto, GR</option>
                        </optgroup>
                        
                        <optgroup label="Capoluoghi Liguria">
                            <option value="Genova, GE">Genova, GE</option>
                            <option value="La Spezia, SP">La Spezia, SP</option>
                            <option value="Savona, SV">Savona, SV</option>
                            <option value="Imperia, IM">Imperia, IM</option>
                        </optgroup>

                        <optgroup label="Altre città popolose">
                            <option value="Roma, RM">Roma, RM</option>
                            <option value="Milano, MI">Milano, MI</option>
                            <option value="Napoli, NA">Napoli, NA</option>
                            <option value="Torino, TO">Torino, TO</option>
                            <option value="Palermo, PA">Palermo, PA</option>
                            <option value="Bologna, BO">Bologna, BO</option>
                            <option value="Bari, BA">Bari, BA</option>
                            <option value="Catania, CT">Catania, CT</option>
                            <option value="Verona, VR">Verona, VR</option>
                            <option value="Venezia, VE">Venezia, VE</option>
                            <option value="Messina, ME">Messina, ME</option>
                            <option value="Padova, PD">Padova, PD</option>
                            <option value="Trieste, TS">Trieste, TS</option>
                            <option value="Parma, PR">Parma, PR</option>
                            <option value="Brescia, BS">Brescia, BS</option>
                        </optgroup>
                    </datalist>

                    <label for="telefono">Telefono</label>
                    <input type="tel" name="tel" id="telefono" value="<?= $tel ?>"
                        title="Numero di telefono"
                        placeholder="3141592653" <?= ($is_extern ? "required" : "") ?>>

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?= $email ?>"
                        title="Email qui"
                        placeholder="esempio@mail.com">

                    <label for="cf" title="Codice fiscale">Codice Fiscale</label>
                    <input type="text" name="cf" id="cf" required value="<?= $cf ?>" 
                        pattern="[a-zA-Z]{6}[0-9]{2}[a-zA-Z][0-9]{2}[a-zA-Z][0-9]{3}[a-zA-Z]"
                        title="Codice fiscale corretto"
                        placeholder="aaaaaa11a11a111a">

                    <label for="doc_type">Tipo documento di RICONOSCIMENTO</label>
                    <select name="doc_type" id="doc_type" required 
                        value="<?php echo $doc_type ?>" title="La tessera sanitaria non va bene">
                        <optgroup label="Documenti di riconoscimento (hanno la foto)">
                            <?php
                                $tipi = getTipiDocumento($connection);
                                foreach ($tipi as $tipo)
                                {
                                    $label = acc($tipo->label);
                                    if ($tipo->id == $doc_type)
                                    {
                                        echo "<option value='$tipo->id' selected='selected'>$label</option>\n";
                                    } else {
                                        echo "<option value='$tipo->id'>$label</option>\n";
                                    }
                                }
                            ?>
                        </optgroup>
                        <optgroup label="La tessera sanitaria non va bene"></optgroup>
                    </select>

                    <label for="doc_code">Codice documento</label>
                    <input type="text" name="doc_code" id="doc_code" required value="<?= $doc_code ?>">

                    <label for="doc_expires">Scadenza documento</label>
                    <input type="date" name="doc_expires" id="doc_doc_expires" required value="<?= $doc_expires ?>">
                    
                    <label for="doc_file">Documento (sia fronte che retro)</label>
                    <input type="file" name="doc_file" id="doc_file" required accept="<?=$file_accept?>" />
                </div>
                    <?php if ($is_extern) { ?>
                        <input name="extern" type="hidden" value="Esterno">
                        <p class="text" style="text-align:justify;">
                            Premendo "conferma" autorizzi l'Associazione Amichiamoci A.S.D. (Diocesi di Livorno)
                            a memorizzare i tuoi dati. Tali dati sono tenuti solo a scopo assicurativo.<br>
                            Autorizzi inoltre l'associazione a mostrare il tuo nome e cognome nelle varie classifiche 
                            (parziali e finali) della manifestazione.<br>
                            I dati che stai immettendo possono essere rimossi in futuro scrivendo a 
                            <a href="mailto:info@amichiamoci.it" class="link">info@amichiamoci.it</a>.
                        </p>
                    <?php } ?>

                <p class="text" id="codice-fiscale-check-result" style="font-weight: bold"></p>
                <input class="button rounded" type="submit" id="submit-btn" name="anagrafica_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>
<footer>
    <p class="text" style="text-align: center; user-select: none;">
        Copyright &copy; Amichiamoci <?= date("Y") ?><br>
        Gli autori del presente form possono essere contattati alla mail <a href="mailto:dev@amichiamoci.it" class="link">dev@amichiamoci.it</a>
    </p>
</footer>
</body>

</html>