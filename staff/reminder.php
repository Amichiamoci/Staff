<?php
    include "../check_login.php";
    
    if (!User::$Current->is_admin)
    {
        header("Location: index.php");
        exit;
    }
    $year = (int)date("Y");
    $emails = Iscrizione::EmailNonSubscribed($connection, $year);
    $sent = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        foreach ($emails as $row)
        {
            $testo = "";
            if ($row["sesso"] === "M") {
                $testo .= "Caro ";
            } else {
                $testo .= "Cara ";
            }
            $testo .= htmlspecialchars($row["nome"]) . ",<br />";
            $testo .= "<p>";
            $testo .= "Ti scriviamo per farti sapere che <strong>sono aperte le iscrizioni</strong> ad Amichiamoci $year!<br />";
            $testo .= "Come fare a iscriversi? Clicca su queto link o copialo sul browser, facendo attenzione a copiare per bene tutti i caratteri:";
            $testo .= "</p>";
            $testo .= "<a style=\"font-size: larger\" href=\"" . ISCRIZIONI_URL . "\">" . ISCRIZIONI_URL . "</a>";
            $testo .= "<p>";
            $testo .= "Come puoi rimanere aggiornato? Seguendo la nostra pagina Instagram o il nostro canale WhatsApp:";
            $testo .= "</p>";
            $testo .= "<a href=\"" . INSTAGRAM_URL . "\">" . INSTAGRAM_URL . "</a><br />";
            $testo .= "<a href=\"" . WHATSAPP_URL . "\">" . WHATSAPP_URL . "</a><br />";
            $testo .= "<p>Ti aspettiamo!</p><br /><br />";
            $testo .= "<small>Ti preghiamo di non rispondere a quest'email</small>";
            if (Email::Send($row["email"], "Iscrizione Amichiamoci $year", $testo))
            {
                $sent[] = $row["email"];
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

<section class="flex vertical">
    <div>
        <a href="<?= ISCRIZIONI_URL ?>">Iscrizioni</a> - 
        <a href="<?= WHATSAPP_URL ?>">Whatsapp</a> - 
        <a href="<?= INSTAGRAM_URL ?>">Instagram</a>
    </div>
    <form method="post">
        <button type="submit" class="button rounded">Invia Email</button>
        <div>
            Email inviate:<br>
            <ul>
                <?php foreach ($sent as $email) { ?>
                    <li>
                        <a href="mailto:<?= $email ?>" class="link"><?= $email ?></a>
                    </li>
                <?php } ?>
            </ul>
            <br>
            Totale:<?= count($sent) ?>/<?= count($emails) ?>
        </div>
    </form>
</section>

</div>

</body>

</html>