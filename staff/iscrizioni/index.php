<?php

    include "../../check_login.php";
    $dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

    $add_link = ($dati_staff->is_subscribed() && $dati_staff->is_referente) || $anagrafica->is_admin;
    $year = null;
    $include_all = 1;
    if (isset($_GET["year"]))
    {
        if ($_GET["year"] !== "all")
        {
            $year = $_GET["year"];
            $include_all = 2;
        }
    }
?>


<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
    <?php if ($include_all === 1) { ?>
	    <title>Amichiamoci | Elenco Anagrafiche</title>
    <?php } else { ?>
        <title>Amichiamoci | Elenco Iscritti <?= $year ?></title>
    <?php } ?>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        
        <?php include "./menu.php"; ?>
        <div class="column col-100 flex vertical">
            <?= getAnagraficheList($connection, $year, 0, $add_link) ?>
        </div>
    </div>
</section>

</div>

</body>

</html>