<?php
include "../../check_login.php";

?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Inserisci punteggio partite</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">
<?php include "../curr-edition.php"; ?>

<div class="column col-100 flex center">
    <?= inserisciPunteggioPartite($connection) ?>
</div>


</div>


</body>

</html>