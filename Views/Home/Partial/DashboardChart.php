<?php

use Amichiamoci\Models\Edizione;

assert(isset($chart) && is_array($chart));
assert(isset($editions) && is_array($editions));
$filter = $chart['filter'];
?>

<article
    class="card shadow-sm"
    id="dashboard-<?= htmlspecialchars($filter) ?>"
    data-dashboard-chart="<?= htmlspecialchars($filter) ?>"
    data-chart-type="<?= htmlspecialchars($chart['type']) ?>"
>
    <h2 class="card-header h5 bg-body-tertiary mb-0"><?= htmlspecialchars($chart['title']) ?></h2>
    <?php if ($editions !== [] && $filter !== 'history') {
        $currentYear = (int)date('Y');
        $defaultEdition = array_find($editions, static fn(Edizione $edition): bool => $edition->Year === $currentYear);
        $defaultEdition ??= $editions[0];
        ?>
        <div class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3">
            <label
                class="fw-semibold"
                for="select-<?= htmlspecialchars($filter) ?>"
            >
                Edizione
            </label>
            <select
                class="form-select form-select-sm w-auto"
                id="select-<?= htmlspecialchars($filter) ?>"
                data-chart-year
            >
                <?php foreach ($editions as $edition) { ?>
                    <option
                        value="<?= $edition->Year ?>"
                        <?= $edition->Id === $defaultEdition->Id ? 'selected' : '' ?>
                    >
                        <?= $edition->Year ?>
                    </option>
                <?php } ?>
            </select>
            <?php if ($filter === 'tournaments') { ?>
                <label
                    class="fw-semibold"
                    for="tournament-chart-area"
                >
                    Area sportiva
                </label>
                <select
                    class="form-select form-select-sm w-auto"
                    id="tournament-chart-area"
                    data-chart-area
                    disabled
                >
                    <option value="">Seleziona area</option>
                </select>
            <?php } ?>
            <?php if (isset($chart['parish'])) { ?>
                <span class="small text-body-secondary"><?= htmlspecialchars($chart['parish']->Nome) ?></span>
            <?php } ?>
        </div>
    <?php } ?>
    <div class="card-body">
        <p  class="text-body-secondary mb-0"
            role="status"
        >
            <?= $editions === [] ? 'Nessuna edizione disponibile.' : '' ?>
        </p>
        <p  class="text-body-secondary mb-0"
            data-chart-loading
            hidden
        >
            Caricamento…
        </p>
        <div data-chart-results></div>
        <template>
            <section class="mb-3">
                <p  class="text-body-secondary"
                    hidden
                >
                    Nessun dato disponibile.
                </p>
                <button
                    class="btn btn-sm btn-outline-info mb-2"
                    type="button"
                    data-chart-details
                    aria-haspopup="dialog"
                    title="Mostra i dati di <?= htmlspecialchars($chart['title']) ?>"
                >
                    Dati e dettagli
                </button>
                <div
                    class="position-relative"
                    hidden
                >
                    <canvas aria-hidden="true"></canvas>
                </div>
                <dialog
                    class="dashboard-data-dialog bg-body text-body border rounded-3 shadow p-0"
                    aria-labelledby="chart-details-<?= htmlspecialchars($filter) ?>"
                >
                    <header class="d-flex align-items-center justify-content-between gap-3 border-bottom px-4 py-3">
                        <h1
                            class="h5 mb-0"
                            id="chart-details-<?= htmlspecialchars($filter) ?>"
                        >
                            <?= htmlspecialchars($chart['title']) ?>
                        </h1>
                        <form
                            method="dialog"
                            class="m-0"
                        >
                            <button
                                type="submit"
                                class="btn-close"
                                aria-label="Chiudi"
                                title="Chiudi i dati del grafico"
                                autofocus
                            ></button>
                        </form>
                    </header>
                    <div class="table-responsive px-4 pb-3">
                        <table class="table table-sm align-middle mb-0 mt-2">
                            <caption class="visually-hidden"><?= htmlspecialchars($chart['title']) ?></caption>
                            <thead>
                                <tr>
                                    <th scope="col"><?= htmlspecialchars($chart['category']) ?></th>
                                    <?php foreach ($chart['series'] as $index => $series) { ?>
                                        <th
                                            scope="col"
                                            class="text-end"
                                            data-color="<?= htmlspecialchars($chart['colors'][$index]) ?>"
                                        >
                                            <?= htmlspecialchars($series) ?>
                                        </th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </dialog>
            </section>
        </template>
    </div>
</article>
