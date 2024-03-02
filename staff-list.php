<?php

include "./check_login.php";

?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Elenco Staffisti</title>
</head>

<body>

<?php include "./parts/nav.php" ?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <div class="column col-100 flex vertical">
            <?php
            if (isset($_GET["tutti"]))
            {
			    echo getStaffList($connection, 0, true);
            } else {
                echo getStaffList($connection);
            }

			?>
        </div>
    </div>
</section>

</div>

</body>

</html>