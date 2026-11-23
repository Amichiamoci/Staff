<?php

assert(isset($P) && is_string($P));
assert(isset($user) && $user instanceof \Amichiamoci\Models\User);
assert(isset($editions) && is_array($editions));

$parish = $user->Admin ? null : ($staff ?? null)?->Parrocchia;
$charts = [];
if ($user->Admin || $parish !== null) {
    $charts[] = [
        'title' => 'Iscrizioni da verificare',
        'filter' => 'readiness',
        'parish' => $parish,
        'category' => 'Parrocchia',
        'series' => ['Senza problemi', 'Con problemi'],
        'colors' => ['#198754', '#c2410c'],
        'type' => 'horizontal',
    ];
}
$charts[] = [
    'title' => 'Preparazione dei tornei',
    'filter' => 'tournaments',
    'category' => 'Torneo',
    'series' => ['Da programmare', 'In attesa di risultato', 'Con risultato'],
    'colors' => ['#c2410c', '#0d6efd', '#198754'],
    'type' => 'horizontal',
];
?>

<div class="row g-3 mb-4">
    <?php foreach ($charts as $chart) { ?>
        <div class="col-12 col-xl-6">
            <?php require __DIR__ . '/DashboardChart.php'; ?>
        </div>
    <?php } ?>
</div>

<?php require_once __DIR__ . '/chart.js.php'; ?>
<script
    src="<?= $P ?>/js/dashboard-charts.js"
    defer
></script>
