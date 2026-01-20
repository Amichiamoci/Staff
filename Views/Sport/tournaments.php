<?php

use Amichiamoci\Models\Torneo;

$tornei_per_sport = array_reduce(
    array: $tornei,
    callback: function (array $carry, Torneo $t): array {
      $carry[$t->Sport->Id][] = $t;
      return $carry;
    },
    initial: [],
);
?>

<?php if ($user->Admin) { ?>
    <div class="input-group mb-2">
        <select id="anno-selector" class="form-control">
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
            const select_a = document.getElementById('anno-selector');
            const f = () => {
                const a = Number(select_a.value);
                select_a.removeEventListener('change', f);
                window.location.replace(`?year=${a}`);
            };
            select_a.addEventListener('change', f);
        })();
    </script>
<?php } ?>

<h1>
    Tornei attivi (<?= count(value: $tornei) ?>)
</h1>

<?php foreach (array_keys($tornei_per_sport) as $sport_id) { ?>
    <h3 class="mt-1">
        <?= htmlspecialchars(string: $tornei_per_sport[$sport_id][0]->Sport->Nome) ?>
        (<?= count(value: $tornei_per_sport[$sport_id]) ?>)
    </h3>
    <div class="row m-0">
        <?php foreach ($tornei_per_sport[$sport_id] as $torneo) { ?>
            <div class="col col-xs-6 col-sm-4">
                <?php require dirname(path: __DIR__) . '/Shared/Torneo.php'; ?>
            </div>
        <?php } ?>
    </div>
<?php } ?>