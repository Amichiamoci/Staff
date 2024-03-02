<?php

include "./check_login.php";

if (isset($_POST["maintenance_status"]) && $anagrafica->is_admin)
{
    switch ((int)$_POST["maintenance_status"])
    {
        case 1://Online
            $query = "CALL StopMaintenance()";
            break;
        case 2://Maintenance
            $query = "CALL StartMaintenance()";
            break;
        case 3://Full block
            $query = "CALL BlockSystem()";
            break;
    }
    if (!isset($query))
    {
        $message = "Errore nel form.";
    } else {
        $result = mysqli_query($connection, $query);
        $ok = (bool)$result;
        mysqli_next_result($connection);
        if ($ok) 
        {
            header("Location: ./index.php");
            exit;
        }
        $message = "Errore nel DB.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Manutenzione</title>
</head>

<body>

<?php include "./parts/nav.php" ?>
<div class="container">

<section class="login full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="maintenance.php" method="post">
                <h3>Accessibilit&agrave; sistema</h3>
                <?php
                    if (isset($message))
                    {
                        echo "<p class='error'>$message</p>";
                    }
                ?>
                <h5>
                    Status attuale: <?= getSystemStatus($connection) ?>
                </h5>
                <div class="input-box flex v-center wrap">
                    <label for="status">Status</label>
                    <select name="maintenance_status" id="status">
                        <optgroup label="Accessibile">
                            <option value="1">
                                Imposta online
                            </option>
                        </optgroup>
                        <optgroup label="Non accessibile">
                            <option value="2">
                                Metti in manutenzione
                            </option>
                            <option value="3">
                                Blocca totalmente
                            </option>
                        </optgroup>
                    </select>
                </div>
                <input class="button rounded" type="submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>