<?php

include "../check_login.php";

$user_message = "Qui puoi inviare messaggi agli utenti dell'app Amichiamoci";
if (isset($_POST["message_submit"]) && isset($_POST["testo"]))
{
    $testo = $_POST["testo"];

    if (strlen($testo) > 0)
    {
        if (creaMessaggio($connection, $testo, $user_id))
        {
            $user_message = "Messaggio inviato correttamente";
        } else {
            $user_message = "&Egrave; avvenuto un errore durante la creazione del messaggio o durante l'invio della notifica all'app.<br /> ";
            $user_message .= "Il messaggio potrebbe essre stato recapitato correttamente ma gli utenti potrebbero non riceverlo finch&eacute; non aprono l'app.";
        }
    } else {
        $user_message = "Messaggio vuoto econseguentemente non valido";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Invia messaggio all'App</title>
</head>

<body>

<?php include "../parts/nav.php";?>
<div class="container">

<!-- Form ----------------------------------------------------------------- -->

<section id="send-message" class="send-message full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="crea-messaggio.php" method="post" class="send-message-form">
                <p class="text">
                    <?php
                        echo $user_message;
                    ?>
                </p>
                <hr>
                <div class="input-box flex v-center wrap">
                    <textarea name="testo" id="testo">
                    </textarea>
                </div>
                <input class="button rounded" type="submit" name="message_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>


<!-- Text Editor -->
<script src="../assets/ckeditor/build/ckeditor.js"></script>
<script type="text/javascript">
    ClassicEditor
        .create( document.querySelector( '#testo' ))
        .then( editor => {
            window.editor = editor;
        } )
        .catch( error => {
            console.error( 'Oops, something went wrong!' );
            console.error( error );
        } );
</script>
</body>

</html>