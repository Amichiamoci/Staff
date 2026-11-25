<?php
use Amichiamoci\Models\{ProblemaIscrizione, User};

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($user) && ($user instanceof User), 'var $user not valid');

assert(isset($iscritti) && is_array($iscritti), 'var $iscritti not valid');
assert(isset($iscritti_problemi) && is_array($iscritti_problemi), 'var $iscritti_problemi not valid');

?>

<?php if ($user->Admin) { ?>
    <div class="input-group mb-2">
        <select
            id="parrocchia-selector"
            class="form-control"
        >
            <?php foreach ($parrocchie as $parrocchia) { ?>
                <option
                    value="<?= $parrocchia->Id ?>"
                    <?= ($parrocchia->Id === $id_parrocchia) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(string: $parrocchia->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <select
            id="anno-selector"
            class="form-control"
        >
            <?php foreach ($edizioni as $e) { ?>
                <option
                    value="<?= $e->Year ?>"
                    <?= ($e->Year === $anno) ? 'selected' : '' ?>
                >
                    <?= $e->Year ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <script>
        (() => {
            const $select_p = $('#parrocchia-selector');
            const $select_a = $('#anno-selector');
            const f = () => {
                const p = Number($select_p.val());
                const a = Number($select_a.val());
                $select_p.off('change', f);
                $select_a.off('change', f);
                window.location.replace(`?church=${p}&year=${a}`);
            };
            $select_p.on('change', f);
            $select_a.on('change', f);
        })();
    </script>
<?php } ?>

<?php if (count(value: $iscritti_problemi) > 0) { ?>
    <h2 class="h1 text-warning">
        Problemi iscrizioni (<?= count(value: $iscritti_problemi) ?>/<?= count(value: $iscritti) ?>)
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
                            <?php if (isset($problema->Tutore)) { ?>
                                <dt class="col-sm-4 text-nowrap">
                                    Tutore
                                </dt>
                                <dd class="col-sm-8 text-danger">
                                    <?= htmlspecialchars(string: $problema->Tutore) ?>
                                    <?php if ($user->Admin) { ?>
                                        <a  class="link-primary link-underline link-underline-opacity-0"
                                            title="Aggiungi certificato"
                                            href="<?= $P ?>/staff/modifica_iscrizione?id=<?= $problema->Iscrizione ?>"
                                        >
                                            Inserisci
                                        </a>
                                    <?php } ?>
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
                                    <?php if ($user->Admin) { ?>
                                        <a  class="link-primary link-underline link-underline-opacity-0"
                                            title="Aggiungi certificato"
                                            href="<?= $P ?>/staff/modifica_iscrizione?id=<?= $problema->Iscrizione ?>"
                                        >
                                            Inserisci
                                        </a>
                                    <?php } ?>
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
    <?= count(value: $iscritti) ?> iscritti per il <?= date(format: 'Y') ?>
</h1>

<div class="container m-1">
    <div class="form-floating overflow-hidden">
        <input
            type="search"
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
    (() => {
        const $anagrafiche = $('[data-keywords]');
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
                $anagrafiche.removeClass('d-none');
                return;
            }

            function filter_term(s) {
                return s.length > 0 && s !== '/' && s !== '\\' && s !== '|';
            }

            const search_terms = str.toLowerCase().split(' ').filter(filter_term);
            if (search_terms.length === 0) {
                $anagrafiche.removeClass('d-none');
                return;
            }

            $anagrafiche.each(function () {
                const $anagrafica = $(this);
                const keywords = $anagrafica.attr('data-keywords').toLowerCase().split(' ').filter(filter_term);
                if (keywords.length === 0) {
                    $anagrafica.addClass('d-none');
                    return;
                }

                const active_search_terms = search_terms.filter(term => keywords.some(keyword => keyword.includes(term)));

                if (active_search_terms.length < search_terms.length) {
                    $anagrafica.addClass('d-none');
                } else {
                    $anagrafica.removeClass('d-none');
                }
            });
        }

        $('input.anagrafiche-search-input').on('input change paste', function () {
            HighLightSearch($(this).val());
        });
    })();
</script>