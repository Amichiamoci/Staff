<?php

include "../../check_login.php";
$dati_staff = Staff::Get($connection, User::$Current->staff_id);
$squadra = new Squadra(null, null, null, null, null, null, null, null);
//Imposto valori di default
$squadra->id_parrocchia = $dati_staff->id_parrocchia;
$squadra->parrocchia = $dati_staff->parrocchia;

$curr_edition = Edizione::Current($connection);
if (!$curr_edition->ok())
{
    header("Location: ../");
    exit;
}
if (isset($_GET["id"]) && !is_array($_GET["id"]))
{
    //Carica squadra qui
    $squadra = Squadra::Load($connection, (int)$_GET["id"]);
}
if (
    isset($_POST["id"]) &&
    isset($_POST["nome"]) && 
    isset($_POST["parrocchia"]) && 
    isset($_POST["sport"]) &&
    isset($_POST["membri"]))
{
    $squadra->id = (int)$_POST["id"];
    $squadra->nome = $_POST["nome"];
    $squadra->id_parrocchia = (int)$_POST["parrocchia"];
    $squadra->id_sport = (int)$_POST["sport"];
    $squadra->id_iscr_membri = $_POST["membri"];

    if ($squadra->id === 0)
    {
        if (Squadra::Create(
            $connection, 
            $squadra->nome, 
            $squadra->id_parrocchia, 
            $squadra->id_sport, 
            $squadra->id_iscr_membri,
            $curr_edition->id))
        {
            header("Location: index.php");
            exit;
        } 

        $errore = "Impossibile creare la squadra.\nRiprova pi&ugrave; tardi";
    } else {
        if (Squadra::Edit(
            $connection, 
            $squadra->id,
            $squadra->nome, 
            $squadra->id_parrocchia, 
            $squadra->id_sport, 
            $squadra->id_iscr_membri))
        {
            header("Location: index.php");
            exit;
        } 

        $errore = "Impossibile modificare la squadra \"$squadra->nome\".\nRiprova pi&ugrave; tardi";
    }
    
}
$lista_parrocchie = Parrocchia::GetAll($connection);
$lista_sport = Sport::GetAll($connection);

?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
    <?php if ($squadra->id === 0) { ?>
	    <title>Amichiamoci | Crea Squadra</title>
    <?php } else { ?>
	    <title>Amichiamoci | Modifica Squadra</title>
    <?php } ?>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">
<?php include "../curr-edition.php"; ?>
<!-- Staff ----------------------------------------------------------------- -->
<section class="admin flex center">
    <div class="column col-100 flex center">
        <form action="crea.php" method="post">
            <h3>
                Stai creando una squadra
            </h3>
            <?php
            if (isset($errore))
                echo "<p class=\"text error\">$errore</p>"
            ?>
            <input type="hidden" value="<?= $squadra->id ?>" name="id">
            <div class="input-box flex v-center wrap">

                <label for="nome">
                    Nome
                </label>
                <input type="text" id="nome" name="nome" size="64" 
                    maxlength="128" pattern="[A-Za-z0-9\s]{4,}" value="<?= htmlspecialchars($squadra->nome) ?>" required spellcheck="false">

                <label for="parrocchia">
                    Parrocchia
                </label>
                <select name="parrocchia" required>
                    <?php
                        foreach ($lista_parrocchie as $parr)
                        {
                            $label = htmlspecialchars($parr->nome);
                            $id = $parr->id;
                            if ($id == $squadra->id_parrocchia)
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
                <select name="sport" required>
                    <?php
                        foreach ($lista_sport as $sport)
                        {
                            $label = htmlspecialchars($sport->label);
                            $id = $sport->id;
                            if ($id == $squadra->id_sport)
                            {
                                echo "<option value='$id' selected='selected'>$label</option>\n";
                            } else {
                                echo "<option value='$id'>$label</option>\n";
                            }
                        }
                    ?>
                </select>

                <!--Mettere qui membri squadra-->
                <label for="membri">
                    Membri possibili per l'edizione <?= $curr_edition->year ?>
                </label>
                <input type="hidden" id="membri" name="membri" required value="<?= $squadra->id_iscr_membri ?>">
                <style type="text/css">
                    #member-list {
                        width: 75%;
                        height: fit-content;
                        max-height: 60vh;
                        overflow-y: scroll;
                    }
                    #member-list > li {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: fit-content;
                    }
                </style>
                <ul id="member-list">
                    <?php
                        $iscritti = Iscrizione::GetAll($connection);
                        $lista_membri = explode(",", $squadra->id_iscr_membri);
                        $last_p = null;
                        foreach ($iscritti as $iscritto)
                        {
                            if (!isset($last_p) || $last_p !== $iscritto->parrocchia)
                            {
                                $last_p = $iscritto->parrocchia;
                                echo "<li><h3>$last_p</h3></li>\n";
                            }
                            
                            $label = htmlspecialchars($iscritto->nome);
                            $id = $iscritto->id;
                            echo "<li>\n<label for='member-$id'>$label</label>";
                            echo "<div class='checkbox'>\n";
                            if (in_array($id, $lista_membri))
                            {
                                echo "<input type='checkbox' data-id='$id' class='possible-member' id='member-$id' checked>";
                            } else {
                                echo "<input type='checkbox' data-id='$id' class='possible-member' id='member-$id'>";
                            }
                            echo "\n</div>\n</li>";
                        }
                    ?>
                </ul>
                <script>
                    const input = document.getElementById('membri');
                    const possibleMembers = [...document.querySelectorAll('.possible-member')];
                    var list = [];
                    function UpdateList()
                    {
                        list.sort();
                        input.value = list.join(',');
                    }
                    possibleMembers.forEach(elem => {
                        if (elem.checked)
                        {
                            //First load
                            const id = elem.getAttribute('data-id');
                            list.push(id);
                        }
                        elem.addEventListener('change', evt => {
                            const id = evt.target.getAttribute('data-id');
                            if (evt.target.checked)
                            {
                                if (!list.includes(id))
                                {
                                    list.push(id);
                                }
                            } else {
                                list = list.filter(member => member != id);
                            }
                            UpdateList();
                        });
                    });
                </script>
            </div>
            <input class="button rounded" type="submit" value="Conferma" onclick="UpdateList()">
        </form>
    </div>
</section>

</div>

</body>

</html>