<?php
include "../../check_login.php";

$dati_staff = Staff::Get($connection, User::$Current->staff_id);

if (!$dati_staff->is_in("Tornei") && !User::$Current->is_admin) {
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
$torneo = Torneo::LoadIfToGenerate($connection, $id);
if (!isset($torneo))
{
    //Torneo non trovato, potrebbe avere gia' un calendario
    header("Location: ./index.php");
    exit;
}
if (isset($_POST["id"]))
{
    if (Torneo::GenerateCalendar($connection, $id))
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
            TORNEO <?= htmlspecialchars($torneo->nome) ?>
        </h2>
        <h4>
            <?= htmlspecialchars($torneo->sport) ?>
        </h4>
        <h4>
            <?= htmlspecialchars($torneo->tipo) ?>
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