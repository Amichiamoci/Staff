<?php

assert(isset($P) && is_string($P), 'var $P not valid');

?>

<div class="card">
    <div class="card-body">
        <div class="card-title text-center">
            <strong>
                Provider Email
            </strong>
        </div>
        <canvas
            id="email-stats-chart"
            style="max-height: 200px;"
            class="m-x-auto m-y-auto"
            oncontextmenu="return false"
        ></canvas>
    </div>
</div>

<?php require_once __DIR__ . '/chart.js.php'; ?>
<script
    src="<?= $P ?>/js/stats-email.js"
    defer
></script>