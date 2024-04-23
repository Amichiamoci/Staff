<?php

require_once "../load/basic_functions.php";

if (!Cookie::Exists("esit")) {
    header("Location: ./index.php");
    exit;
}
$esit = htmlspecialchars(Cookie::Get("esit"));

?>
<!DOCTYPE html>
<html lang="it-it">
<head>
    <?php include "../parts/head.php"; ?>
    <link rel="canonical" href="./index.php">
	<meta name="description" content="Iscrizione completata | Amichiamoci">

	<meta property="og:title" content="Inserimento dati personali | Amichiamoci">
	<meta property="og:type" content="website">
	<meta property="og:url" content="./index.php">
	<meta property="og:description" content="Iscrizione completata | Amichiamoci">
	<meta property="og:image" content="../assets/favicon.png">

	<link rel="icon" href="../assets/favicon.png">
	<link rel="apple-touch-icon" href="../assets/favicon.png">
    <title>
        Amichiamoci | Iscrizione completata
    </title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

    <?php include "../parts/edition.php"; ?>

    <h1>
        Esito iscrizione
    </h1>
    <p>
        <?= $esit ?>
    </p>
</div>
</body>
</html>
