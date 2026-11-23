<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($editions) && is_array($editions), 'var $editions not valid');

?>

<div class="card">
    <div class="card-header user-select-none">
        <div class="input-group">
            <label
                class="visually-hidden"
                for="stats-age-edition"
            >
                Edizione per la distribuzione per età
            </label>
            <select
                id="stats-age-edition"
                class="form-control mb-1"
                title="Edizione"
            >
                <?php foreach ($editions as $edition) { ?>
                    <option value="<?= $edition->Id ?>">
                        <?= $edition->Year ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="card-title text-center">
            <strong>
                Suddivisione per età
            </strong>
        </div>
        <canvas
            id="stats-age-chart"
            style="max-height: 200px;"
            class="d-none"
            role="img"
            aria-label="Numero di partecipanti per singolo anno di età"
        ></canvas>
        <p  class="text-body-secondary small mb-0"
            id="stats-age-status"
            role="status"
        ></p>
    </div>
</div>

<?php require_once __DIR__ . '/chart.js.php'; ?>
<script
    src="<?= $P ?>/js/stats-age.js"
    defer
></script>