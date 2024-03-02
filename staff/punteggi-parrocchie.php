<?php
    include "../check_login.php";
    if (!$anagrafica->is_admin)
    {
        header("Location: index.php");
        exit;
    }
    if (
        isset($_POST["punti"]) && 
        isset($_POST["parrocchia"]))
    {
        header("Content-Type: application/json");
        $punti = sql_sanitize($_POST["punti"]);
        $parrocchia = (int)$_POST["parrocchia"];
        $edizione = (int)$_POST["edizione"];
        $query = "REPLACE INTO punteggio_parrocchia (parrocchia, edizione, punteggio) VALUES ($parrocchia, $edizione, '$punti')";
        $res = mysqli_query($connection, $query);
        if ($res)
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
                $query = "SELECT l.id, l.nome, IF(p.punteggio IS NULL, '?', p.punteggio) AS \"punti\" 
                FROM lista_parrocchie_partecipanti l
                    LEFT OUTER JOIN punteggio_parrocchia p ON l.id = p.parrocchia
                WHERE p.edizione = $edizione->id
                ORDER BY nome ASC";
                $res = mysqli_query($connection, $query);
                if ($res)
                {
                    while ($row = $res->fetch_assoc())
                    {
                        $id = (int)$row["id"];
                        $nome = acc($row["nome"]);
                        $punti = $row["punti"];
                        ?>
                        <tr>
                            <td style="padding: .2em .5em .2em 1.3em;">
                                <?= $nome ?>
                            </td>
                            <td>
                                <input type="text" style="width:100%; text-align: center;"
                                    onchange="AggiornaPunteggioParrocchia(<?= $id ?>)" 
                                    value="<?= $punti ?>"
                                    id="parrocchia-<?= $id ?>">
                            </td>
                        </tr>
                        <?php
                    }
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
        })
        return console.log(parr, o);
    }
</script>

</div>

</body>

</html>