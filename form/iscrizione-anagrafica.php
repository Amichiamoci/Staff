<?php
    $hide_share_link = true;
    $is_extern = true;
    $year = (int)date("Y");

    require_once "../load/db_manager.php";
    
    $edizione = Edizione::FromYear($connection, $year);
    if (!isset($edizione) || !$edizione->IscrizioniAperte())
    {
        header("Location: ./index.php");
        exit;
    }
    if (!Cookie::Exists("id_anagrafica"))
    {
        header("Location: ./index.php");
        exit;
    }
    $id_anagrafica = (int)Cookie::Get("id_anagrafica");
    $errore = "";

    $iscrizione = new Iscrizione(null, null, null, null, $edizione->year, $edizione->id, null, null);
    if (
        isset($_POST["iscrivi_submit"]) &&
        isset($_POST["parrocchia"]) && ctype_digit($_POST["parrocchia"]) &&
        isset($_POST["maglia"]) && is_string($_POST["maglia"]) && strlen($_POST["maglia"]) > 0
    ) {
        $iscrizione->id_parrocchia = (int)$_POST["parrocchia"];
        $iscrizione->taglia = $_POST["maglia"];
        if (Iscrizione::Exists($connection, $id_anagrafica, $edizione->id))
        {
            // E' gia' iscritto
            header("Location: ./index.php");
            exit;
        }
        $dati_anagrafici = Anagrafica::Load($connection, $id_anagrafica);
        if (!isset($dati_anagrafici) || $dati_anagrafici->eta < 18)
        {
            // id fasullo, non nel db
            header("Location: ./index.php");
            exit;
        }

        $nome_file = 
            "/certificati/$edizione->year" . 
            "_certificato_" . 
            $dati_anagrafici->nome . " " . $dati_anagrafici->cognome;

        //Upload file certificato
        if (
            isset($_FILES["certificato"]) && 
            !empty($_FILES["certificato"]) &&
            is_uploaded_file($_FILES["certificato"]['tmp_name'])
        ) {
            if (!upload_file($_FILES["certificato"], $nome_file, $errore))
            {
                //Abbiamo il file ma non riusciamo a salvarlo
                $errore = "Impossibile uploadare il file certificato. Riprova pi&ugrave; tardi.<br>$errore"; 
                return;
            }
            //File salvato correttamente
        } else {
            //La form non contiene file, il file sara' aggiunto in un secondo momento
            $nome_file = null;
        }

        if (!Iscrizione::Create(
            $connection, 
            $id_anagrafica, 
            $iscrizione->id_tutore, 
            $nome_file, 
            $iscrizione->id_parrocchia, 
            $iscrizione->taglia, 
            $edizione->id))
        {
            $errore = "Impossibile inserire iscrizione nel DB!";
            //Riempire la form per far ritentare piu facilemnte l'utente
        } else {
            if (strlen($nome_file) > 0)
            {
                $testo = "$nome $cognome, sei iscritto correttamente ad Amichiamoci $edizione->year";
            } else {
                $testo = "$nome $cognome, sei iscritto SENZA CERTIFICATO ad Amichiamoci $edizione->year";
            }
            Cookie::Set("esit", $testo, 10);
            try {
                $a = Anagrafica::Load($connection, $id_anagrafica);
                if (isset($a) && !empty($a->email))
                {
                    Email::Send(
                        $a->email, 
                        "Iscrizione Amichiamoci $edizione->year",
                        "Ciao " . $testo . 
                        "<br><br><small>Ti preghiamo di non rispondere a questa email</small>");
                }
            } catch (Exception $ex) { }
            header("Location: ./iscrizione-completata.php");
            exit;
        }
    }
    $lista_parrocchie = Parrocchia::GetAll($connection);
    $staff_possibili = StaffBase::All($connection);
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
    <link rel="canonical" href="./index.php">
	<meta name="description" content="Form di inserimento dati personali | Amichiamoci">

	<meta property="og:title" content="Inserimento dati personali | Amichiamoci">
	<meta property="og:type" content="website">
	<meta property="og:url" content="./index.php">
	<meta property="og:description" content="Form di inserimento dati personali | Amichiamoci">
	<meta property="og:image" content="../assets/favicon.png">

	<link rel="icon" href="../assets/favicon.png">
	<link rel="apple-touch-icon" href="../assets/favicon.png">
    <title>
        Amichiamoci | Completa iscrizione
    </title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<?php include "../parts/edition.php"; ?>

<!-- Form ----------------------------------------------------------------- -->

<section class="full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id_anagrafica ?>">

                <?php if (isset($errore) && strlen($errore) > 0) { ?>
                    <p class="error">
                        <strong><?= $errore ?></strong>
                    </p>
                <?php } ?>

                <div class="input-box flex v-center wrap">

                    <label for="maglia">Taglia maglietta</label>
                    <select name="maglia" id="maglia" required>
                        <?php
                            $taglie = Maglie::All();
                            foreach ($taglie as $taglia)
                            {
                                if ($taglia == $iscrizione->taglia)
                                {
                                    echo "<option value='$taglia' selected='selected'>$taglia</option>\n";
                                } else {
                                    echo "<option value='$taglia'>$taglia</option>\n";
                                }
                            }
                        ?>
                    </select>

                    <label for="parrocchia">Parrocchia</label>
                    <select name="parrocchia" id="parrocchia" required>
                        <?php
                            $predefined_parrocchia = $iscrizione->id_parrocchia;
                            if ($predefined_parrocchia === 0 && isset($dati_staff))
                            {
                                $predefined_parrocchia = $dati_staff->id_parrocchia;
                            }
                            foreach ($lista_parrocchie as $parr)
                            {
                                $label = htmlspecialchars($parr->nome);
                                $id = $parr->id;
                                if ($id === $predefined_parrocchia)
                                {
                                    echo "<option value='$id' selected='selected'>$label</option>\n";
                                } else {
                                    echo "<option value='$id'>$label</option>\n";
                                }
                            }
                        ?>
                    </select>

                    <label for="certificato">Certificato</label>
                    <input type="file" name="certificato" id="certificato" accept="<?= join(", ", ALLOWED_EXT_DOTS) ?>" />
                </div>

                <input class="button rounded" type="submit" name="iscrivi_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>