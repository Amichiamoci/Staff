<?php

    include "../../check_login.php";
    $dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

    $add_link = ($dati_staff->is_subscribed() && $dati_staff->is_referente) || $anagrafica->is_admin;
    $selected = 0;
    if (isset($_GET["id"]) && !is_array($_GET["id"]))
    {
        $selected = (int)$_GET["id"];
    }
    if ($selected === 0)
    {
        $selected = $dati_staff->id_parrocchia;
    }
    $include_all = 5;
    if (isset($_GET["error"]))
    {
        $include_all = 6;
    }
?>


<!DOCTYPE html>
<html>

<head>
    <?php include "../../parts/head.php";?>
	<title>Amichiamoci | Elenco Iscritti per parrocchia</title>
</head>

<body>

<?php include "../../parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section id="table-section" class="table-section flex center">
    <div class="grid">
        <form class="column col-100">
            <div class="input-box flex v-center wrap">
                <label for="parrocchia">
                    Parrocchia:
                </label>
                <select id="parrocchia" class="select">
                    <?php 
                        $lista_parrocchie = parrocchie($connection);
                        foreach ($lista_parrocchie as $parr)
                        {
                            $label = acc($parr->nome);
                            $id = $parr->id;
                            if ($id == $selected)
                            {
                                echo "<option value='$id' selected='selected'>$label</option>\n";
                            } else {
                                echo "<option value='$id'>$label</option>\n";
                            }
                        }
                    ?>
                </select>
            </div>
            <script type="text/javascript">
                (() => {
                    const select = document.getElementById('parrocchia');
                    const f = () => {
                        const id = Number(select.value);
                        select.removeEventListener('change', f);
                        window.location.replace('?id=' + id + 
                            (new URLSearchParams(window.location.search).has('error') ? '&error' : ''));
                    };
                    select.addEventListener('change', f);
                })();
            </script>
        </form>
        <?php include "./menu.php"; ?>
        <div class="column col-100 flex vertical">
            <?php if ($include_all === 5) {
                echo getAnagraficheList($connection, date("Y"), $selected, $add_link);
            } else {
                echo getIscrizioniSbagliate($connection, date("Y"), $selected, $add_link);
            }
            ?>
        </div>
    </div>
</section>

</div>

</body>

</html>