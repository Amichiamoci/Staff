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
    class="btn btn-outline-primary mt-2 mb-2"
    title="Esporta tabella"
>
    Esporta come foglio Excel &nbsp;
    <i class="bi bi-file-earmark-arrow-down"></i>
</button>

<table id="csi" class="table table-striped border border-1">
    <thead>
        <tr><td colspan="32"></td></tr>
        <tr>
            <td> </td>
            <td colspan="31"> 
                TESSERAMENTO CENTRO SPORTIVO ITALIANO
            </td>
        </tr>
        <tr><td colspan="32"></td></tr>
        <tr>
            <td></td>
            <td colspan="2"> 
                <strong>Comitato di:</strong>
            </td>
            <td colspan="4" contenteditable="true"> 
                LIVORNO 
            </td>
            <td></td>
            <td colspan="4"> 
                <strong>Codice Comitato</strong> 
            </td>
            <td colspan="3" contenteditable="true">
                <?= htmlspecialchars(string: $codice_comitato_csi) ?>
            </td>
        </tr>
        <tr><td colspan="32"></td></tr>
        <tr>
            <td></td>
            <td colspan="2"> 
                <strong>Società sportiva:</strong> 
            </td>
            <td colspan="4" contenteditable="true"> 
                CIRCOLO SPORTIVO DIOCESANO AMICHIAMOCI 
            </td>
            <td></td>
            <td colspan="4"> 
                <strong>Codice Società</strong> 
            </td>
            <td colspan="3" contenteditable="true">
                <?= htmlspecialchars(string: $codice_societa_csi) ?>
            </td>
        </tr>
        <tr>
            <td colspan="30"></td>
            <td>
                <strong>Data</strong>
            </td>
            <td>
                <strong>Firma (B)</strong>
            </td>
        </tr>
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
                <td contenteditable="true"> <strong>X</strong> </td>
                <td contenteditable="true"> </td>
                <td contenteditable="true"> </td>
            </tr>
        <?php } ?>
        <tr><td colspan="32"></td></tr>
        <tr>
            <td colspan="32">
                <small>
                    Informazioni Privacy (N.B. il testo aggiornato di queste informazioni è conservato, 
                    a disposizione di chiunque lo richieda, presso i Comitati territoriali e sul sito 
                    Internet istituzionale del CSI) - Ai sensi degli artt. 13 e 14 del Regolamento UE 2016/67
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    Base giuridica di questi trattamenti si rinviene nell’essere gli stessi necessari
                    all’esecuzione di un contratto di cui l’interessato è parte nonché  per adempiere
                    agli obblighi cui è soggetto il titolare del trattamento. I dati appartenenti
                    a categorie p
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    Titolare del trattamento è il CENTRO SPORTIVO ITALIANO – Via della Conciliazione, 1 - 00193 ROMA. 
                    I dati potranno essere comunicati ai soggetti ai quali l'invio si renda obbligatorio 
                    in forza di Legge o regolamento e, in particolare, al CONI e CONINET SPA
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    I trattamenti per i quali si richiede un consenso, invece, 
                    sono da ritenersi facoltativi e la mancata concessione dello stesso impedisce esclusivamente 
                    lo svolgimento di tali attività accessorie. I dati, fatto salvo ogni obbligo di legge, 
                    saranno conserva
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <strong>
                    IL/LA SOTTOSCRITTO/A LETTE E COMPRESE LE INFORMAZIONI CHE PRECEDONO
                </strong>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    1) Diritti di immagine – Circa l’archiviazione e il libero utilizzo da parte del CSI,
                    senza limiti di tempo e senza finalità di lucro delle proprie immagini fotografiche o
                    audiovisive (o delle immagini del/della proprio/a figlio/a in caso di tesserato min
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    2) Attività promozionali del CSI - In relazione al trattamento dei dati personali
                    dell’interessato per finalità di marketing diretto/ricerche di mercato del CSI nei termini sopra esposti
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    3) Attività promozionali di Terzi - In relazione al trattamento dei dati
                    personali dell’interessato per finalità di comunicazione al CONI ovvero ai soggetti
                    sopra indicati per loro proprie iniziative di marketing diretto/ricerche di mercato
                    nei termini so
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    A) Per ognuna delle finalità sopra elencate 1), 2) e 3) deve essere indicata con una X
                    il consenso al trattamento da parte del tesserato.
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    B) Firma del tesserato da apporsi di proprio pugno da parte del tesserato o di
                    chi esercita la potestà genitoriale in caso di minorenne  (in caso di genitori
                    separati la firma deve essere di entrambi).
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                <small>
                    C) Evidenziare con una X gli atleti disabili.
                </small>
            </td>
        </tr>
        <tr>
            <td colspan="32">
                Il sottoscritto, Presidente e/o Legale Rappresentante dell’Associazione sportiva,
                Società sportiva, Circolo culturale sportivo, a conoscenza delle norme relative
                al tesseramento e all’assicurazione stipulata dal CSI per i suoi tesserati,
                dichiara sotto la
            </td>
        </tr>
        <tr> <td colspan="32"> </td> </tr>
        <tr>
            <td> </td>
            <td> <strong>Data:</strong> </td>
            <td><?= date(format: "d/m/Y") ?></td>
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
                wch: 16 // Luogo nascita
            },
            {
                wch: 20 // Indirizzo
            },
            {
                wch: 5 // Civico
            },
            {
                wch: 12 // Comune
            },
            {
                wch: 8 // CAP
            },
            {
                wch: 4 // Provincia
            },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },

            {
                wch: 13 // Telefono
            },
            {
                wch: 20 // Codice Fiscale
            },
            {
                wch: 25 // Email
            },
            
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },
            {   wch: 3 },

            {
                wch: 8 // Data
            },
            {
                wch: 15 // Firma
            },
        ];
        XLSX.utils.book_append_sheet(wb, sheet, "Inserimento Tesserati");
        if (!wb.Props) 
            wb.Props = {};
        wb.Props.Title = "Iscrizioni <?= SITE_NAME ?> <?= date(format: "Y") ?>";
        wb.Props.Author = "<?= SITE_NAME ?>";

        XLSX.writeFile(wb, "Iscrizioni-<?= SITE_NAME ?>-<?= date(format: "Y")?>.xlsx");
    }
</script>