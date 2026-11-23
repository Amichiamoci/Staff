<?php

use Amichiamoci\Models\{Squadra, User, StaffBase};

assert(isset($user) && $user instanceof User, 'var $user not valid');
assert(!isset($staff) || $staff instanceof StaffBase, 'var $staff not valid');
assert(isset($teams) && is_array($teams), 'var $teams not valid');
assert(isset($edizioni) && is_array($edizioni), 'var $edizioni not valid');
assert(isset($parrocchie) && is_array($parrocchie), 'var $parrocchie not valid');
assert(isset($id_parrocchia) && is_int($id_parrocchia), 'var $id_parrocchia not valid');
assert(isset($anno) && is_int($anno), 'var $anno not valid');

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
