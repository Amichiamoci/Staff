<?php

include "./check_login.php";

?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Attività degli utenti</title>
</head>

<body>

<?php include "./parts/nav.php" ?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <h2>
            Lista dei log-in
        </h2>
        <div class="tables flex center">
            <?php

			echo getUsersActivity($connection);

			?>
        </div>
    </div>
</section>

</div>

</body>

</html>