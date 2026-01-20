<?php
use Amichiamoci\Models\Staff;

$default_parrocchia = 0;
if (isset($staff) && $staff instanceof Staff) {
    $default_parrocchia = $staff->Parrocchia->Id;
}

if (!isset($card_name))
    $card_name = 'card';
?>

<div class="card">
    <?php if (!isset($card_church_year_ignore)) { ?>
        <div class="card-header user-select-none">
            <div class="input-group">
                <select id="<?= $card_name ?>-church" class="form-control mb-1" title="Parrocchia">
                    <?php foreach ($churches as $church) { ?>
                        <option value="<?= $church->Id ?>" <?= ($church->Id === $default_parrocchia) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(string: $church->Nome) ?>
                        </option>
                    <?php } ?>
                </select>
                <?php if ($user->Admin) { ?>
                    <select id="<?= $card_name ?>-year" class="form-control mb-1" title="Edizione">
                        <?php foreach ($editions as $edition) { ?>
                            <option value="<?= $edition->Year ?>">
                                <?= $edition->Year ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } else { ?>
                    <input type="hidden" id="<?= $card_name ?>-year" value="<?= date(format: 'Y') ?>">
                <?php }?>
            </div>
        </div>
    <?php } ?>
    <div class="card-body">
        <div class="card-title">
            <?php if (isset($card_link)) { ?>
                <a 
                    href="<?= $P . $card_link ?>"
                    class="link-underline link-underline-opacity-0 text-reset"
                >
                    <strong>
                        <?= htmlspecialchars(string: $card_title) ?>
                    </strong>
                </a>
            <?php } else { ?>   
                <strong>
                    <?= htmlspecialchars(string: $card_title) ?>
                </strong>
            <?php } ?>
        </div>
        <ul class="list-group-flush p-0 overflow-y-auto" style="max-height: 250px;" id="<?= $card_name ?>-list"></ul>
    </div>
</div>

<script>
    (() => {
        async function load(parrocchia, anno) {
            <?php if (!isset($card_church_year_ignore)) { ?>
                const r = await fetch(`<?= $P . $card_json_base ?>?church=${parrocchia}&year=${anno}`, { method: 'GET' });
            <?php } else { ?>
                const r = await fetch(`<?= $P . $card_json_base ?>`, { method: 'GET' });
            <?php } ?>
            
            if (!r.ok) return;
            
            const ul = document.getElementById('<?= $card_name ?>-list');
            ul.innerHTML = '<li class="list-group-item user-select-none text-danger">Impossibile reperire i dati</li>';

            const o = await r.json();
            if (o.length > 0) {
                ul.innerHTML = '';
            }
            for (const i of o) {
                ul.appendChild(<?= $card_js_mapper ?>(i));
            }
            if (o.length === 0) {
                ul.innerHTML = '<?= $card_nothing_found ?>';
            }
        }
        <?php if (!isset($card_church_year_ignore)) { ?>
            const select_p = document.getElementById('<?= $card_name ?>-church');
            const select_a = document.getElementById('<?= $card_name ?>-year');
            select_p.addEventListener('change', function() {
                load(select_p.value, select_a.value);
            });
            select_a.addEventListener('change', function() {
                load(select_p.value, select_a.value);
            });
            load(select_p.value, select_a.value);
        <?php } else { ?>
            load(null, null);
        <?php } ?>
    })();
</script>

<?php
unset($card_name);
if (isset($card_title))
    unset($card_title);
if (isset($card_link))
    unset($card_link);
if (isset($card_json_base))
    unset($card_json_base);
if (isset($card_js_mapper))
    unset($card_js_mapper);
if (isset($card_nothing_found))
    unset($card_nothing_found);
if (isset($card_church_year_ignore))
    unset($card_church_year_ignore);
?>