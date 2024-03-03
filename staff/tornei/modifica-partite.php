<?php
include "../../check_login.php";

$dati_staff = Staff::Get($connection, $anagrafica->staff_id);

if (!$dati_staff->is_in("Tornei") && !$anagrafica->is_admin && !$dati_staff->is_referente) {
    header("Location: ./index.php");
    exit;
}

if (!isset($_GET["id"]))
{
    header("Location: ./index.php");
    exit;
}
$id = (int)$_GET["id"];
$nome = "";
$tipo = "";
$sport = "";
$query1 = "SELECT nome, tipo, sport FROM tornei_attivi WHERE id = $id AND partite <> 0";
$result1 = mysqli_query($connection, $query1);
if (!$result1 || $result1->num_rows === 0)
{
    header("Location: ./index.php");
    exit;
}
if ($row1 = $result1->fetch_assoc())
{
    $nome = $row1["nome"];
    $tipo = $row1["tipo"];
    $sport = $row1["sport"];
} else {
    //Torneo non trovato, potrebbe avere gia' un calendario
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
            TORNEO <?= acc($nome) ?>
        </h2>
        <h4>
            <?= acc($sport) ?> - <small> <?= acc($tipo) ?> </small>
        </h4>
    </div>
    <hr>
    <?= calendarioTorneo($connection, $id) ?>
</div>


</div>


</body>

</html>