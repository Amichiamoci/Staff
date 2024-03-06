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
            Amichiamoci <?= $year ?> &egrave; terminato!
        </h1>
        <h3>
            Iscrivi all'<a href="./index.php">edizione corrente</a>
        </h3>
    </div>
</section>

</div>
</body>
</html>