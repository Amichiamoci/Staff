<?php

include "../check_login.php";

?>


<!DOCTYPE html>
<html>

<head>
    <?php include "../parts/head.php";?>
	<title>Amichiamoci | Elenco Maglie</title>
    
</head>

<body>

<?php include "../parts/nav.php";?>

<div class="container">

<!-- Table ----------------------------------------------------------------- -->

<section class="table-section flex vertical">
    <h3 style="text-align: center;">
        Lista maglie per l'anno <?= date("Y")?>
    </h3>
    <p class="text" style="padding-inline: 1.5em;">
        I valori che vengono riportati nelle tabelle rispecchiano le attuali iscrizioni registrate nel sistema
    </p>
    <div class="grid">
        <div class="column col-100 flex vertical tables">
            <?= listaMaglie($connection, false, "maglie-singoli", false) ?>
            <h4>
                Riepilogo per parrocchia
            </h4>
            <p class="text">
                Per aggiungere eventuali maglie &quot;<em>in pi&ugrave;</em>&quot; modificare i valori nelle celle della tabella che segue.<br>
                <b>Attenzione</b>: le modifiche che farai non saranno salvate nel sistema: potranno solo essere esportate nel file Excel tramite il tasto sotto.
                Per ogni maglia che aggiungeraisar&agrave; creata una riga nella tabella sopra, in modo da ricordarsi facilmente le aggiunte fatte.<br>
                Compilare la colonna "Colore" con il colore scelto per ciascuna parrocchia quest'anno
            </p>
            <?= listaMaglie($connection, true, "maglie-totali")	?>
        </div>
        <button type="button" onclick="exportTables()" class="button rounded cool" title="Esporta tabella">
            Esporta come foglio Excel &nbsp; <i class="fa-solid fa-file-export"></i>  
        </button>
    </div>
</section>

</div>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script type="text/javascript">
    var aggiunte = 0;
    function update_tables(cell)
    {
        if (!cell)
            return;
        const tbody = document.querySelectorAll('#maglie-singoli > tbody')[0];
        const data_ref = cell.getAttribute("data-ref");
        const corrections = [...document.querySelectorAll("#maglie-singoli > tbody > tr[data-correct]")];
        if (cell.innerHTML.indexOf(' ') >= 0)
            cell.innerHTML = cell.innerHTML.trim();
        const plus = Number(cell.innerHTML) - Number(cell.getAttribute("data-min"));
        if (!cell.hasAttribute('data-border'))
        {
            cell.setAttribute('data-border', cell.style.border);
        }
        if (isNaN(Number(cell.innerHTML)) || plus < 0 || cell.innerHTML == '')
        {
            cell.title = 'Valore non valido!';
            cell.style.border = '2px dashed red';
            return;
        }
        cell.title = '';
        cell.style.border = cell.getAttribute('data-border');
        if (plus > 0)
        {
            cell.title = 'Valore originale: ' + cell.getAttribute("data-min");
            cell.style.border = '2px solid black';
        }
        const diff = plus - corrections.length;
        function create_row()
        {
            const [parrocchia, taglia] = data_ref.split('|');
            const tr = document.createElement('tr');
            tr.setAttribute('data-correct', data_ref);
            tr.style.userSelect = 'none';
            tr.title = 'Riga aggiunta automaticamente';
            tr.innerHTML = `<td>Aggiunta</td><td>#${++aggiunte}</td><td>${parrocchia}</td><td>${taglia}</td>`;
            tbody.appendChild(tr);
        }
        function delete_row()
        {
            for (const correction of corrections)
            {
                if (correction.getAttribute("data-correct") === data_ref && !correction.hasAttribute("data-removed"))
                {
                    correction.setAttribute("data-removed", "1");
                    tbody.removeChild(correction);
                    return;
                }
            }
        }
        for (let i = 0; i < diff; i++)
        {
            create_row();
        }
        for (let i = 0; i > diff; i--)
        {
            delete_row();
        }
    }
    [...document.querySelectorAll("#maglie-totali > tbody > tr > td[data-min][data-ref]")].forEach(
        cell => cell.addEventListener('input', () => update_tables(cell)));
    function exportTables()
    {
        const table1 = document.getElementById('maglie-singoli');
        if (!table1)
        {
            alert("Errore interno");
            return;
        }
        const table2 = document.getElementById('maglie-totali');
        if (!table2)
        {
            alert("Errore interno");
            return;
        }
        const wb = XLSX.utils.book_new();
        const sheet1 = XLSX.utils.table_to_sheet(table1);
        sheet1["!cols"] = [
            {
                wch: 14 //Cognome
            },
            {
                wch: 12 //Nome
            },
            {
                wch: 25 //Parrocchia
            },
            {
                wch: 6 //Taglia
            }];
        const sheet2 = XLSX.utils.table_to_sheet(table2);
        sheet2["!cols"] = [
            {
                wch: 25 //Parrocchia
            },
            /*{
                wch: 17 //Colore
            },*/
            {
                wch: 4 //XS
            },
            {
                wch: 4 //S
            },
            {
                wch: 4 //M
            },
            {
                wch: 4 //L
            },
            {
                wch: 4 //XL
            },
            {
                wch: 4 //XXL
            },
            {
                wch: 4 //3XL
            }];
        XLSX.utils.book_append_sheet(wb, sheet1, "Ordini per persona");
        XLSX.utils.book_append_sheet(wb, sheet2, "Riepilogo");
        if (!wb.Props) 
            wb.Props = {};
        wb.Props.Title = "Maglie Amichiamoci <?= date("Y")?>";

        XLSX.writeFile(wb, "Maglie-<?= date("Y")?>.xlsx");
    }
</script>
</body>

</html>