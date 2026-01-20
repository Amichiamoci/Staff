<h1>
    <?= htmlspecialchars(string: $title) ?> (<?= count(value: $anagrafiche) ?>)
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
        <?php foreach ($anagrafiche as $anagrafica) { 
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
    <?php foreach ($anagrafiche as $anagrafica) { ?>
        <div class="col col-xs-12 col-sm-6 col-lg-4 mb-2"
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