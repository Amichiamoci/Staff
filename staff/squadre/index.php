<?php

 include "../../check_login.php";
 $dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

?>


<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Elenco Squadre</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <div class="column col-100 flex vertical">
            <?php
                $year = null;
                $sport = null;
                
                if (isset($_GET["year"]))
                {
                    $year = $_GET["year"];
                }
                if (isset($_GET["sport"]))
                {
                    $sport = $_GET["sport"];
                }
                echo getSquadreList($connection, $year, $sport, $dati_staff->is_referente || $anagrafica->is_admin);
			?>
        </div>
    </div>
</section>

</div>

</body>

</html>