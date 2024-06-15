<?php
    include "../check_login.php";
    
    if (!User::$Current->is_admin)
    {
        header("Location: index.php");
        exit;
    }
    $year = (int)date("Y");
    $emails = Iscrizione::EmailNonSubscribed($connection, $year);
    $errors = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        foreach ($emails as $row)
        {
            $testo = "Ciao " . htmlspecialchars($row["nome"]) . ",<br />";
            $testo .= "<p>";
            $testo .= "Ti scriviamo per farti sapere che <strong>sono aperte le iscrizioni</strong> ad Amichiamoci $year!<br />";
            $testo .= "Come fare a iscriversi? Clicca su queto link o copialo sul browser, facendo attenzione a copiare per bene tutti i caratteri:";
            $testo .= "</p>";
            $testo .= "<a style=\"font-size: larger\" href=\"" . ISCRIZIONI_URL . "\">" . ISCRIZIONI_URL . "</a>";
            $testo .= "<p>";
            $testo .= "Come puoi rimanere aggiornato? Seguendo la nostra pagina Instagram o il nostro canale WhatApp:";
            $testo .= "</p>";
            $testo .= "<a href=\"" . INSTAGRAM_URL . "\">" . INSTAGRAM_URL . "</a><br />";
            $testo .= "<a href=\"" . WHATSAPP_URL . "\">" . WHATSAPP_URL . "</a><br />";
            $testo .= "<p>Ti aspettiamo!</p><br /><br />";
            $testo .= "<small>Ti preghiamo di non rispondere a quest'email</small>";
            if (!Email::Send($row["email"], "Iscrizione Amichiamoci $year", $testo))
            {
                $errors[] = $row["email"];
            }
        }
    }
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Email inviate</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<section class="flex center">
    <form method="post">
        <button type="submit" class="button rounded">Invia Email</button>
    </form>
    <a href="<?= WHATSAPP_URL ?>">Whatsapp</a> - <a href="<?= INSTAGRAM_URL ?>">Instagram</a>
    <br>
    Errori nell'invio:<br>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li>
                <a href="mailto:<?= $error ?>" class="link"><?= $error ?></a>
            </li>
        <?php } ?>
    </ul>
</section>

</div>

</body>

</html>