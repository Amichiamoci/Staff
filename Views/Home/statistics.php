<?php

use Amichiamoci\Models\User;

assert(isset($P) && is_string($P));
assert(isset($editions) && is_array($editions));
assert(isset($user) && $user instanceof User);

$charts = [
    [
        'title' => 'Partecipazione per edizione',
        'filter' => 'history',
        'category' => 'Edizione',
        'series' => ['Totale partecipanti', 'Alla prima edizione', 'Già partecipanti'],
        'colors' => ['#0d6efd', '#198754', '#9333ea'],
        'type' => 'line',
    ],
    [
        'title' => 'Fabbisogno di magliette',
        'filter' => 'shirts',
        'parish' => $user->Admin ? null : ($staff ?? null)?->Parrocchia,
        'category' => 'Taglia',
        'series' => ['Partecipanti', 'Staff'],
        'colors' => ['#0d6efd', '#9333ea'],
        'type' => 'bar',
    ],
];
?>

<header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <h1 class="mb-0">Statistiche</h1>
    <a  href="<?= $P ?>/"
        class="btn btn-outline-primary"
        title="Torna alla panoramica del portale"
    >
        Torna alla panoramica
    </a>
</header>

<div class="row g-3 dashboard-cards">
    <div class="col-12 col-md-6">
        <?php require __DIR__ . '/Partial/FromStatsCard.php'; ?>
    </div>
    <div class="col-12 col-md-6">
        <?php require __DIR__ . '/Partial/EmailProvidersCard.php'; ?>
    </div>
    <?php foreach ($charts as $chart) { ?>
        <div class="col-12">
            <?php require __DIR__ . '/Partial/DashboardChart.php'; ?>
        </div>
    <?php } ?>
</div>
<script
    src="<?= $P ?>/js/dashboard-charts.js"
    defer
></script>
