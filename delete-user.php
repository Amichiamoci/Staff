<?php

include "./check_login.php";
$message = "";

if (isset($_POST["delete_user"]) && $anagrafica->is_admin)
{
    $target_id = $_POST["target_id"];
    $res = deleteUser($connection, $target_id);
    if ($res == "")
    {
        $message = "<span>&Egrave; avvenuto un errore: non &egrave; stato possibile cancellare l'utente</span>";
    } else {
        $message = "<span style='user-select: none;'>Utente cancellato (<output>$res</output> righe eliminate)</span>";
    }
    $message .= "<br>";
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Reset account</title>
</head>

<body>


<?php include "./parts/nav.php" ?>

<div class="container">

<section id="login" class="login full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <p>
                <?php echo $message ?>
                <br>
                <a href="all-users.php">Tutti gli utenti</a>
            </p>
        </div>
    </div>
</section>

</div>

</body>

</html>