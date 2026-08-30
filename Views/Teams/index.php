<?php

use Amichiamoci\Models\Squadra;
use Amichiamoci\Models\User;
use Amichiamoci\Models\StaffBase;

if (!isset($user) || !($user instanceof User)) {
    throw new \Exception(message: 'var $user not valid');
}

if (isset($staff) && !($staff instanceof StaffBase)) {
    throw new \Exception(message: 'var $staff not valid');
}

if (!isset($teams) || !is_array($teams)) {
    throw new \Exception(message: 'var $teams not valid');
}

if (!isset($edizioni) || !is_array($edizioni)) {
    throw new \Exception(message: 'var $edizioni not valid');
}

if (!isset($parrocchie) || !is_array($parrocchie)) {
    throw new \Exception(message: 'var $parrocchie not valid');
}

if (!isset($id_parrocchia) || !is_int($id_parrocchia)) {
    throw new \Exception(message: 'var $id_parrocchia not valid');
}

if (!isset($anno) || !is_int($anno)) {
    throw new \Exception(message: 'var $anno not valid');
}

$squadre_per_sport = array_reduce(
    array: $teams,
    callback: function (array $carry, Squadra $s): array {
      $carry[$s->Sport->Id][] = $s;
      return $carry;
    },
    initial: [],
);
?>
<?php if ($user->Admin || $staff !== null) { ?>
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

<h1>
    Lista squadre (<?=count(value: $teams)?>)
</h1>
<p>
    Le squadre sono raggruppate per lo sport per il quale sono registrate
</p>
<?php foreach (array_keys(array: $squadre_per_sport) as $sport_id) { ?>
    <details class="mt-1" open>
        <summary>
            <h4 class="d-inline">
                <?= htmlspecialchars(string: $squadre_per_sport[$sport_id][0]->Sport->Nome) ?>
                (<?= count(value: $squadre_per_sport[$sport_id]) ?>)
            </h4>
        </summary>
        <div class="row">
            <?php foreach ($squadre_per_sport[$sport_id] as $squadra) { ?>
                <div class="col col-xs-6 col-sm-4">
                    <?php require dirname(path: __DIR__) . '/Shared/Squadra.php'; ?>
                </div>
            <?php } ?>
        </div>
    </details>
<?php } ?>