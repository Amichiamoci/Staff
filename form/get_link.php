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
    require_once "../load/models/token.php"; 
    $cf = trim($_POST["cf"]);
    $anagrafica = Anagrafica::FromCF($connection, $cf);
    if (isset($anagrafica) && !empty($anagrafica->email) /*&& $anagrafica->eta >= 18*/)
    {
        $expire = date_add(new DateTime(), new DateInterval("P7D"));

        $token = Token::Generate($connection, $edizione->id, $anagrafica->id, $expire->format("Y-m-d"));
        if (isset($token))
        {
            $url = ADMIN_URL . "/form/token.php?val=" . $token->val;
            $testo = "Ciao $anagrafica->nome,<br>\nClicca questo link per completare l'iscrizione ad Amichiamoci $year:\n<br><br>" . 
            "<a href=\"" . $url . "\" target=\"_blank\">" . $url . "</a><br>Ti sar&agrave; possibile cliccare su questo link una volta sola.<br>\n" .
            "Il link scadr&agrave; in 6 giorni.<br>\n" .
            "Se non sei stato tu ad avviare questa procedura segnalacelo a <a href=\"mailto:" . CONTACT_EMAIL . "\">" . CONTACT_EMAIL . "</a><br>" .
            "<small>Ti preghiamo di non rispondere a questa email</small>";
            if (
                !Email::Send(
                    $anagrafica->email,
                    "Conferma iscrizione $year",
                    $testo,
                    $connection
                )) {
                $errore = "Impossibile inviare l'email!";
            } else {
                $errore = "Email inviata, controlla lo SPAM";
            }
        } else {
            $errore = "Qualcosa è anadto storto";
        }
    } else {
        $errore = "Dati non trovati o privi di email." .
            "Se hai già compilato il form di iscrizione online in passato, senza fornire però un'email, " . 
            "contatta uno staffista per iscriverti";
    }
}
    
?>
<!DOCTYPE html>
<html lang="it-it">
<head>
    <?php include "../parts/head.php"; ?>
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
                <?php if (isset($errore)) { ?>
                    <p class="text">
                        &nbsp;&darr;<br>
                        <strong>
                            <?= htmlspecialchars($errore) ?>
                        </strong>
                        <br>&nbsp;&uarr;
                    </p>
                <?php } ?>

                <p class="text">
                    Compila questo form solo se hai gi&agrave; partecipato ad Amichiamoci dal 2023 od oltre
                </p>
                <input type="hidden" name="year" value="<? $year ?>">                
                <div class="input-box flex v-center wrap">
                    <label for="cf">
                        Codice fiscale
                    </label>
                    <input name="cf" id="cf" value="<?= $cf ?>" 
                        title="Il tuo codice fiscale" 
                        pattern="[A-Za-z]{6}[0-9]{2}[ABCDEHLMPRSTabcdehlmprst]{1}[0-9]{2}[A-Za-z]{1}[0-9LMNPQRSTUVlmnpqrstuv]{3}[A-Za-z]{1}">
                    <button type="submit" class="button rounded">
                        Inviami il link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
