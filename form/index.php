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
<html lang="it-it">
<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Iscriviti all'edizione <?= $year ?></title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

<section class="flex vertical" style="margin-inline: 10px; width: calc(100% - 20px);">
    <h1>
        Ciao 
    </h1>
    <?php if ($edizione->IscrizioniAperte()) { ?>
        <h2>
            Ti stai registrando per Amichiamoci <?= $year ?>
        </h2>
        <h4>
            Leggere qui prima di andare avanti!
        </h4>
        <p class="text" style="text-align: justify">
            Assicurati, prima di procedere con la compilazione, 
            di avere delle scansioni o foto di un tuo documento di identit&agrave;, in un unico file.<br>
            
            Per completare l'iscrizione è necessario un certificato medico sportivo di livello almeno NON agonistico, 
            se non lo hai adesso potrai comunque compilare il form 
            e mandarlo successivamente ai tuoi staffisti di riferimento.
        </p>

        <h3>
            Per i maggiorenni
        </h3>
        <a class="button" href="./get_link.php?year=<?= $year ?>" target="_self" title="Vai al prossimo passaggio">
            Ho gi&agrave; partecipato ad Amichiamoci 2023 
            <?= (int)date("Y") >= 2025 ? "o successivi" : "" ?>
        </a>
        <br>
        <a class="button" href="./invia_anagrafica.php?year=<?= $year ?>&success=./iscrizione.php"
            target="_self" title="Vai al prossimo passaggio">
            Non ho partecipato ad Amichiamoci 2023
            <?= (int)date("Y") >= 2025 ? "o successivi" : "" ?>
        </a>

        <hr style="margin-block: 7px;">

        <h3>
            Per i minorenni
        </h3>
        <p class="text" style="text-align: justify">
            I minori che hanno partecipato dal 2023 od oltre possono procedere con il link apposito sotto.<br>

            I minori che non hanno partecipato dal 2023 od oltre devono, <strong>PRIMA DI PROCEDERE</strong>, 
            far compilare ad un proprio genitore/tutore il 
            <a href ="./invia_anagrafica.php?year=<?= $year ?>" target="_self" class="link">form apposito</a>, a meno che 
            il genitore/tutore non abbia già inviato i suoi documenti in passato
        </p>
        <a class="button" href="./get_link.php?year=<?= $year ?>" target="_self" title="Vai al prossimo passaggio">
            Ho gi&agrave; partecipato ad Amichiamoci 2023 
            <?= (int)date("Y") >= 2025 ? "o successivi" : "" ?>
        </a>
        <br>
        <a class="button" href="./invia_anagrafica.php?year=<?= $year ?>" target="_self" title="Vai al prossimo passaggio">
            Sono un genitore e voglio inviare i dati
        </a>

    <?php } else { ?>
        <p>
            Purtroppo le iscrizioni per Amichiamoci <?= $year ?> sono chiuse adesso
        </p>
    <?php } ?>
</section>

</div>
</body>
</html>