<?php

include "../../check_login.php";

$dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

$nome_squadra = "";
$id_squadra = 0;
if ( !isset($_GET["id"]) && !isset($_POST["id_squadra"]) )
{
    //Squadra non specificata
    header("Location: ../index.php");
    exit;
}
if ( !($dati_staff->is_referente || $anagrafica->is_admin) )
{
    //Autorizazione mancante
    header("Location: ../index.php");
    exit;
}
if (isset($_GET["id"]) && !is_array($_GET["id"]))
{
    //Si sta caricando la pagina di conferma
    $id_squadra = (int)$_GET["id"];
    $nome_squadra = getNomeSquadra($connection, $id_squadra);
} elseif (!is_array($_POST["id_squadra"])) {
    //E' stato premuto conferma
    $id_squadra = (int)$_POST["id_squadra"];
    $r = raw_squadra($connection, $id_squadra);
    $nome_squadra = acc($r->nome);
    if ($r->id_parrocchia == $dati_staff->id_parrocchia || $anagrafica->is_admin)
    {
        //Si sta cancellando una squadra della propria parrocchia
        try {
            $errore = cancellaSquadra($connection, $id_squadra);
        } catch (Exception $ex) {
            $errore = "Impossibile cancellare la squadra!";
        }
        if (!isset($errore) || strlen($errore) === 0)
        {
            //Tutto ok, esco
            header("Location: index.php");
            exit;
        }
    } else {
        //Non si possono cancellare le squadre alle altre parrocchie
        $errore = "La squadra non &egrave; della propria parrocchia.";
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Area Staff</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Staff ----------------------------------------------------------------- -->
<section id="staff" class="admin flex center">
    <div class="column col-100 flex center">
        <form action="cancella.php" method="post" class="login-form">
            <h3>
                Stai per cancellare la squadra &quot;<em><?= $nome_squadra ?></em>&quot;,
                &egrave; una scelta consapevole?
            </h3>
            <hr>
            <?php
            if (isset($errore))
                echo "<p class='text error'><strong>$errore</strong></p><hr>"
            ?>
            <p class="text">
                Ogni record contenente riferimenti a &quot;<em><?= $nome_squadra ?></em>&quot; sar&agrave;
                cancellato e non ripristinabile
            </p>
            <input type="hidden" value="<?= $id_squadra ?>" name="id_squadra">
            <input class="button rounded" type="submit" name="cancella_squadra_submit" value="Conferma">
        </form>
    </div>
</section>

</div>


</body>

</html>