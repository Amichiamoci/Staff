<?php
    include "../check_login.php";
    
    $errore = "";
    $def_edizione = 0;
    $maglia = "L";
    $tutte_commissioni = Commissione::All($connection);
    $commissioni_scelte = array();
    $dati_staff = Staff::Get($connection, User::$Current->staff_id);
    if (isset($_POST["partecipa_submit"]))
    {
        if (isset($_POST["edizione"]))
        {
            $def_edizione = (int)$_POST["edizione"];
        }
        if (isset($_POST["maglia"]))
        {
            $maglia = $_POST["maglia"];
        }
        foreach($tutte_commissioni as $commissione)
        {
            if (isset($_POST["commissione-$commissione->id"]))
            {
                $commissioni_scelte[] = $commissione->id;
            }
        }
        $is_referente = isset($_POST["referente"]);
        if (Staff::Partecipa($connection, User::$Current->staff_id, $def_edizione, $maglia, $commissioni_scelte, $is_referente))
        {
            header("Location: index.php");
            exit;
        }
    } elseif (isset($dati_staff))
    {
        $maglia = $dati_staff->maglia;
    }
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Partecipa</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Form ----------------------------------------------------------------- -->

<section class="flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="partecipa.php" method="post">
                <h5>
                    Ti stai iscrivendo come staff ad Amichiamoci 
                    <select name="edizione" required style="display: inline-block;">
                    <?php
                        $edizioni = Edizione::LoadAll($connection);
                        foreach($edizioni as $edizione)
                        {
                            if ($edizione->id === $def_edizione) {
                                echo "<option value='$edizione->id' selected='selected'>$edizione->year</option>\n";
                            } else {
                                echo "<option value='$edizione->id'>$edizione->year</option>\n";
                            }
                        }
                    ?>
                    </select>
                </h5>
                <?php
                if ($errore != "")
                    echo "<p class='error'>$errore</p>"
                ?>
                <p>
                    Ecco qui la lista delle <strong>commissioni</strong> a cui ti puoi unire
                </p>
                <div class="input-box flex v-center wrap">
                    <?php
                        foreach ($tutte_commissioni as $commissione)
                        {
                            $name = "commissione-$commissione->id";
                            $checked = (isset($dati_staff) && $dati_staff->is_in($commissione->nome)) ? "checked" : "";
                            ?>
                                <label for="<?= $name ?>" title="<?= htmlspecialchars($commissione->nome) ?>">
                                    <?= htmlspecialchars($commissione->nome) ?>
                                </label>
                                <div class="checkbox">
                                    <input type="checkbox" name="<?= $name ?>" id="<?= $name ?>" <?= $checked ?>>
                                </div>
                            <?php
                        }
                    ?>
                    <label for="maglia">Taglia maglietta</label>
                    <select name="maglia" id="maglia" required>
                        <?php
                            $taglie = Maglie::All();
                            foreach ($taglie as $taglia)
                            {
                                if ($taglia == $maglia)
                                {
                                    echo "<option value='$taglia' selected='selected'>$taglia</option>\n";
                                } else {
                                    echo "<option value='$taglia'>$taglia</option>\n";
                                }
                            }
                        ?>
                    </select>
                    <br>
                    <hr>
                    <br>
                    <label for="referente">
                        Referente parrocchiale
                    </label>
                    <div class="checkbox">
                        <input type="checkbox" name="referente" id="referente" <?= (isset($dati_staff) && $dati_staff->is_referente) ? "checked" : "" ?> >
                    </div>
                </div>
                <p>
                    Parrocchia: <?= htmlspecialchars($dati_staff->parrocchia) ?>
                </p>
                <input class="button rounded" type="submit" name="partecipa_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>