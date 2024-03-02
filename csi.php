<?php

include "./check_login.php";

?>


<!DOCTYPE html>
<html>

<head>
    <?php include "./parts/head.php";?>
	<title>Amichiamoci | Tesseramenti CSI</title>
    
</head>

<body>

<?php include "./parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section class="table-section flex vertical">
    <h3 style="text-align: center;">
        Assicurazioni per l'anno <?= date("Y")?>
    </h3>
    <p class="text" style="padding-inline: 1.5em;">
        I valori che vengono riportati nelle tabelle rispecchiano le attuali iscrizioni registrate nel sistema
    </p>
    <div class="grid">
        <div class="column col-100 flex vertical tables">
            <?= listaIscrizioni($connection, "lista-iscrizioni") ?>
        </div>
        <button type="button" onclick="exportTables()" class="button rounded cool" title="Esporta tabella">
            Esporta come foglio Excel &nbsp; <i class="fa-solid fa-file-export"></i>  
        </button>
    </div>
</section>

</div>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script type="text/javascript">
    function exportTables()
    {
        const table = document.getElementById('lista-iscrizioni');
        const wb = XLSX.utils.book_new();
        const sheet = XLSX.utils.table_to_sheet(table);
        sheet["!cols"] = [
            {
                wch: 6 //N°
            },
            {
                wch: 15 //Cognome
            },
            {
                wch: 15 //Nome
            },
            {
                wch: 6 //Sesso
            },
            {
                wch: 10 //Nato il
            },
            {
                wch: 20 //Luogo nascita
            },
            {
                wch: 13 //Telefono
            },
            {
                wch: 23 //Email
            }];
        XLSX.utils.book_append_sheet(wb, sheet, "Lista Iscrizioni <?= date("Y") ?>");
        if (!wb.Props) 
            wb.Props = {};
        wb.Props.Title = "Iscrizioni Amichiamoci <?= date("Y") ?>";
        wb.Props.Author = "Amichiamoci A.S.D.";

        XLSX.writeFile(wb, "Iscrizioni-<?= date("Y")?>.xlsx");
    }
</script>
</body>

</html>