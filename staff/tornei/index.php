<?php
include "../../check_login.php";

$dati_staff = Staff::Get($connection, User::$Current->staff_id);

if (!$dati_staff->is_in("Tornei") && !User::$Current->is_admin && !$dati_staff->is_referente) {
    header("Location: ..");
    exit;
}


?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Area Staff</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">
<?php include "../curr-edition.php"; ?>
<!-- Staff ----------------------------------------------------------------- -->

<div class="column col-100 flex center">
    <?= getTornei($connection) ?>
</div>


</div>


</body>

</html>