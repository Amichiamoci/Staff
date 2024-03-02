<?php

include "./check_login.php";

?>

<!DOCTYPE html>
<html>

<head>
	<?php include "./parts/head.php"; ?>
	<title>Amichiamoci | Elenco Anagrafiche</title>
</head>

<body>

<?php include "./parts/nav.php"; ?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <div class="column col-100 flex vertical">
            <?php
			
			echo getAnagraficheList($connection, null, 0, false);

			?>
        </div>
    </div>
</section>

</div>

</body>

</html>