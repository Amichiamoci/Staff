<?php
if (!isset($_GET["year"]) || !ctype_digit($_GET["year"]))
{
    header("Location: ?year=" . date("Y"));
    exit;
}
$year = (int)$_GET["year"];
$current_year = (int)date("Y");
if ($year < $current_year)
{
    header("Location: ./edizione_passata.php?year=$year");
    exit;
}
require_once "../load/db_manager.php";
$is_extern = true;
Cookie::DeleteIfExists("id_anagrafica");
Cookie::DeleteIfExists("login_forward");
Cookie::DeleteIfExists("success");
Cookie::DeleteIfExists("form");

$edizione = Edizione::FromYear($connection, $year);
if (!isset($edizione) || !$edizione->ok())
{
    header("Location: ./edizione_non_trovata.php?year=$year");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Iscriviti all'edizione <?= $year ?></title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

<section class="flex center">
    <div>
        <h1>
            Ciao 
        </h1>
        <?php if ($edizione->IscrizioniAperte()) { ?>
            <h2>
                Ti stai registrando per Amichiamoci <?= $year ?>
            </h2>
            <p>
                Assicurati, prima di procedere con la compilazione, di avere delle scansioni o foto di un tuo documento di identit&agrave;, in un unico file.<br>
                Se hai meno di 18 anni fai compilare questo form <u>prima</u> ad un tuo <strong>genitore/tutore</strong>.<br>
                Per completare l'iscrizione è necessario un certificato medico sportivo di livello almeno NON agonistico, 
                se non lo hai adesso potrai comunque compilare il form 
                e mandarlo successivamente ai tuoi staffisti di riferimento.
            </p>
            
            <a class="button" href="./get_link.php?year=<?= $year ?>" target="_self" title="Vai al prossimo passaggio">
                Ho gi&agrave; partecipato ad Amichiamoci 2023 
                <?= (int)date("Y") >= 2025 ? "o successivi" : "" ?>
            </a>
            <br>
            <a class="button" href="./invia_anagrafica.php?year=<?= $year ?>&success=./iscrizione.php" target="_self" title="Vai al prossimo passaggio">
                <strong>Non</strong> ho (ancora) partecipato ad Amichiamoci 2023
                <?= (int)date("Y") >= 2025 ? "o successivi" : "" ?>
            </a>
        <?php } else { ?>
            <p>
                Purtroppo le iscrizioni per Amichiamoci <?= $year ?> sono chiuse adesso
            </p>
        <?php } ?>
    </div>
</section>

</div>
</body>
</html>