<?php
    if (!isset($_COOKIE["id_anagrafica"]) && !isset($_GET["cambia-parrocchia"]) && !isset($_POST["cambia"]))
    {
        header("Location: iscrizioni/crea-anagrafica.php?success=../diventa-staff");
        exit;
    }
    include "../check_login.php";
    if (User::$Current->staff_id != 0 && !isset($_GET["cambia-parrocchia"]) && !isset($_POST["cambia"]))
    {
        header("Location: index.php");
        exit;
    }
    $id_anagrafica = 0;
    if (isset($_COOKIE["id_anagrafica"]))
        $id_anagrafica = (int)$_COOKIE["id_anagrafica"];
    $parrocchia = 0;
    if (isset($_POST["staff_submit"]))
    {
        if (isset($_POST["parrocchia"]))
        {
            $parrocchia = (int)$_POST["parrocchia"];
        }
        if (isset($_POST["cambia"]))
        {
            if (Staff::ChangeParrocchia($connection, User::$Current->staff_id, $parrocchia))
            {
                Cookie::Set("esit", "Parrocchia cambiata correttamente", 3600);
            }
            header("Location: index.php");
            exit;
        } else {
            if (Staff::Create($connection, $id_anagrafica, $user_id, $parrocchia) != 0)
            {
                Cookie::Delete("id_anagrafica");
                header("Location: partecipa.php");
                exit;
            }
        }
        $errore = "&Egrave; avvenuto un errore, non &egarve; stato possibile eseguire la query adesso, ritorna pi&ugrave; tardi.";
        
    }
    $lista_parrocchie = Parrocchia::GetAll($connection);
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Diventa Staff</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Form ----------------------------------------------------------------- -->

<section class="full-h flex center">
    <div class="grid">
        <div class="column col-100">
            <form action="diventa-staff.php" method="post" class="login-form">
                <?php if (isset($_GET["cambia-parrocchia"])) { ?>
                    <h5>
                        Stai cambiando parrocchia
                    </h5>
                    <p class="text">
                        Ti preghiamo di non abusare di questa funzionalit&agrave;
                    </p>
                    <input type="hidden" name="cambia" value="1">
                <?php } else { ?>
                    <h5>
                        Ti stai registrando come staff ad Amichiamoci
                    </h5>
                    <p class="text">
                        Una volta registrato avrai accesso <strong>permanente</strong> al portale da staffista, 
                        tramite il quale potrai iscrivere ragazzi della tua parrocchia e molto altro.<br>
                        Ogni nuova edizione di Amichiamoci ti verr&agrave; chiesto di scegliere se partecipare o meno,
                        e, in caso di risposta affermativa, di specificare la taglia della maglia da staff e le commissioni di cui intendi fare parte.
                    </p>
                <?php } ?>
                <?php if (isset($errore)) { ?>
                    <p class="error">
                        <?php echo $errore; ?>
                    </p>
                <?php } ?>
                <div class="input-box flex v-center wrap">
                    <label for="parrocchia">
                        Parrocchia
                    </label>
                    <select name="parrocchia" id="parrocchia" required>
                        <?php
                            foreach ($lista_parrocchie as $parr)
                            {
                                $label = htmlspecialchars($parr->nome);
                                $id = $parr->id;
                                if ($id == $parrocchia)
                                {
                                    echo "<option value='$id' selected='selected'>$label</option>\n";
                                } else {
                                    echo "<option value='$id'>$label</option>\n";
                                }
                            }
                        ?>
                    </select>
                </div>
                <input class="button rounded" type="submit" name="staff_submit" value="Conferma">
            </form>
        </div>
    </div>
</section>

</div>

</body>

</html>