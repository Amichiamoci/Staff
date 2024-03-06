<?php
    $is_editing = false;
    include "../../check_login.php";
    if (isset($_GET["form"]))
    {
        Cookie::Set("form", date("Y"), 3600);
    }

    Cookie::DeleteIfExists("id_anagrafica");
    
    if (isset($_GET["success"]) && !is_array($_GET["success"]))
    {
        Cookie::Set("success", $_GET["success"], 3600);
    }

    if (isset($_POST["anagrafica_submit"]) &&
        isset($_POST["nome"]) && is_string($_POST["nome"]) &&
        isset($_POST["cognome"]) && is_string($_POST["cognome"]) &&
        isset($_POST["email"]) && is_string($_POST["email"]) &&
        isset($_POST["cf"]) && is_string($_POST["cf"]) &&
        isset($_POST["doc_type"]) && ctype_digit($_POST["doc_type"]) &&
        isset($_POST["doc_code"]) && is_string($_POST["doc_code"]) &&
        isset($_POST["doc_expires"]) && is_string($_POST["doc_expires"]))
    {
        //Dati form
        $nome = file_remove_characters($_POST["nome"]);
        $nome = string_capitalize_words($nome);

        $cognome = file_remove_characters($_POST["cognome"]);
        $cognome = string_capitalize_words($cognome);

        $nome_file = "/documenti/documento $nome $cognome";
        $nome_file = file_spaces_to_underscores($nome_file);
        
        $compleanno = $_POST["compleanno"];

        $provenienza = $_POST["provenienza"];

        $tel = isset($_POST["tel"]) && is_string($_POST["tel"]) ? $_POST["tel"] : null;

        $email = $_POST["email"];

        $cf = $_POST["cf"];
        
        $doc_type = (int)$_POST["doc_type"];

        $doc_code = $_POST["doc_code"];

        $doc_expires = $_POST["doc_expires"];

        if (isset($_FILES["doc_file"]) &&
            upload_file($_FILES["doc_file"], $nome_file, $errore))
        {

            $id_anagrafica = Anagrafica::Create(
                $connection, 
                $nome, $cognome, 
                $compleanno, $provenienza, $tel, 
                $email, $cf, $doc_type, 
                $doc_code, $doc_expires, 
                $nome_file, $is_extern);
            if ($id_anagrafica === 0)
            {
                $errore = "<strong>Impossibile inserire anagrafica nel sistema!</strong>";
            } else {
                Cookie::Set("id_anagrafica", "$id_anagrafica", 3600 * 2);
                Cookie::Set("esit", "Dati inseriti correttamente.", 3600);
                $url = "../index.php";
                if (isset($_COOKIE["success"]))
                {
                    $url = $_COOKIE["success"];
                    Cookie::Delete("success");
                }
                header("Location: $url");
                exit;
            }
        }
        $anagrafica = new Anagrafica(
            $id_anagrafica, 
            $nome,
            $cognome,
            null);
        $anagrafica->compleanno = $compleanno;
        $anagrafica->proveninenza = $provenienza;
        $anagrafica->telefono = $tel;
        $anagrafica->email = $email;
        $anagrafica->cf = $cf;
        $anagrafica->doc_code = $doc_code;
        $anagrafica->doc_expires = $doc_expires;
    }
?>
<!DOCTYPE html>
<html lang="it-it">
<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Crea Anagrafica</title>
</head>

<body>
<?php include "../../parts/nav.php";?>
<div class="container">

    <!-- Form ----------------------------------------------------------------- -->
    <section class="flex center">
        <?php include "../../form/anagrafica.php"; ?>
    </section>
</div>
</body>
</html>