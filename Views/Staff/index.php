<?php
use Amichiamoci\Models\ProblemaIscrizione;
?>

<?php if ($user->Admin) { ?>
    <div class="input-group mb-2">
        <select id="parrocchia-selector" class="form-control">
            <?php foreach ($parrocchie as $parrocchia) { ?>
                <option value="<?= $parrocchia->Id ?>" 
                    <?= ($parrocchia->Id === $id_parrocchia) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(string: $parrocchia->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <select id="anno-selector" class="form-control">
            <?php foreach ($edizioni as $e) { ?>
                <option value="<?= $e->Year ?>" <?= ($e->Year === $anno) ? 'selected' : '' ?>>
                    <?= $e->Year ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <script>
        (() => {
            const select_p = document.getElementById('parrocchia-selector');
            const select_a = document.getElementById('anno-selector');
            const f = () => {
                const p = Number(select_p.value);
                const a = Number(select_a.value);
                select_p.removeEventListener('change', f);
                select_a.removeEventListener('change', f);
                window.location.replace(`?church=${p}&year=${a}`);
            };
            select_p.addEventListener('change', f);
            select_a.addEventListener('change', f);
        })();
    </script>
<?php } ?>

<?php if (count(value: $iscritti_problemi) > 0) { ?>
    <h2 class="h1 text-warning">
        Problemi iscrizioni (<?= count(value: $iscritti_problemi) ?>)
    </h2>
    <div class="row">
        <?php foreach ($iscritti_problemi as $problema) { 
            if (!($problema instanceof ProblemaIscrizione)) 
                continue;    
        ?>
            <div class="col col-xs-12 col-sm-6 col-lg-4 mb-2">
                <div class="card">
                    <div class="card-header">
                        <strong>
                            <?= htmlspecialchars(string: $problema->Nome) ?>
                        </strong>

                        <?php if ($problema->Sesso === 'M') { ?>
                            <i class="bi bi-gender-male text-end"></i>
                        <?php } elseif ($problema->Sesso === 'F') { ?>
                            <i class="bi bi-gender-female text-end"></i>
                        <?php } ?>

                        <a  href="<?= $P ?>/staff/edit_anagrafica?id=<?= $problema->Id ?>"
                            class="link-underline link-underline-opacity-0 link-primary text-end"
                            title="Modifica <?= htmlspecialchars(string: $problema->Nome) ?>"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <?php if (isset($problema->Documento)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Documento
                                </dt>
                                <dd class="col-sm-8 text-danger">
                                    <?= htmlspecialchars(string: $problema->Documento) ?>
                                    <a  class="link-primary link-underline link-underline-opacity-0"
                                        title="Aggiungi documento"
                                        href="<?= $P ?>/staff/edit_anagrafica?id=<?= $problema->Id ?>"
                                    >
                                        Inserisci
                                    </a>
                                </dd>
                            <?php } ?>
                            <?php if (isset($problema->ScadenzaDocumento)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Scadenza
                                </dt>
                                <dd class="col-sm-8 text-danger">
                                    <?= htmlspecialchars(string: $problema->ScadenzaDocumento) ?>
                                </dd>
                            <?php } ?>
                            <?php if (isset($problema->CodiceDocumento)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Documento
                                </dt>
                                <dd class="col-sm-8 text-warning">
                                    <?= htmlspecialchars(string: $problema->CodiceDocumento) ?>
                                </dd>
                            <?php } ?>


                            <?php if (isset($problema->Tutore)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Tutore
                                </dt>
                                <dd class="col-sm-8 text-danger">
                                    <?= htmlspecialchars(string: $problema->Tutore) ?>
                                    <a  class="link-primary link-underline link-underline-opacity-0"
                                        title="Aggiungi certificato"
                                        href="<?= $P ?>/staff/modifica_iscrizione?id=<?= $problema->Iscrizione ?>"
                                    >
                                        Inserisci
                                    </a>
                                </dd>
                            <?php } ?>
                            <?php if (isset($problema->Eta)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Età
                                </dt>
                                <dd class="col-sm-8 text-danger">
                                    <?= htmlspecialchars(string: $problema->Eta) ?>
                                </dd>
                            <?php } ?>
                            <?php if (isset($problema->Certificato)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Certificato
                                </dt>
                                <dd class="col-sm-8 text-danger">
                                    <?= htmlspecialchars(string: $problema->Certificato) ?>
                                    <a  class="link-primary link-underline link-underline-opacity-0"
                                        title="Aggiungi certificato"
                                        href="<?= $P ?>/staff/modifica_iscrizione?id=<?= $problema->Iscrizione ?>"
                                    >
                                        Inserisci
                                    </a>
                                </dd>
                            <?php } ?>
                            <?php if (isset($problema->Taglia)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Maglia
                                </dt>
                                <dd class="col-sm-8 text-warning">
                                    <?= htmlspecialchars(string: $problema->Taglia) ?>
                                </dd>
                            <?php } ?>

                            
                            <?php if (isset($problema->Email)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Email
                                </dt>
                                <dd class="col-sm-8 text-warning">
                                    <?= htmlspecialchars(string: $problema->Email) ?>
                                    <br>
                                    <a  class="link-primary link-underline link-underline-opacity-0"
                                        title="Aggiungi email"
                                        href="<?= $P ?>/staff/edit_anagrafica?id=<?= $problema->Id ?>"
                                    >
                                        Aggiungi
                                    </a>
                                </dd>
                            <?php } ?>
                            <?php if (isset($problema->Telefono)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Telefono
                                </dt>
                                <dd class="col-sm-8 text-warning">
                                    <?= htmlspecialchars(string: $problema->Telefono) ?>
                                </dd>
                            <?php } ?>
                        </dl>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>

<h1>
    <?= count(value: $iscritti) ?> iscritti di <?= htmlspecialchars(string: $nome_parrocchia) ?>
    per il <?= date(format: 'Y') ?>
</h1>

<div class="container m-1">
    <div class="form-floating overflow-hidden">
        <input  type="search" 
                id="anagrafiche-search" 
                class="form-control anagrafiche-search-input"                
                list="anagrafiche-data-list"
                title="Cerca persona"
        >
        <label for="search">Cerca nome, cognome, email o numero di telefono</label>
    </div>

    <datalist id="anagrafiche-data-list">
        <?php foreach ($iscritti as $anagrafica) { 
            $keywords = explode(separator: ' ', string: $anagrafica->KeyWords()); 
            foreach ($keywords as $keyword) { ?>
                <option>
                    <?= htmlspecialchars(string: $keyword)?>
                </option>
            <?php } ?>
        <?php } ?>
    </datalist>
</div>

<div class="row">
    <?php foreach ($iscritti as $anagrafica) { ?>
        <div 
            class="col col-xs-12 col-sm-6 col-lg-4 mb-2"
            data-keywords="<?= $anagrafica->KeyWords() ?>"
        >
            <?php require dirname(path: __DIR__) . "/Shared/Anagrafica.php"; ?>
        </div>
    <?php } ?>
</div>

<script>
    const anagrafiche = [...document.querySelectorAll('[data-keywords]')];
    var search_timeout_id = 0;
    function HighLightSearch(str) {
        if (search_timeout_id !== 0) {
            clearTimeout(search_timeout_id);
        }
        search_timeout_id = setTimeout(s => {
            HighLightEntity(s);
            // We reached the end
            search_timeout_id = 0;
        }, 100, str);
    }

    function HighLightEntity(str) {
        if (typeof str !== 'string' || str.length === 0) {
            anagrafiche.forEach(anagrafica => anagrafica.classList.remove('d-none'));
            return;
        }

        function filter_term(s) {
            return s.length > 0 && s !== '/' && s !== '\\' && s !== '|';
        }

        const search_terms = str.toLowerCase().split(' ').filter(filter_term);
        if (search_terms.length === 0) {
            anagrafiche.forEach(anagrafica => anagrafica.classList.remove('d-none'));
            return;
        }

        anagrafiche.forEach(anagrafica => {
            const keywords = anagrafica.getAttribute('data-keywords').toLowerCase().split(' ').filter(filter_term);
            if (keywords.length === 0) {
                anagrafica.classList.add('d-none');
                return;
            }

            const active_search_terms = search_terms.filter(term => keywords.some(keyword => keyword.includes(term)));

            if (active_search_terms.length < search_terms.length) {
                anagrafica.classList.add('d-none');
            } else {
                anagrafica.classList.remove('d-none');
            }
        });
    }

    $('input.anagrafiche-search-input').on('input change paste', function () {
        HighLightSearch($(this).val());
    });
</script>