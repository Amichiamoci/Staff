<?php
    include "../check_login.php";
    if (!User::$Current->is_admin)
    {
        header("Location: index.php");
        exit;
    }
    if (
        isset($_POST["punti"]) && 
        isset($_POST["parrocchia"]) && 
        isset($_POST["edizione"]))
    {
        header("Content-Type: application/json");
        $punti = $_POST["punti"];
        $parrocchia = $_POST["parrocchia"];
        $edizione = $_POST["edizione"];
        if (PunteggioParrocchia::Insert($connection, $edizione, $parrocchia, $punti))
        {
            echo "{ \"res\": 1 }";
        } else {
            echo "{ \"res\": 0 }";
        }
        exit;
    }
?>
<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Punti parrocchia</title>
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<?php include "./curr-edition.php"; ?>

<hr>

<!-- Form ----------------------------------------------------------------- -->

<div class="grid baseline">
    <div class="column col-100 flex vertical">
        <h3>
            Punteggi per parrocchia
        </h3>
    </div>
    <div class="column col-100 flex vertical">
        <table class="default-table">
            <thead>
                <tr>
                    <th>
                        Parrocchia
                    </th>
                    <th>
                        Punti
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php 
                $punteggi = PunteggioParrocchia::All($connection, $edizione->id);
                foreach ($punteggi as $punteggio)
                {
                    ?>
                    <tr>
                        <td style="padding: .2em .5em .2em 1.3em;">
                            <?= $punteggio->parrocchia ?>
                        </td>
                        <td>
                            <input type="text" style="width:100%; text-align: center;"
                                onchange="AggiornaPunteggioParrocchia(<?= $punteggio->id_parrocchia ?>)" 
                                value="<?= htmlspecialchars($punteggio->punteggio) ?>"
                                id="parrocchia-<?= $punteggio->id_parrocchia ?>">
                        </td>
                    </tr>
                    <?php
                }
            ?>
            </tbody>
        </table>
        
    </div>
</div>

<script type="text/javascript">
    async function AggiornaPunteggioParrocchia(parr)
    {
        if (!parr)
            return;
        const p = document.getElementById('parrocchia-' + parr);
        if (!p)
            return;
        const o = await post_async_json('punteggi-parrocchie.php',{
            'punti': p.value,
            'edizione': <?= $edizione->id ?>,
            'parrocchia': parr
        });
        return console.log(parr, o);
    }
</script>

</div>

</body>

</html>