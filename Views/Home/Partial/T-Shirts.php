<?php
use Amichiamoci\Models\Staff;

$default_parrocchia = 0;
if (isset($staff) && $staff instanceof Staff) {
    $default_parrocchia = $staff->Parrocchia->Id;
}
?>
<div class="card">
    <div class="card-header user-select-none">
        <select id="t-shirts-church" class="form-control mb-1" title="Parrocchia">
            <?php foreach ($churches as $church) { ?>
                <option value="<?= $church->Id ?>" <?= ($church->Id === $default_parrocchia) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $church->Nome) ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <ul class="list-group-flush card-body" id="t-shirts-list"></ul>
</div>

<script>
    (() => {
        async function load(parrocchia) {
            const r = await fetch('/t_shirts?church=' + parrocchia, { method: 'GET' });
            if (!r.ok) return;
            
            const ul =document.getElementById('t-shirts-list');
            ul.innerHTML = '<li class="list-group-item user-select-none">Nessuna maglia per la parrocchia selezionata</li>';

            const o = await r.json();
            if (o.length > 0) {
                ul.innerHTML = '';
            }
            for (const i of o) {
                const li =document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `<strong>${i.taglia}</strong>: <output class="font-monospace">${i.numero}</output>`;
                ul.appendChild(li);
            }
        }
        const select = document.getElementById('t-shirts-church');
        select.addEventListener('change', function() {
            load(select.value);
        });
        load(select.value);
    })();
</script>