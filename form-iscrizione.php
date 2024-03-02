<?php
try {
    if (isset($_GET["edizione"]) && !is_array($_GET["edizione"]))
    {
        $year = (int)$_GET["edizione"];
        if ($year != (int)date("Y"))
        {
            throw new Exception("Invalid year");
        }
    }
} catch (Exception $ex)
{
    if (!isset($_COOKIE["esit"]) || $_COOKIE["esit"] == "")
    {
        header("Location: ../index.php");
        exit;
    }
}
if (!isset($year))
    $year = (int)date("Y");
include "./load/config.php";
include "./load/basic_functions.php";
if (isset($_COOKIE["id_anagrafica"]))
{
    our_delete_cookie("id_anagrafica");
}
if (isset($_COOKIE["login_forward"]))
{
    our_delete_cookie("login_forward");
}
if (isset($_COOKIE["success"]))
{
    our_delete_cookie("success");
}
if (isset($_COOKIE["form"]))
{
    our_delete_cookie("form");
}
//Form is open:
//Crea anagrafica e basta
if (!isset($_COOKIE["esit"]) || $_COOKIE["esit"] == "")
{
    header("Location: ./staff/iscrizioni/crea-anagrafica.php?form=$year&success=../../form-iscrizione");
    exit;
}
$cookie = $_COOKIE["esit"];
our_delete_cookie("esit");
?>
<!DOCTYPE html>
<html lang="it-it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" value="noindex,nofollow">
    <meta name="author" content="Riccardo Ciucci">
    <title>
        Esito registrazione dati | Amichiamoci
    </title>
	<meta name="description" content="Form di inserimento dati personali | Amichiamoci">

	<meta property="og:title" content="Esito Registrazione | Amichiamoci">
	<meta property="og:type" content="website">
	<meta property="og:url" content="https://www.amichiamoci.it/form-iscrizione.php">
	<meta property="og:description" content="Form di inserimento dati personali | Amichiamoci">
	<meta property="og:image" content="../assets/icons/favicon.png">

	<link rel="icon" href="../assets/icons/favicon.png">
	<link rel="apple-touch-icon" href="../assets/icons/favicon.png">
</head>
<body>
    <h1 style="display:none">
        Esito registrazione dati personali
    </h1>
    <h3>
        <?= acc($cookie) ?>
    </h3>
    <p>
        Puoi chiudere questa pagina.
        <br>
        Se hai specificato una email, 
        dovresti ricevere a breve un riepilogo dei dati inseriti, 
        controlla nello spam prima che venga cancellato!
        <br>
        <?php 
            if (isset($_GET["id"])) {
                echo "Codice dei dati inseriti: <output>" . $_GET["id"] . "</output>";
            }
        ?>
    </p>
</body>
</html>
