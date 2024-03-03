<?php

    include "../../check_login.php";
    $year = null;
    $include_all = 3;
    if (isset($_GET["year"]) && !is_array($_GET["year"]))
    {
        if ($_GET["year"] !== "all")
        {
            $year = $_GET["year"];
            $include_all = 4;
        }
    }
    //Do not remove
    $dati_staff = Staff::Get($connection, $anagrafica->staff_id);

    $add_link = ($dati_staff->is_subscribed() && $dati_staff->is_referente) || $anagrafica->is_admin;
?>


<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Elenco non iscritti</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        
        <?php include "./menu.php"; ?>
        <div class="column col-100 flex vertical">
            <?= getNonPartecipantiList($connection, $year) ?>
        </div>
    </div>
</section>

</div>

</body>

</html>