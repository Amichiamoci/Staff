<?php
    $is_extern = true;
    if (!(
        (isset($_GET["year"]) && ctype_digit($_GET["year"])) || 
        (isset($_POST["year"]) && ctype_digit($_POST["year"]))
    )) {
        header("Location: ./index.php");
        exit;
    }
    require_once "../load/db_manager.php";
    $year = (isset($_GET["year"]) && ctype_digit($_GET["year"])) ? $_GET["year"] : $_POST["year"];
    $year = (int)$year;
    $edizione = Edizione::FromYear($connection, $year);
    if (!isset($edizione) || !$edizione->IscrizioniAperte())
    {
        header("Location: ./index.php");
        exit;
    }

    if (
        isset($_POST["anagrafica_submit"]) &&
        isset($_POST["nome"]) && is_string($_POST["nome"]) &&
        isset($_POST["cognome"]) && is_string($_POST["cognome"]) &&
        isset($_POST["email"]) && is_string($_POST["email"]) &&
        isset($_POST["cf"]) && is_string($_POST["cf"]) &&
        isset($_POST["doc_type"]) && ctype_digit($_POST["doc_type"]) &&
        isset($_POST["doc_code"]) && is_string($_POST["doc_code"]) &&
        isset($_POST["doc_expires"]) && is_string($_POST["doc_expires"]))
    {
        //Dati form
        $nome = file_remove_characters($_POST["nome"]);
        $nome = string_capitalize_words($nome);

        $cognome = file_remove_characters($_POST["cognome"]);
        $cognome = string_capitalize_words($cognome);

        $nome_file = "/documenti/documento $nome $cognome self";
        $nome_file = file_spaces_to_underscores($nome_file);
        
        $compleanno = $_POST["compleanno"];

        $provenienza = $_POST["provenienza"];

        $tel = isset($_POST["tel"]) && is_string($_POST["tel"]) ? $_POST["tel"] : null;

        $email = $_POST["email"];

        $cf = $_POST["cf"];
        
        $doc_type = (int)$_POST["doc_type"];

        $doc_code = $_POST["doc_code"];

        $doc_expires = $_POST["doc_expires"];

        if (isset($_FILES["doc_file"]) &&
            upload_file($_FILES["doc_file"], $nome_file, $errore))
        {

            $id_anagrafica = Anagrafica::Create(
                $connection, 
                $nome, $cognome, 
                $compleanno, $provenienza, $tel, 
                $email, $cf, $doc_type, 
                $doc_code, $doc_expires, 
                $nome_file, $is_extern);
            if ($id_anagrafica === 0)
            {
                $errore = "<strong>Impossibile inserire anagrafica nel sistema!</strong><br>Una causa pu&ograve; essere che sei gi&agrave; stato registrato.";
            } else {
                $nome = htmlspecialchars($nome);
                $cognome = htmlspecialchars($cognome);
                $tel = isset($tel) ? htmlspecialchars($tel) : "";
                $email = strtolower(htmlspecialchars($email));
                $cf = htmlspecialchars(strtoupper($cf));
                $provenienza = htmlspecialchars($provenienza);
                $doc_code = strtoupper(htmlspecialchars($doc_code));
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
                Email::Send($email, $subject, $text, $connection);
                Cookie::Set("id_anagrafica", "$id_anagrafica", 3600 * 2);
                Cookie::Set("esit", "Dati inseriti correttamente.", 3600);
                $url = "../index.php";
                if (isset($_COOKIE["success"]))
                {
                    $url = $_COOKIE["success"];
                    Cookie::Delete("success");
                }
                header("Location: $url");
                exit;
            }
        }
        $anagrafica = new Anagrafica(
            $id_anagrafica, 
            $nome,
            $cognome,
            null);
        $anagrafica->compleanno = $compleanno;
        $anagrafica->proveninenza = $provenienza;
        $anagrafica->telefono = $tel;
        $anagrafica->email = $email;
        $anagrafica->cf = $cf;
        $anagrafica->doc_code = $doc_code;
        $anagrafica->doc_expires = $doc_expires;
    }

?>
<!DOCTYPE html>
<html lang="it-it">
<head>
    <?php include "../parts/head.php"; ?>
    <link rel="canonical" href="./form/index.php">
	<meta name="description" content="Form di inserimento dati personali | Amichiamoci">

	<meta property="og:title" content="Inserimento dati personali | Amichiamoci">
	<meta property="og:type" content="website">
	<meta property="og:url" content="./form/index.php">
	<meta property="og:description" content="Form di inserimento dati personali | Amichiamoci">
	<meta property="og:image" content="../assets/favicon.png">

	<link rel="icon" href="../assets/favicon.png">
	<link rel="apple-touch-icon" href="../assets/favicon.png">
    <title>
        Amichiamoci | Invia i tuoi dati
    </title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

    <?php include "../parts/edition.php"; ?>
    <!-- Form ----------------------------------------------------------------- -->
    <section class="flex center" style="margin-block: 0;">
        <?php include "./anagrafica.php"; ?>
    </section>
</div>
<footer>
    <p class="text" style="text-align: center; user-select: none;">
        Copyright &copy; Amichiamoci <?= date("Y") ?><br>
        Gli autori del presente form possono essere contattati alla mail 
        <a href="mailto:<?= CONTACT_EMAIL ?>" class="link"><?= CONTACT_EMAIL ?></a>
    </p>
</footer>
</body>
</html>





    