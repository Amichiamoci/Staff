<?php

include "./check_login.php";
if ($anagrafica->is_admin)
{
    if (isset($_POST["block_user"]))
    {
        $target_id = $_POST["target_id"];
        if (User::Ban($connection, $target_id))
        {
            $message = "Ban effettuato.";
        } else {
            $message = "&Egrave; avvenuto un errore: non &egrave; stato possibile eseguire il ban.";
        }
        $message .= "<br>";
    } elseif (isset($_POST["restore_user"]))
    {
        $target_id = $_POST["target_id"];
        if (User::Restore($connection, $target_id))
        {
            $message = "Ban annullato.";
        } else {
            $message = "&Egrave; avvenuto un errore: non &egrave; stato possibile annullare il ban.";
        }
        $message .= "<br>";
    }
} else {
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Blocca/Sblocca account</title>
</head>

<body>


<?php include "./parts/nav.php" ?>

<div class="container">


<section id="login" class="login full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <p class="text">
                <?php if (isset($message)) echo $message ?>
            </p>
            <br>
            <a href="all-users.php" class="link">Tutti gli utenti</a>
        </div>
    </div>
</section>

</div>

</body>

</html>