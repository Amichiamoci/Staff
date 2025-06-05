<h1>
    Tesseramenti per C.S.I.
</h1>
<p class="ps-1 pe-1">
    I valori che vengono riportati nelle tabelle rispecchiano le attuali iscrizioni registrate nel sistema
</p>

<table id="csi" class="table table-striped border border-1">
    <thead>
        <tr><td colspan="8"></td></tr>
        <tr><td colspan="8"> CENTRO SPORTIVO ITALIANO </td></tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="2"> <strong>Comitato di:</strong> </td>
            <td colspan="2" contenteditable="true"></td>
            <td colspan="2"> <strong>Codice Comitato:</strong> </td>
            <td colspan="2" contenteditable="true"></td>
        </tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="2"> <strong>Società sportiva:</strong> </td>
            <td colspan="2" contenteditable="true">Amichiamoci A.S.D.</td>
            <td colspan="2"> <strong>Codice Società:</strong> </td>
            <td colspan="2" contenteditable="true"></td>
        </tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td> <strong>N°</strong> </td>
            <td> <strong>COGNOME</strong> </td>
            <td> <strong>NOME</strong> </td>
            <td> <strong>SESSO</strong> </td>
            <td> <strong>LUOGO NASCITA</strong> </td>
            <td> <strong>NATO IL</strong> </td>
            <td> <strong>Telefono</strong> </td>
            <td> <strong>Email</strong> </td>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1;
            foreach ($iscrizioni as $iscrizione) {
            if (!($iscrizione instanceof Amichiamoci\Models\TesseramentoCSI)) continue; ?>
            <tr>
                <td scope="row"><?= $i++ ?></td>
                <td><?= htmlspecialchars(string: $iscrizione->Cognome) ?></td>
                <td><?= htmlspecialchars(string: $iscrizione->Nome) ?></td>
                <td><?= htmlspecialchars(string: $iscrizione->Sesso) ?></td>
                <td><?= htmlspecialchars(string: $iscrizione->LuogoNascita) ?></td>
                <td><?= htmlspecialchars(string: $iscrizione->DataNascita) ?></td>
                <td>
                    <?php if (isset($iscrizione->Telefono)) { ?>
                        <a href="tel:<?= htmlspecialchars(string: $iscrizione->Telefono) ?>">
                            <?= htmlspecialchars(string: $iscrizione->Telefono) ?>
                        </a>
                    <?php } ?>
                </td>
                <td>
                    <?php if (isset($iscrizione->Email)) { ?>
                        <a href="mailto:<?= htmlspecialchars(string: $iscrizione->Email) ?>">
                            <?= htmlspecialchars(string: $iscrizione->Email) ?>
                        </a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td> <strong>Data:</strong> </td>
            <td colspan="2"><?= date(format: "d/m/Y") ?></td>
            <td colspan="2"> <strong>Il Presidente:</strong> </td>
            <td colspan="3">________________________</td>
        </tr>
    </tbody>
</table>

<button 
    type="button"
    onclick="exportTables()"
    class="btn btn-outline-primary mt-2"
    title="Esporta tabella">
    Esporta come foglio Excel &nbsp;
    <i class="bi bi-file-earmark-arrow-down"></i>
</button>

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js" defer></script>
<script>
    function exportTables()
    {
        const table = document.getElementById('csi');
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
                wch: 15 //Nato il
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
        XLSX.utils.book_append_sheet(wb, sheet, "Lista Iscrizioni <?= date(format: "Y") ?>");
        if (!wb.Props) 
            wb.Props = {};
        wb.Props.Title = "Iscrizioni <?= SITE_NAME ?> <?= date(format: "Y") ?>";
        wb.Props.Author = "<?= SITE_NAME ?> A.S.D.";

        XLSX.writeFile(wb, "Iscrizioni-<?= date(format: "Y")?>.xlsx");
    }
</script>