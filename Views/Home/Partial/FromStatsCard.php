<div class="card">
    <div class="card-body">
        <div class="card-title text-center">
            <strong>
                Luoghi di nascita
            </strong>
        </div>
        <canvas id="stats-chart" 
            style="max-height: 200px;" 
            class="m-x-auto m-y-auto"
            oncontextmenu="return false"></canvas>
    </div>
</div>

<?php require_once __DIR__ . '/chart.js.php'; ?>
<script src="<?= $B ?>/Public/js/stats-from.js" defer></script>