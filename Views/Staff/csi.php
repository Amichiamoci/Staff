<?php
use Amichiamoci\Utils\Security;

$amichiamoci_full_address = Security::LoadEnvironmentOfFromFile(
    var: 'LEGAL_ADDRESS', 
    default: 'Via del Seminario, 68, Livorno, 57121, LI',
);

$amichiamoci_address_parts = explode(separator: ',', string: $amichiamoci_full_address, limit: 5);
for ($i = 0; $i < 5; $i++) {
    if (empty($amichiamoci_address_parts[$i])) {
        $amichiamoci_address_parts[$i] = '';
    }
}

$codice_comitato_csi = Security::LoadEnvironmentOfFromFile(var: 'CODICE_COMITATO_CSI', default: '');
$codice_societa_csi = Security::LoadEnvironmentOfFromFile(var: 'CODICE_SOCIETA_CSI', default: '');


?>

<h1>
    Tesseramenti per C.S.I.
</h1>
<p class="ps-1 pe-1">
    I valori che vengono riportati nelle tabelle rispecchiano le attuali iscrizioni registrate nel sistema.
</p>

<button 
    type="button"
    onclick="exportTables()"
    class="btn btn-outline-primary mt-2"
    title="Esporta tabella"
>
    Esporta come foglio Excel &nbsp;
    <i class="bi bi-file-earmark-arrow-down"></i>
</button>

