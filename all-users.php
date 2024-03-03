<?php

include "./check_login.php";

?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Elenco utenti</title>
</head>

<body>

<?php include "./parts/nav.php" ?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <div class="column col-100 flex vertical">
            <h2>Lista utenti</h2>
            <?php

			echo allUsers($connection, User::$Current->is_admin);

			?>
        </div>
    </div>
</section>

</div>

</body>

</html>