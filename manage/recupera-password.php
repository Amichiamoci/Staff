<?php

include "../load/db_manager.php";

$user = User::LoadFromSession();
if (isset($user) && $user->TimeLogged() < 60 * 30)
{
    header("Location: ../index.php");
    exit;
}

$mail_subject = "";
if (isset($_POST["recover_submit"]) && isset($_POST["user_name"]) && is_string($_POST["user_name"]))
{
    $user = $_POST["user_name"];
    $email = "";
    $target_id = 0;
    if (preg_match($user_names_regex, $user))
    {
        try {
            $data = explode(",", Email::GetByUserName($connection, $user));
            $email = $data[0];
            $target_id = (int)$data[1];
        } catch (Exception $ex) {
            $err = $ex->getMessage();
            $target_id = 0;
        }
    }
    if (!empty($email) && $target_id !== 0)
    {
        //Do the recover here

    }

    $mail_subject = "?subject=" . urlencode("Recupero password $user");
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Recupera Password</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Login ----------------------------------------------------------------- -->

<section id="login" class="login full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="recupera-password.php" method="post" class="login-form">
                <h3>Inserisci le credenziali</h3>
                <p class="text">
                    Se hai un account staff associato riceverai una mail all'indirizzo che hai specificato alla creazione.<br>
                    Se il processo fallisce o non ti sei ancora registrato come staffista contatta tempestivamente
                    <a target="_blank" href="mailto:dev@amichiamoci.it<?= $mail_subject ?>" title="Clicca qui" class="link">dev@amichiamoci.it</a>
                </p>
                <h4>
                    Funzionalit&agrave; non ancora disponibile
                </h4>
                <div class="input-box flex v-center wrap">
                    <label for="username">Username</label>
                    <input type="text" name="user_name" id="username" required>

                    <label for="email">Email</label>
                    <input type="email" name="user_mail" id="email" required>
                </div>

                <input class="button rounded" type="submit" name="recover_submit" value="Conferma" disabled>
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>