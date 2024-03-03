<?php

include "../load/db_manager.php";

if (isset($_COOKIE["user_id"]))
{
    $anagrafica_inutilizzata = new AnagraficaResult();
    if (isUserLogged($connection, (int)$_COOKIE["user_id"], $_SERVER['HTTP_USER_AGENT'], getUserIp(), $anagrafica_inutilizzata)){
        header("Location: ../index.php");
        exit;
    }
}

if (isset($_POST["login_submit"]) && 
    isset($_POST["username"]) && !is_array($_POST["username"]) &&
    isset($_POST["password"]) && !is_array($_POST["password"]))
{
    $user = $_POST["username"];
    $pass = $_POST["password"];

    if (!empty($user) && !empty($pass))
    {
        if (User::Login($connection, $user, $pass, $_SERVER['HTTP_USER_AGENT'], getUserIp()))
        {
            $loc = "../index.php";
            //Ha passato il login
            if (isset($_COOKIE["login_forward"]))
            {
                $loc = $_COOKIE["login_forward"];
                Cookie::Delete("login_forward");
            } 
            header("Location: $loc");
        } else {
            //Non ha passato il login
            header("Location: login.php?login_result=failure");
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Login</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Login ----------------------------------------------------------------- -->

<section id="login" class="login full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="login.php" method="post" class="login-form">
                <?php
                    if (isset($_GET["login_result"]))
                    {
                        $result = $_GET["login_result"];

                        if ($result == "error")
                        {
                            ?>
                                <p class="error">
                                    Un errore &egrave; avvenuto.
                                </p>
                            <?php
                        } elseif ($result == "failure") {
                            ?>
                                <p class="error">
                                    Username o password errati
                                </p>
                                <a class="link" href="recupera-password.php" title="Clicca qui">Password dimenticata?</a>
                            <?php
                        }
                    }
                ?>
                <h3>Inserisci le credenziali</h3>
                <div class="input-box flex v-center wrap">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required autocomplete="on">

                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required autocomplete="on">
                </div>
                <input class="button rounded" type="submit" name="login_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>