<?php
include "../../check_login.php";

$dati_staff = Staff::Get($connection, User::$Current->staff_id);

if (!$dati_staff->is_in("Tornei") && !User::$Current->is_admin && !$dati_staff->is_referente) {
    header("Location: ../index.php");
    exit;
}
$torneo = 0;
$squadra = 0;
if (
    isset($_POST["torneo"]) &&
    isset($_POST["squadra"]))
{
    $torneo = (int)$_POST["torneo"];
    $squadra = (int)$_POST["squadra"];
    if (Torneo::Iscrivi($connection, $torneo, $squadra))
    {
        header("Location: index.php");
        exit;
    }
    $errore = "Impossibile iscrivere al torneo ora, riprova pi&ugrave; tardi!";
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
    <form action="iscrivi.php" method="post">
        <h3>
            Stai iscrivendo una squadra ad un torneo
        </h3>
        <?php
        if (isset($errore))
            echo "<p class='error'>$errore</p>"
        ?>
        <div class="input-box flex v-center wrap">

            <label for="torneo">
                Torneo
            </label>
            <select name="torneo" id="torneo" required>
                <?php
                    $tornei = Torneo::GetAll($connection);
                    foreach ($tornei as $t)
                    {
                        $label = htmlspecialchars($t->nome . " | " . $t->sport . ": " . $t->tipo . ", " . $t->numero_squadre . " squadre");
                        $id = $t->id;
                        if ($id == $torneo)
                        {
                            echo "<option value='$id' selected='selected'>$label</option>\n";
                        } else {
                            echo "<option value='$id'>$label</option>\n";
                        }
                    }
                ?>
            </select>

            <label for="squadra">
                Squadra
            </label>
            <select name="squadra" id="squadra" required>
                <?php
                    $squadre = Squadra::List($connection, date("Y"));
                    foreach ($squadre as $s)
                    {
                        $label = htmlspecialchars($s->nome . " | " . $s->sport);
                        $id = $s->id;
                        if ($id == $squadra)
                        {
                            echo "<option value='$id' selected='selected'>$label</option>\n";
                        } else {
                            echo "<option value='$id'>$label</option>\n";
                        }
                    }
                ?>
            </select>
        </div>
        <input class="button rounded" type="submit" name="torneo_iscrivi_submit" value="Conferma">
    </form>
</div>


</div>


</body>

</html>