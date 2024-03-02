<?php

include "../check_login.php";
$message = "";
$form = "";
if (isset($_GET["action"]))
{
    $form = $_GET["action"];
}

if (isset($_POST["cambio_password_submit"]))
{
    $curr_pass = $_POST["curr_password"];
    $new_pass = $_POST["new_password"];
    if (changeUserPassword($connection, $user_id, $curr_pass, $new_pass))
    {
        logUserOut($_COOKIE["user_id"]);
        header("Location: login.php");
        exit;
    }
    $message = "&Egrave; avvenuto un errore: la vecchia password potrebbe non essere corretta";
}
if (isset($_POST["cambio_username_submit"]))
{
    $curr_pass = $_POST["curr_password"];
    $new_name = $_POST["new_username"];
    if (!changeUserName($connection, $user_id, $curr_pass, $new_name))
    {
        $message = "&Egrave; avvenuto un errore: la password potrebbe non essere corretta o il nuovo nome utente potrebbe essere gi&agrave; preso.";
        //header("location: login.php");
    } else {
        header("Location: ../index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Cambiamento dati utente</title>
</head>

<body>

<?php include "../parts/nav.php" ?>

<div class="container">

<!-- Login ----------------------------------------------------------------- -->

<section class="login full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <?php if ($form == "password") { ?>
                <form action="cambia.php?action=password" method="post" class="login-form">
                    <h3>Cambia password</h3>
                    <p class="text">
                        La password deve avere tra gli 8 e i 16 caratteri, 
                        almeno un simbolo, almeno un numero, una maiuscola ed una minuscola.<br>
                        Se l'operazione avr&agrave; successo sarai reindirizzato alla pagina di login
                    </p>
                    <?php
                        if ($message != "")
                        {
                            echo "<p class='error'>$message</p>";
                        }
                    ?>
                    <div class="input-box flex v-center wrap">
                        <label for="curr_password">Password attuale</label>
                        <input type="password" name="curr_password" id="curr_password" required>

                        <label for="new_password">Nuova Password</label>
                        <input type="password" name="new_password" id="new_password" required>
                    </div>
                    <input class="button rounded" type="submit" id="submit_new_password" name="cambio_password_submit" value="Conferma">
                </form>
                <script type="text/javascript">
                    document.getElementById('new_password').oninput = checkPasswordMatch;
                    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&'])[^ ]{8,16}$/;
                    const btn = document.getElementById("submit_new_password");
                    function checkPasswordMatch()
                    {
                        const password = $("#new_password").val();
                        btn.disabled = password !== '' && !regex.test(password);
                    }
                </script>
            <?php } elseif ($form == "username") { ?>
                <form action="cambia.php?action=username" method="post" class="login-form">
                    <h3>Cambia nome utente</h3>
                    <?php
                        if ($message != "")
                        {
                            echo "<p class='error'>$message</p>";
                        }
                    ?>
                    <div class="input-box flex v-center wrap">
                        <label for="curr_password">Password attuale</label>
                        <input type="password" name="curr_password" id="curr_password" required>

                        <label for="new_username">Nuovo username</label>
                        <input type="text" name="new_username" id="new_username" required pattern="[A-Za-z0-9]{6,16}" maxlength="16" placeholder="Solo lettere e numeri">
                    </div>
                    <input class="button rounded" type="submit" name="cambio_username_submit" value="Conferma">
                </form>
            <?php  
                } else {
                    header("location: ../index.php");
                    exit;
                };
            ?>
        </div>
    </div>
</section>

</div>

</body>

</html>