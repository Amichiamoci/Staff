<div class="card">
    <div class="card-header user-select-none">
        <div class="input-group">
            <select id="stats-church-year" class="form-control mb-1" title="Edizione">
                <?php foreach ($editions as $edition) { ?>
                    <option value="<?= $edition->Year ?>">
                        <?= $edition->Year ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="card-title text-center">
            <strong>
                Suddivisione per parrocchie
            </strong>
        </div>
        <canvas id="stats-church-chart" 
            style="max-height: 200px;" 
            class="m-x-auto m-y-auto"
            oncontextmenu="return false"></canvas>
    </div>
</div>

<?php require_once __DIR__ . '/chart.js.php'; ?>
<script src="<?= $B ?>/Public/js/stats-church.js" defer></script>