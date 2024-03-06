<?php

include "../../check_login.php";
$titolo = "";
$testo = "";
$inizio = "";
$fine = "";
$edizione = Edizione::Current($connection);
function addDot($str)
{
    return ".$str";
}
$allowed_ext = array(
    "jpg",        "jpeg",       "gif",
    "png",        "bmp",        "avif",
    "tif",        "tiff",       "webp",
    "heic",       "heif");
$file_accept = join(", ", array_map("addDot", $allowed_ext));
$errore = "";
if (isset($_POST["titolo"]))
{
    $titolo = $connection->real_escape_string($_POST["titolo"]);
    $testo = $connection->real_escape_string($_POST["testo"]);
    if (strlen($testo) == 0)
    {
        $testo = "NULL";
    } else {
        $testo = "'$testo'";
    }
    $inizio = $connection->real_escape_string($_POST["inizio"]);
    $fine = $connection->real_escape_string($_POST["fine"]);
    if (strlen($fine) == 0)
    {
        $fine = "NULL";
    } else {
        $fine = "'$fine'";
    }
    $nome_file = "locandine/$edizione->year" . "_" .
        str_replace(array(" ", ".", ",", ":", ";", "<", ">", "?", "'", "\"", "\\"), "_", $titolo);
    if (isset($_FILES["locandina"]) && !empty($_FILES["locandina"]) &&
            is_uploaded_file($_FILES["locandina"]['tmp_name']))
    {
        if (!upload_file($_FILES["locandina"], $nome_file, $errore))
        {
            //Abbiamo il file ma non riusciamo a salvarlo
            $errore = "Impossibile uploadare la locandina. Riprova pi&ugrave; tardi.<br>$errore"; 
            $nome_file = "NULL";
        } else {
            //File salvato correttamente
            $nome_file = "'$nome_file'";
        }
    } else {
        //La form non contiene file, il file sara' aggiunto in un secondo momento
        $errore = "Nessun file";
        $nome_file = "NULL";
    }
    $query = "REPLACE INTO eventi (titolo, edizione, descrizione, locandina, inizio, fine) " .
        "VALUES ('$titolo', $edizione->id, $testo, $nome_file, '$inizio', $fine)";
    if ($testo == "NULL")
    {
        $testo = "";
    } else {
        $tetso = substr($testo, 1, strlen($testo) - 2);
    }
    if ($fine == "NULL")
    {
        $fine = "";
    } else {
        $fine = substr($fine, 1, strlen($fine) - 2);
    }
    $result = mysqli_query($connection, $query);
    if ($result)
    {
        mysqli_next_result($connection);
        //Ok
        header("Location: ../index.php");
        exit;
    } else {
        $errore = "Impossibile interrogare il DB";
    }
}


?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Crea evento</title>
</head>

<body>

<?php include "../../parts/nav.php";?>
<div class="container">

<!-- Form ----------------------------------------------------------------- -->

<section id="send-message" class="send-message full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="crea.php" method="post" class="send-message-form" enctype="multipart/form-data">
                <?php if ($errore != "") { ?>
                    <p class="text">
                        <?= $errore ?>
                    </p>
                <?php } ?>
                <div class="input-box flex v-center wrap">

                    <label for="titolo" title="Obbligatorio">
                        Titolo
                    </label>
                    <input type="text" name="titolo" id="titolo" value="<?= $titolo ?>" required maxlength="128">

                    <label for="locandina" title="Non obbligatoria ma consigliata">
                        Locandina
                    </label>
                    <input type="file" accept="<?= $file_accept ?>" name="locandina" id="locandina">

                    <label for="inizio" title="Obbligatorio">
                        Inizio evento
                    </label>
                    <input type="datetime-local" name="inizio" id="inizio" value="<?= $inizio ?>" required>

                    <label for="fine">
                        Fine evento
                    </label>
                    <input type="datetime-local" name="fine" id="fine" value="<?= $fine ?>">

                    <textarea name="testo" id="testo">
                        <?= $testo ?>
                    </textarea>
                </div>
                <input class="button rounded" type="submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>


<!-- Text Editor -->
<script src="../../assets/ckeditor/build/ckeditor.js"></script>
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