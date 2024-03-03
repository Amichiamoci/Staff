<?php

    include "../check_login.php";
    if (!User::$Current->is_admin)
    {
        header("Location: ../index.php");
        exit;
    }
?>


<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Elenco Email</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <div class="column col-100 flex vertical">
            <?php
                echo emailList($connection);
			?>
        </div>
    </div>
</section>

</div>

</body>

</html>