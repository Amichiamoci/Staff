<?php

    //Iscrizione da aggiornare
    $cod_iscrizione = 0;
    if (isset($_GET["iscrizione"]))
    {
        $cod_iscrizione = (int)$_GET["iscrizione"];
    }
    if (isset($_POST["cod_iscrizione"]))
    {
        $cod_iscrizione = (int)$_POST["cod_iscrizione"];
    }

    //Persona di iscrivere
    if (isset($_GET["id"]))
    {
        $id_anagrafica = (int)$_GET["id"];
    }
    if (isset($_POST["id"]))
    {
        $id_anagrafica = (int)$_POST["id"];
    }
    if (!isset($id_anagrafica) && $cod_iscrizione === 0)
    {
        header("Location: index.php");
        exit;
    }
    
    include "../../check_login.php";

    
    if (User::$Current->staff_id === 0 && !User::$Current->is_admin) {
        header("Location: ../../index.php");
        exit;
    }
    $dati_staff = Staff::Get($connection, User::$Current->staff_id);
    if ((!$dati_staff->is_subscribed() || !$dati_staff->is_referente) && !User::$Current->is_admin)
    {
        header("Location: ../index.php");
        exit;
    }
    $edizione = Edizione::Current($connection);
    function addDot($str)
    {
        return ".$str";
    }
    $allowed_ext = array(
        "jpg",        "jpeg",       "gif",
        "png",        "bmp",        "avif",
        "tif",        "tiff",       "webp",
        "heic",       "heif",       "pdf",        
        "doc",        "docx",       "ppt",
        "pptx");
    $file_accept = join(", ", array_map("addDot", $allowed_ext));
    $errore = "";
    if ($cod_iscrizione !== 0)
    {
        $iscrizione = Iscrizione::Load($connection, $cod_iscrizione);
    }
    function form()
    {
        global $connection;
        global $id_anagrafica;
        global $edizione;
        global $cod_iscrizione;
        global $iscrizione;
        global $allowed_ext;

        if (Iscrizione::Exists($connection, $id_anagrafica, $edizione->id) && $cod_iscrizione === 0)
        {
            $errore = acc($iscrizione->nome). " &egrave; gi&agrave; iscritto ad Amichiamoci per questa edizione!";
            return;
        }
        
        $nome_file = "upload/certificati/" . $edizione->year . 
                "_certificato_" . 
                str_replace(array(" ", ".", ",", ":", ";", "<", ">", "?", "'", "\"", "\\"), "_", $iscrizione->nome);
        //Upload file certificato
        if (isset($_FILES["certificato"]) && !empty($_FILES["certificato"]) &&
            is_uploaded_file($_FILES["certificato"]['tmp_name']))
        {
            if (!upload_file($_FILES["certificato"], $allowed_ext, $nome_file, $errore))
            {
                //Abbiamo il file ma non riusciamo a salvarlo
                $errore = "Impossibile uploadare il file certificato. Riprova pi&ugrave; tardi.<br>$errore"; 
                return;
            }
            //File salvato correttamente
        } else {
            //La form non contiene file, il file sara' aggiunto in un secondo momento
            $nome_file = "";
        }

        //Se era presente il certificato e' stato inserito/aggiornato
        if ($cod_iscrizione !== 0)
        {
            if (strlen($nome_file) > 0)
            {
                //Certificato da aggiornare
                if (!Iscrizione::UpdateCertificato($connection, $cod_iscrizione, $nome_file))
                {
                    $errore = "Impossibile inserire certificato!";
                    return;
                    //Riempire la form per far ritentare piu facilemnte l'utente
                }
            }
            if (!$iscrizione->Update($connection))
            {
                $errore = "Impossibile aggiornare iscrizione!";
                //Riempire la form per far ritentare piu facilemnte l'utente
            }
            if ($errore == "")
            {
                header("Location: index.php");
                exit;
            }
            return;
        }
        
        //Voglio iscrivere l'utente
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
            $nome_iscrizione = acc($iscrizione->nome);
            if (strlen($nome_file) > 0)
            {
                Cookie::Set("esit", "$nome_iscrizione iscritto correttamente ad Amichiamoci " . $edizione->year . ".", 10);
            } else {
                Cookie::Set("esit", "$nome_iscrizione iscritto SENZA CERTIFICATO ad Amichiamoci " . $edizione->year . ".", 10);
            }
            header("Location: index.php");
            exit;
        }
    }
    if (isset($_POST["iscrivi_submit"]))
    {
        if (isset($_POST["tutore"]))
        {
            $iscrizione->id_tutore = (int)$_POST["tutore"];
        }
        if (isset($_POST["parrocchia"]))
        {
            $iscrizione->id_parrocchia = (int)$_POST["parrocchia"];
        }
        if (isset($_POST["maglia"]))
        {
            $iscrizione->taglia = $_POST["maglia"];
        }
        form();
    }
    $lista_parrocchie = Parrocchia::GetAll($connection);
    $staff_possibili = StaffBase::All($connection);
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Iscrizione</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Form ----------------------------------------------------------------- -->

<section id="send-message" class="send-message full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="iscrivi.php" method="post" class="login-form" enctype="multipart/form-data">
                <?php if ($cod_iscrizione === 0) { ?>
                    <h3>
                        Stai iscrivendo <?= acc($iscrizione->nome) ?> ad Amichiamoci <?= $edizione->year ?>
                    </h3>
                    <p class="text">
                        Se vuoi iscrivere un'altra persona (la cui anagrafica &egrave; da creare)
                        <a class="link" href="./crea-anagrafica.php?success=iscrivi"> clicca qui </a><br>
                        Se vuoi iscrivere un'altra persona, la cui anagrafica &egrave; gi&agrave; nel DB, 
                        <a class="link" href="./index.php">clicca qui</a>
                    </p>
                <?php } else { ?>
                    <h3>
                        Stai modificando l'iscrizione di <?= acc($iscrizione->nome) ?> per il <?= $edizione->year ?>
                    </h3>
                <?php } ?>

                <input type="hidden" name="id" value="<?= $id_anagrafica ?>">
                <input type="hidden" name="cod_iscrizione" value="<?= $cod_iscrizione ?>">

                <?php
                if ($errore != "")
                    echo "<p class='error'>$errore</p>"
                ?>

                <div class="input-box flex v-center wrap">

                    <label for="maglia">Taglia maglietta</label>
                    <select name="maglia" id="maglia" required>
                        <?php
                            $taglie = array("XS", "S", "M", "L", "XL", "XXL", "3XL");
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
                            if ($predefined_parrocchia === 0)
                            {
                                $predefined_parrocchia = $dati_staff->id_parrocchia;
                            }
                            foreach ($lista_parrocchie as $parr)
                            {
                                $label = acc($parr->nome);
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

                    <label for="tutore">Tutore</label>
                    <select name="tutore" required>
                        <option value="0">Nessun tutore</option>
                        <?php
                            $anagrafiche = Anagrafica::GetAll($connection, function (Anagrafica $a){
                                return $a->eta >= 18;
                            });
                            foreach ($anagrafiche as $a)
                            {
                                $label = acc($a->nome . " " . $a->cognome);
                                $id = $a->id;
                                if ($id === $iscrizione->id_tutore)
                                {
                                    echo "<option value='$id' selected='selected'>$label</option>\n";
                                } else {
                                    echo "<option value='$id'>$label</option>\n";
                                }
                            }
                        ?>
                    </select>
                    <label for="certificato">Certificato</label>
                    <input type="file" name="certificato" id="certificato" accept="<?=$file_accept?>" />
                </div>
                <input class="button rounded" type="submit" name="iscrivi_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>