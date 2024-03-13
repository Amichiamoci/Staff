<?php
    $is_extern = true;
    $hide_share_link = true;
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


    $cf = "";
    if (isset($_POST["cf"]) && is_string($_POST["cf"]))
    {
        $cf = $_POST["cf"];
        require_once "../load/models/token.php"; 
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
        Amichiamoci | Ottieni codice
    </title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

    <?php include "../parts/edition.php"; ?>

    <div class="grid">
         <div class="column col-100">
            <form method="post">
                <p class="text">
                    Compila questo form solo se hai gi&agrave; partecipato ad Amichiamoci dal 2023 od oltre
                </p>
                <input type="hidden" name="year" value="<? $year ?>">                
                <div class="input-box flex v-center wrap">
                    <label for="cf">
                        Codice fiscale
                    </label>
                    <input name="cf" id="cf" value="<?= $cf ?>" title="Il tuo codice fiscale">
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>





    