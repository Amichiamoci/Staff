<?php

include "./check_login.php";
$message = "";

if (isset($_POST["reset_user"]) && $anagrafica->is_admin)
{
    $target_id = (int)$_POST["target_id"];
    $res = User::ResetPassword($connection, $target_id);
    if ($res == "")
    {
        $message = "&Egrave; avvenuto un errore: non &egrave; stato possibile resettare la password";
    } else {
        $message = "<span style='user-select: none;'>Nuova password: </span><output>$res</output>";
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
    <?php include "parts/head.php";?>
	<title>Amichiamoci | Reset account</title>
</head>

<body>


<?php include "parts/nav.php" ?>

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