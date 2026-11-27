<?php

use Amichiamoci\Models\Partita;

assert(isset($P) && is_string($P));
assert(isset($partita) && $partita instanceof Partita);
assert(isset($match_csrf_token) && is_string($match_csrf_token));

$winner = $partita->SquadraVincitriceATavolino();
$sport = $partita->getSport();
$currentDate = new DateTimeImmutable('now', new DateTimeZone('Europe/Rome'));
$volleyball = $sport->Area === 'Pallavolo';
$steps = $sport->Area === 'Basket' ? [2, 3, -1] : [-1];
$scores = [];
foreach ($partita->Punteggi as $score) {
    $scores[] = [$score->Casa, $score->Ospiti];
}
$canUseBoard = array_all($scores, static fn(array $pair): bool =>
    preg_match('/^[0-9]{0,8}$/D', $pair[0]) && preg_match('/^[0-9]{0,8}$/D', $pair[1])
);

?>

<?php if ($partita->VincitoreATavolino === null && $canUseBoard) { ?>
    <section
        id="scoreboard"
        class="match-scoreboard d-flex flex-column flex-grow-1 gap-2 gap-sm-3 p-2 p-sm-3"
        data-match="<?= $partita->Id ?>"
        data-csrf="<?= htmlspecialchars($match_csrf_token) ?>"
        data-scores="<?= htmlspecialchars(json_encode($scores, JSON_THROW_ON_ERROR)) ?>"
        data-score-ids="<?= htmlspecialchars(json_encode(array_column($partita->Punteggi, 'Id'), JSON_THROW_ON_ERROR)) ?>"
        aria-labelledby="scoreboard-heading"
    >
        <header class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex flex-grow-1 align-items-center gap-2 gap-sm-3">
                <img
                    src="<?= $P ?>/images/banner.png"
                    alt="Amichiamoci"
                    class="match-scoreboard-logo"
                >
                <div class="lh-sm">
                    <p class="fw-semibold text-break mb-1">
                        <?= htmlspecialchars($sport->Nome) ?>
                    </p>
                    <time
                        class="small text-body-secondary"
                        datetime="<?= htmlspecialchars($currentDate->format('Y-m-d')) ?>"
                    >
                        <?= htmlspecialchars($currentDate->format('d/m/Y')) ?>
                    </time>
                </div>
            </div>
            <h1
                id="scoreboard-heading"
                class="visually-hidden"
            >
                <?= htmlspecialchars($sport->getIcon()) ?> Segnapunti
            </h1>
            <div class="d-flex gap-2">
                <a  href="<?= $P ?>/sport/tournament?id=<?= $partita->Torneo ?>#match-<?= $partita->Id ?>"
                    class="btn btn-outline-light d-inline-flex align-items-center justify-content-center"
                    aria-label="Torna al calendario"
                    title="Torna al calendario"
                    data-board-back
                    data-no-swup
                >
                    <i  class="bi bi-arrow-left"
                        aria-hidden="true"
                    ></i>
                </a>
                <button
                    type="button"
                    class="btn btn-outline-light"
                    data-board-fullscreen
                    aria-pressed="false"
                    aria-label="Schermo intero"
                    title="Schermo intero"
                    hidden
                >
                    <i  class="bi bi-arrows-fullscreen"
                        aria-hidden="true"
                    ></i>
                </button>
            </div>
        </header>

        <?php if ($volleyball) { ?>
            <div class="d-flex flex-wrap gap-2">
                <div
                    class="d-flex flex-wrap gap-2"
                    data-board-sets
                    role="group"
                    aria-label="Set della partita"
                ></div>
                <button
                    type="button"
                    class="btn btn-outline-light"
                    data-board-new-set
                    title="Aggiungi il prossimo set"
                    disabled
                >
                    + Set
                </button>
            </div>
        <?php } ?>

        <div class="row g-2 g-sm-3 flex-grow-1">
            <?php foreach ([$partita->Casa, $partita->Ospiti] as $side => $team) { ?>
                <div class="col-6">
                    <section
                        class="d-flex flex-column h-100 gap-2"
                        aria-label="<?= htmlspecialchars($team->Nome) ?>"
                    >
                        <?php $incrementLabel = $sport->Area === 'Calcio' ? 'Aggiungi un gol' : 'Aggiungi un punto'; ?>
                        <button
                            type="button"
                            class="btn btn-outline-light border-3 <?= $side === 0 ? 'border-primary' : 'border-danger' ?> bg-body-tertiary text-body rounded-3 flex-grow-1 d-flex flex-column justify-content-center align-items-center gap-2 p-2"
                            title="<?= htmlspecialchars($incrementLabel . ' · ' . $team->Nome) ?>"
                            aria-describedby="board-team-<?= $side ?>"
                            data-board-side="<?= $side ?>"
                            data-board-step="1"
                            disabled
                        >
                            <span class="fs-4 fw-semibold text-break w-100">
                                <?= htmlspecialchars($team->Nome) ?>
                            </span>
                            <span
                                id="board-team-<?= $side ?>"
                                class="match-scoreboard-score w-100"
                                data-board-score="<?= $side ?>"
                                aria-live="polite"
                            >
                                <?= htmlspecialchars(($scores[$volleyball ? count($scores) - 1 : 0][$side] ?? '') ?: '0') ?>
                            </span>
                            <span class="visually-hidden">
                                <?= htmlspecialchars($incrementLabel) ?>
                            </span>
                        </button>
                        <div class="d-flex flex-wrap justify-content-center gap-1 gap-sm-2">
                            <?php foreach ($steps as $step) {
                                $label = match (true) {
                                    $step < 0 => $sport->Area === 'Calcio' ? 'Togli un gol' : 'Togli un punto',
                                    default => 'Aggiungi ' . $step . ' punti',
                                };
                                ?>
                                <button
                                    type="button"
                                    class="btn fw-semibold <?= $step < 0 ? 'btn-outline-light' : 'btn-light' ?>"
                                    title="<?= htmlspecialchars($label . ' · ' . $team->Nome) ?>"
                                    aria-label="<?= htmlspecialchars($label . ' · ' . $team->Nome) ?>"
                                    data-board-side="<?= $side ?>"
                                    data-board-step="<?= $step ?>"
                                    disabled
                                >
                                    <?= $step > 0 ? '+' : '−' ?><?= abs($step) ?>
                                </button>
                            <?php } ?>
                        </div>
                    </section>
                </div>
            <?php } ?>
        </div>

        <footer class="d-flex flex-wrap align-items-center gap-2">
            <p
                class="small mb-0"
                data-board-status
                role="status"
            >
                Salvataggio automatico
            </p>
            <button
                type="button"
                class="btn btn-outline-light"
                data-board-retry
                title="Riprova a salvare il punteggio"
                hidden
            >
                Riprova
            </button>
            <button
                type="button"
                class="btn btn-outline-light"
                data-board-reload
                title="Ricarica il punteggio dal server"
                hidden
            >
                Ricarica
            </button>
            <p class="w-100 text-center text-break mb-0">
                <strong>Campo</strong>
                <span><?= htmlspecialchars($partita->Campo?->Nome ?? 'Da definire') ?></span>
            </p>
        </footer>
    </section>
<?php } else { ?>
    <header class="p-3">
        <img
            src="<?= $P ?>/images/banner.png"
            alt="Amichiamoci"
            class="match-scoreboard-logo d-block mb-3"
        >
        <a  href="<?= $P ?>/sport/tournament?id=<?= $partita->Torneo ?>#match-<?= $partita->Id ?>"
            title="Torna alla partita nel calendario"
            data-board-back
            data-no-swup
        >
            Torna al calendario
        </a>
        <h1 class="h3 mt-2 mb-1 text-break">
            <?= htmlspecialchars($partita->Casa->Nome) ?> – <?= htmlspecialchars($partita->Ospiti->Nome) ?>
        </h1>
        <p class="mb-0 text-body-secondary">
            <span aria-hidden="true"><?= htmlspecialchars($sport->getIcon()) ?></span>
            <?= htmlspecialchars($sport->Nome) ?>
        </p>
    </header>

    <?php if ($partita->VincitoreATavolino === null) { ?>
        <p class="alert alert-info mx-3">Nel calendario puoi correggere i punteggi esistenti per aprire il segnapunti.</p>
    <?php } else { ?>
        <p class="alert alert-info mx-3">
            Partita assegnata a tavolino.
            <?php if ($winner !== null) { ?>
                Vince <strong><?= htmlspecialchars($winner->Nome) ?></strong>.
            <?php } ?>
        </p>
    <?php } ?>
<?php } ?>
