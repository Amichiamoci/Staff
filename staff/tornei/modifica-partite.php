<?php
include "../../check_login.php";

$dati_staff = Staff::Get($connection, User::$Current->staff_id);

if (!$dati_staff->is_in("Tornei") && !User::$Current->is_admin && !$dati_staff->is_referente) {
    header("Location: ./index.php");
    exit;
}

if (!isset($_GET["id"]))
{
    header("Location: ./index.php");
    exit;
}
$id = (int)$_GET["id"];
$torneo = Torneo::LoadIfGenerated($connection, $id);
if (!isset($torneo))
{
    header("Location: ./index.php");
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

<div class="column col-100 flex wrap">
    <div class="flex vertical v-center" style="width: 100%">
        <h2>
            TORNEO <?= htmlspecialchars($torneo->nome) ?>
        </h2>
        <h4>
            <?= htmlspecialchars($torneo->sport) ?> - <small> <?= htmlspecialchars($torneo->tipo) ?> </small>
        </h4>
    </div>
    <hr>
    <?= calendarioTorneo($connection, $id) ?>
</div>


</div>


</body>

</html>