<?php

include "./check_login.php";

if (isset($_POST["create_user_submit"]) && isset($_POST["email"]))
{
    $email = $_POST["email"];
    $is_admin = $anagrafica->is_admin && filter_has_var(INPUT_POST, 'is_admin');
    if (str_contains($email, "@"))
    {
        $user_name = explode('@', $email)[0];
        $password = generatePassword();
        $hashed = Security::Hash($password);
        $generated_id = createUser($connection, $user_name, $hashed, $is_admin);
        $mail_text = join("\r\n", array(
            "<h3>Benvenuto/a</h3>",
            "<p>&Egrave; appena stato creato un utente sul sito <a href=\"https://www.amichiamoci.it\">amichiamoci.it</a> con questa email.",
            "Ti chiediamo di loggarti sul sito nella sezione <a href=\"https://www.amichiamoci.it/admin\">admin</a>,\r\n" .
                "utilizzando come <strong style=\"user-select: none;\">nome utente: </strong><code style=\"font-family: monospace;\">$user_name</code>\r\n" .
                "<span style=\"user-select: none;\"> e come </span><strong style=\"user-select: none;\">password: </strong><code style='font-family: monospace;'>$password</code><br>",
            "Una volta loggato potrai cambiare sia nome utente che password.</p>",
            "<p>Nel caso tu non riesca a loggarti con le credenziali appena fornite prova a cancellare i cookie e riprovare dopo qualche minuto.</p>",
            "<hr>",
            "<p>Dal portale ti sar&agrave; possibile inserire i tuoi dati anagrafici, scegliendo la mail con cui gestire l'account\r\n" .
            "(puoi quindi non scegliere questa) e anno per anno registrarti come staffista all'edizione corrente.</p>",
            "<hr>",
            "<p>In caso di problemi scrivi tempestivamente a <a href=\"mailto:info@amichiamoci.it\">info@amichiamoci.it</a></p>",
            "Ecco una serie di informazioni che non ti interessaranno, ma io le metto ugualmente:<br>",
            "Hash della password: <output style=\"user-select:none;\">$hashed</output><br>",
            "User id: <output style=\"user-select:none;\">$generated_id</output>"));
        $subject = "Creazione utente";
        
        if ($generated_id != 0)
        {
            //Utente creato, ora va inviata l'email

            if (send_email($email, $subject, $mail_text, $connection, true))
            {
                $message = "Email inviata correttamente a $user_name";
                //Email inviata correttamente
            } else {
                $message = "Errore durante l'invio dell'email";
            }
        } else {
            $message = "Errore durante la creazione dell'utente";
        }
        
    } else {
        $message = "Email \"$email\" non valida!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Crea nuovo utente</title>
</head>

<body>

<?php include "./parts/nav.php" ?>
<div class="container">

<section class="full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="create-user.php" method="post" class="login-form">
                <h3>Crea nuovo utente</h3>
                <?php
                    if (isset($message))
                    {
                        echo "<p class='error'>$message</p>";
                    }
                ?>
                <div class="input-box flex v-center wrap">
                    <label for="email">Email</label>
                    <input type="email" name="email" required>
                    <?php if ($anagrafica->is_admin) { ?>
                        <label for='is_admin'>Admin</label>
                        <div class="checkbox">
                            <input type='checkbox' name='is_admin' id="is_admin">
                        </div>
                    <?php } ?>
                </div>
                <input class="button rounded" type="submit" name="create_user_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>