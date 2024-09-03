<?php
$is_extern = true;
$hide_share_link = true;
require_once "../load/db_manager.php";

if (!Cookie::Exists("id_anagrafica"))
{
    header("Location: ./index.php");
    exit;
}

$edizione = Edizione::Current($connection);
if (!isset($edizione) || !$edizione->IscrizioniAperte())
{
    header("Location: ./index.php");
    exit;
}

$anagrafica = Anagrafica::Load($connection, (int)Cookie::Get("id_anagrafica"));
if (!isset($anagrafica))
{
    header("Location: ./index.php");
    exit;
}
$errore = "";
if (
    isset($_POST["doc_type"]) && ctype_digit($_POST["doc_type"]) &&
    isset($_POST["doc_code"]) && is_string($_POST["doc_code"]) &&
    isset($_POST["doc_expires"]) && is_string($_POST["doc_expires"])
) {
    $nome = $anagrafica->nome;
    $cognome = $anagrafica->cognome;
    $nome_file = "/documenti/documento $nome $cognome self";
    $nome_file = file_spaces_to_underscores($nome_file);
    if (
        isset($_FILES["doc_file"]) &&
        upload_file($_FILES["doc_file"], $nome_file, $errore)
    ) {
    
        $id = Anagrafica::Create(
            $connection,
            '',
            '',
            '',
            '',
            '',
            '',
            $anagrafica->cf,
            (int)$_POST["doc_type"],
            $_POST["doc_code"],
            $_POST["doc_expires"],
            $nome_file
        );
        if ($id === 0 || $id !== (int)Cookie::Get("id_anagrafica")) {
            $errore = "È avvenuto un errore :/";
        } else {
            header("Location: ./iscrizione-anagrafica.php?year=" . date("Y"));
            exit;
        }
    }
}
    
?>
<!DOCTYPE html>
<html lang="it-it">
<head>
    <?php include "../parts/head.php"; ?>
    <link rel="canonical" href="./index.php">
	<meta name="description" content="Form di inserimento dati personali | Amichiamoci">

	<meta property="og:title" content="Inserimento dati personali | Amichiamoci">
	<meta property="og:type" content="website">
	<meta property="og:url" content="./index.php">
	<meta property="og:description" content="Form di inserimento dati personali | Amichiamoci">
	<meta property="og:image" content="../assets/favicon.png">

	<link rel="icon" href="../assets/favicon.png">
	<link rel="apple-touch-icon" href="../assets/favicon.png">
    <title>
        Amichiamoci | Reinvia documento
    </title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

    <?php include "../parts/edition.php"; ?>

    <div class="grid">
         <div class="column col-100">
            <form method="post" enctype="multipart/form-data">
                <?php if (isset($errore) && !empty($errore)) { ?>
                    <p class="text">
                        &darr;
                        <strong>
                            <?= htmlspecialchars($errore) ?>
                        </strong>
                        &uarr;
                    </p>
                <?php } ?>

                <h2>
                    Il tuo documento risulta scaduto, inviane uno valido
                </h2>
                <div class="input-box flex v-center wrap">

                    <?php include __DIR__ . "/form_documento.php"; ?>

                    <button type="submit" class="button rounded">
                        Invia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
