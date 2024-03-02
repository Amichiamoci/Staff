<?php
include "../../check_login.php";

$dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

if (!$dati_staff->is_in("Tornei") && !$anagrafica->is_admin) {
    header("Location: ./index.php");
    exit;
}

if (!isset($_GET["id"]) && !isset($_POST["id"]))
{
    header("Location: ./index.php");
    exit;
}
$id = 0;
$nome = "";
$tipo = "";
$sport = "";
if (isset($_GET["id"]))
{
    $id = (int)$_GET["id"];
} else {
    $id = (int)$_POST["id"];
}
$query1 = "SELECT nome, tipo, sport FROM tornei_attivi WHERE id = $id AND partite = 0";
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
if (isset($_POST["id"]))
{
    if (GeneraCalendario($connection, $id))
    {
        //Successo!
        header("Location: ./index.php");
        exit;
    }
    $errore = "&Egrave; avventuo un errore!";
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
    <form action="crea-partite.php" method="post">
        <h2>
            TORNEO <?= acc($nome) ?>
        </h2>
        <h4>
            <?= acc($sport) ?>
        </h4>
        <h4>
            <?= acc($tipo) ?>
        </h4>
        
        <?php if (isset($errore))
            echo "<p class='text error'>$errore</p>";
        ?>
        
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <input class="button rounded" type="submit"  value="Genera calendario">
    </form>
</div>


</div>


</body>

</html>