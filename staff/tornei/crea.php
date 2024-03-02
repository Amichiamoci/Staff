<?php
include "../../check_login.php";

$dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

if (!$dati_staff->is_in("Tornei") && !$anagrafica->is_admin) {
    header("Location: ../index.php");
    exit;
}
$tipo = 0;
$sport = 0;
$nome = "";
if (
    isset($_POST["nome_torneo"]) &&
    isset($_POST["tipo_torneo"]) &&
    isset($_POST["sport_torneo"]))
{
    $tipo = (int)$_POST["tipo_torneo"];
    $sport = (int)$_POST["sport_torneo"];
    $nome = $_POST["nome_torneo"];
    if (Torneo::Create($connection, $sport, $nome, $tipo))
    {
        header("Location: ../index.php");
        exit;
    }
    $errore = "Impossibile creare il torneo ora, riprova pi&ugrave; tardi!";
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
    <form action="crea.php" method="post">
        <h3>
            Stai creando un torneo
        </h3>
        <?php
        if (isset($errore))
            echo "<p class='error'>$errore</p>"
        ?>
        <div class="input-box flex v-center wrap">

            <label for="nome">
                Nome
            </label>
            <input type="text" id="nome" name="nome_torneo" size="64" 
                maxlength="256" value="<?=$nome?>" required spellcheck="true">

            <label for="sport">
                Tipo
            </label>
            <select name="tipo_torneo" id="tipo" required>
                <?php
                    $tipi_torneo = TipoTorneo::GetAll($connection);
                    foreach ($tipi_torneo as $t)
                    {
                        $label = acc($t->label);
                        $id = $t->id;
                        if ($id == $tipo)
                        {
                            echo "<option value='$id' selected='selected'>$label</option>\n";
                        } else {
                            echo "<option value='$id'>$label</option>\n";
                        }
                    }
                ?>
            </select>

            <label for="sport">
                Sport
            </label>
            <select name="sport_torneo" id="sport" required>
                <?php
                    $lista_sport = Sport::GetAll($connection);
                    foreach ($lista_sport as $sport)
                    {
                        $label = acc($sport->label);
                        $id = $sport->id;
                        if ($id == $chosen_sport)
                        {
                            echo "<option value='$id' selected='selected'>$label</option>\n";
                        } else {
                            echo "<option value='$id'>$label</option>\n";
                        }
                    }
                ?>
            </select>
        </div>
        <input class="button rounded" type="submit" name="torneo_submit" value="Conferma">
    </form>
</div>

</div>


</body>

</html>