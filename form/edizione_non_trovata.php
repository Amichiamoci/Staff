<?php
if (!isset($_GET["year"]) || !ctype_digit($_GET["year"]))
{
    header("Location: index.php");
    exit;
}
$year = (int)$_GET["year"];
?>
<!DOCTYPE html>
<html>
<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci <?= $year ?></title>
</head>
<body>
<?php include "../parts/nav.php";?>
<div class="container">

<section class="full-h flex center">
    <div>
        <h1>
            Siamo spiacenti
        </h1>
        <h3>
            Amichiamoci <?= $year ?> non &egrave; stato trovato
        </h3>
        <p>
            Iscrivi all'<a href="./index.php">edizione corrente</a>
        </p>
    </div>
</section>

</div>
</body>
</html>