<table id="csi" class="table table-striped border border-1">
    <thead>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="8"> 
                TESSERAMENTO CENTRO SPORTIVO ITALIANO
            </td>
        </tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="2"> 
                <strong>Comitato di:</strong>
            </td>
            <td colspan="2" contenteditable="true"> 
                LIVORNO 
            </td>
            <td colspan="2"> 
                <strong>Codice Comitato:</strong> 
            </td>
            <td colspan="2" contenteditable="true">
                <?= htmlspecialchars(string: $codice_comitato_csi) ?>
            </td>
        </tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="2"> 
                <strong>Società sportiva:</strong> 
            </td>
            <td colspan="2" contenteditable="true"> 
                CIRCOLO SPORTIVO DIOCESANO AMICHIAMOCI 
            </td>
            <td colspan="2"> 
                <strong>Codice Società:</strong> 
            </td>
            <td colspan="2" contenteditable="true">
                <?= htmlspecialchars(string: $codice_societa_csi) ?>
            </td>
        </tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="2"> </td>
            <td colspan="9"> </td>
            <td colspan="9"> <strong>Attività Qualifiche</strong> </td>
            <td colspan="4"> </td>
            <td> <strong>Dis</strong> </td>
            <td colspan="3"> <strong>Privacy (A)</strong> </td>
        </tr>
        <tr>
            <td> </td>
            <td> <strong>N°</strong> </td>
            <td> <strong>COGNOME</strong> </td>
            <td> <strong>NOME</strong> </td>
            <td> <strong>SESSO</strong> </td>
            <td> <strong>NATO IL</strong> </td>
            <td> <strong>LUOGO NASCITA</strong> </td>

            <td> <strong>INDIRIZZO</strong> </td>
            <td> <strong>CIVICO</strong> </td>
            <td> <strong>COMUNE</strong> </td>
            <td> <strong>CAP</strong> </td>
            <td> <strong>Prov</strong> </td>

            <td> <strong>TT</strong> </td>
            <td> <strong>1</strong> </td>
            <td> <strong>2</strong> </td>
            <td> <strong>1</strong> </td>
            <td> <strong>2</strong> </td>
            <td> <strong>3</strong> </td>
            <td> <strong>4</strong> </td>
            <td> <strong>5</strong> </td>

            <td> <strong>PREF</strong> </td>
            <td> <strong>TELEFONO</strong> </td>
            <td> <strong>CODICE FISCALE</strong> </td>
            <td> <strong>EMAIL</strong> </td>

            <td> <strong> (C)</strong> </td>
            <td> <strong> 1) </strong> </td>
            <td> <strong> 2) </strong> </td>
            <td> <strong> 3) </strong> </td>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1;
            foreach ($iscrizioni as $iscrizione) {
            if (!($iscrizione instanceof Amichiamoci\Models\TesseramentoCSI)) continue; ?>
            <tr>
                <td> </td>
                <td scope="row" contenteditable="true"><?= $i++ ?></td>
                <td contenteditable="true"><?= htmlspecialchars(string: $iscrizione->Cognome) ?></td>
                <td contenteditable="true"><?= htmlspecialchars(string: $iscrizione->Nome) ?></td>
                <td contenteditable="true"><?= htmlspecialchars(string: $iscrizione->Sesso) ?></td>
                <td contenteditable="true"><?= htmlspecialchars(string: $iscrizione->DataNascita) ?></td>
                <td contenteditable="true"><?= htmlspecialchars(string: $iscrizione->LuogoNascita) ?></td>
                
                <?php for ($j = 0; $j < 5; $j++) { ?>
                    <td contenteditable="true">
                        <small>
                            <?= htmlspecialchars(string: $amichiamoci_address_parts[$j]) ?>
                        </small>
                    </td>
                <?php } ?>

                <td contenteditable="true">AT</td>
                <td contenteditable="true">PR</td>
                <?php for ($j = 0; $j < 7; $j++) { ?>
                    <td contenteditable="true"> </td>
                <?php } ?>

                <td>
                    <?php if (isset($iscrizione->Telefono)) { ?>
                        <a href="tel:<?= htmlspecialchars(string: $iscrizione->Telefono) ?>">
                            <?= htmlspecialchars(string: $iscrizione->Telefono) ?>
                        </a>
                    <?php } ?>
                </td>
                <td contenteditable="true">
                    <output><?= htmlspecialchars(string: $iscrizione->CodiceFiscale) ?></output>
                </td>
                <td>
                    <?php if (isset($iscrizione->Email)) { ?>
                        <a href="mailto:<?= htmlspecialchars(string: $iscrizione->Email) ?>">
                            <?= htmlspecialchars(string: $iscrizione->Email) ?>
                        </a>
                    <?php } ?>
                </td>

                <td contenteditable="true"> </td>
                <td contenteditable="true" colspan="3"> </td>
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

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js" defer></script>
<script>
    function exportTables()
    {
        const table = document.getElementById('csi');
        const wb = XLSX.utils.book_new();
        const sheet = XLSX.utils.table_to_sheet(table);
        sheet["!cols"] = [
            {  
                wch: 2 // <empty>
            },
            {
                wch: 6 // N°
            },
            {
                wch: 15 // Cognome
            },
            {
                wch: 15 // Nome
            },
            {
                wch: 6 // Sesso
            },
            {
                wch: 15 // Nato il
            },
            {
                wch: 20 // Luogo nascita
            },
            {
                wch: 20 // Indirizzo
            },
            {
                wch: 8 // Civico
            },
            {
                wch: 20 // Comune
            },
            {
                wch: 12 // CAP
            },
            {
                wch: 8 // Provincia
            },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },

            {
                wch: 13 // Telefono
            },
            {
                wch: 20 // Codice Fiscale
            },
            {
                wch: 25 // Email
            },
            
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
            {   wch: 6 },
        ];
        XLSX.utils.book_append_sheet(wb, sheet, "Inserimento Tesserati");
        if (!wb.Props) 
            wb.Props = {};
        wb.Props.Title = "Iscrizioni <?= SITE_NAME ?> <?= date(format: "Y") ?>";
        wb.Props.Author = "<?= SITE_NAME ?>";

        XLSX.writeFile(wb, "Iscrizioni-<?= SITE_NAME ?>-<?= date(format: "Y")?>.xlsx");
    }
</script>