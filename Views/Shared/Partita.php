<?php

use Amichiamoci\Models\{Partita, Punteggio, Staff, User};

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($partita) && ($partita instanceof Partita), 'var $partita not valid');
assert(isset($campi) && is_array($campi), 'var $campi not valid');

$can_edit = $user->Admin || ($staff ?? null) instanceof Staff;
if ($can_edit) {
    assert(isset($match_csrf_token) && is_string($match_csrf_token));
}
$sport = $partita->getSport();
$today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d');
$vincitore = $partita->SquadraVincitriceATavolino();
$missingPastResult = $partita->Data !== null && $partita->Data < $today && $partita->VincitoreATavolino === null
    && !array_any($partita->Punteggi, static fn(Punteggio $score): bool => $score->Casa !== '' && $score->Ospiti !== '');
?>

<article
    class="card<?= $missingPastResult ? ' border-warning' : '' ?>"
    id="match-<?= $partita->Id ?>"
    <?php if ($can_edit) { ?>
        data-match-csrf="<?= htmlspecialchars($match_csrf_token) ?>"
        data-match-date="<?= htmlspecialchars($partita->Data ?? '') ?>"
        data-match-today="<?= htmlspecialchars($today) ?>"
    <?php } ?>
>
    <header class="card-header d-flex align-items-start gap-2 user-select-none">
        <?php if ($user->Admin) { ?>
            <div class="small text-body-secondary font-monospace pt-1">#<?= $partita->Id ?></div>
        <?php } ?>

        <span aria-hidden="true"><?= htmlspecialchars($sport->getIcon()) ?></span>

        <div class="flex-grow-1 text-break">
            <strong>
                <?= htmlspecialchars($sport->Nome) ?>
            </strong>
            <?php if ($can_edit) { ?>
                <button
                    type="button"
                    class="btn btn-sm btn-link link-warning px-1 match-forfeit-trigger"
                    title="Gestisci il risultato a tavolino"
                    aria-label="Gestisci il risultato a tavolino"
                    aria-haspopup="dialog"
                    aria-controls="match-forfeit-<?= $partita->Id ?>"
                >
                    <i  class="bi bi-flag"
                        aria-hidden="true"
                    ></i>
                </button>
            <?php } ?>
        </div>
        <?php if ($can_edit) { ?>
            <div class="d-flex align-items-center flex-shrink-0 gap-1">
                <a  href="#match-date-<?= $partita->Id ?>"
                    class="btn btn-sm btn-link link-primary"
                    title="Vai ai campi di modifica della partita"
                    aria-label="Vai ai campi di modifica della partita"
                >
                    <i  class="bi bi-pencil-square"
                        aria-hidden="true"
                    ></i>
                </a>
                <?php if ($user->Admin) { ?>
                    <form
                        action="<?= $P ?>/sport/match_delete"
                        method="post"
                    >
                        <input
                            type="hidden"
                            name="match"
                            value="<?= $partita->Id ?>"
                        >
                        <button
                            type="submit"
                            class="btn btn-sm btn-link link-danger"
                            title="Elimina la partita"
                            aria-label="Elimina la partita"
                            data-confirm="Sei sicuro di voler eliminare la partita? Tutti i risultati andranno persi"
                            data-confirm-btn="Sì, elimina"
                            data-cancel-btn="Annulla"
                        >
                            <i  class="bi bi-trash"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </form>
                <?php } ?>
            </div>
        <?php } ?>
    </header>
    <?php if ($can_edit && $partita->VincitoreATavolino === null) { ?>
        <a  href="<?= $P ?>/sport/match_results?match_id=<?= $partita->Id ?>"
            class="btn btn-primary rounded-0 w-100"
            title="Apri il segnapunti"
            data-no-swup
            data-board-open
            aria-haspopup="dialog"
            <?= $partita->Data !== $today ? 'hidden' : '' ?>
        >
            <i  class="bi bi-clipboard-plus"
                aria-hidden="true"
            ></i>
            Segnapunti
        </a>
    <?php } ?>
    <div class="card-body">
        <div class="card-title text-center text-break">
            <a  href="<?= $P ?>/teams/details?id=<?= $partita->Casa->Id ?>"
                class="text-reset fw-semibold link-underline link-underline-opacity-0"
                title="Vedi la distinta di <?= htmlspecialchars($partita->Casa->Nome) ?>"
            >
                <i  class="bi bi-clipboard2-pulse text-primary"
                    aria-hidden="true"
                ></i>
                <?= htmlspecialchars($partita->Casa->Nome) ?>
            </a>
            <span class="text-secondary">–</span>
            <a  href="<?= $P ?>/teams/details?id=<?= $partita->Ospiti->Id ?>"
                class="text-reset fw-semibold link-underline link-underline-opacity-0"
                title="Vedi la distinta di <?= htmlspecialchars($partita->Ospiti->Nome) ?>"
            >
                <?= htmlspecialchars($partita->Ospiti->Nome) ?>
                <i  class="bi bi-clipboard2-pulse text-primary"
                    aria-hidden="true"
                ></i>
            </a>
        </div>

        <dl class="row">
            <dt
                class="col-sm-4"
                id="match-date-label-<?= $partita->Id ?>"
            >
                Data
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <input
                        type="date"
                        id="match-date-<?= $partita->Id ?>"
                        class="form-control match-date-selector"
                        aria-labelledby="match-date-label-<?= $partita->Id ?>"
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Data ? htmlspecialchars(string: $partita->Data) : '' ?>"
                        title="Imposta la data della partita"
                    >
                <?php } else { ?>
                    <?php if (isset($partita->Data)) { ?>   
                        <?= htmlspecialchars(string: $partita->Data) ?> 
                    <?php } else { ?>
                        <span class="text-warning">
                            Da definire
                        </span>
                    <?php } ?>
                <?php } ?>
            </dd>

            <dt
                class="col-sm-4"
                id="match-time-label-<?= $partita->Id ?>"
            >
                Ora
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <input
                        type="time"
                        class="form-control match-time-selector"
                        aria-labelledby="match-time-label-<?= $partita->Id ?>"
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Orario ? htmlspecialchars(string: $partita->Orario) : '' ?>"
                        step="1"
                        title="Imposta l'orario della partita"
                    >
                <?php } else { ?>
                    <?php if (isset($partita->Orario)) { ?>   
                        <?= htmlspecialchars(string: $partita->Orario) ?> 
                    <?php } else { ?>
                        <span class="text-warning">
                            Da definire
                        </span>
                    <?php } ?>
                <?php } ?>
            </dd>

            <dt
                class="col-sm-4"
                id="match-field-label-<?= $partita->Id ?>"
            >
                Campo
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <select
                        data-match="<?= $partita->Id ?>"
                        class="match-field-selector form-select"
                        aria-labelledby="match-field-label-<?= $partita->Id ?>"
                        title="Imposta il campo della partita"
                    >
                        <option value="">Nessun campo</option>
                        <?php foreach ($campi as $campo) { ?>
                            <option
                                value="<?= $campo->Id ?>"
                                <?= (isset($partita->Campo) && $partita->Campo->Id === $campo->Id) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars(string: $campo->Nome) ?>    
                            </option>
                        <?php } ?>
                    </select>    
                <?php } else { ?>
                    <?php if (isset($partita->Campo)) { ?>   
                        <?= htmlspecialchars(string: $partita->Campo->Nome) ?> 
                    <?php } else { ?>
                        <span class="text-warning">
                            Non ancora specificato
                        </span>
                    <?php } ?>
                <?php } ?>
            </dd>

            <dt class="col-sm-4">
                Risultato
            </dt>
            <dd
                class="col-sm-8"
                data-match-results
            >
                <?php if ($partita->VincitoreATavolino !== null) { ?>
                    <p class="fw-semibold mb-2">
                        A tavolino
                        <?php if ($vincitore !== null) { ?>
                            · Vince <?= htmlspecialchars($vincitore->Nome) ?>
                        <?php } ?>
                    </p>
                <?php } else { ?>
                    <ul class="list-group-flush m-0 p-0"
                        data-match="<?= $partita->Id ?>"
                    >
                        <?php foreach ($partita->Punteggi as $punteggio) { ?>
                            <li class="list-group-item"
                                data-result="<?= $punteggio->Id ?>"
                                <?= $punteggio->Casa !== '' && $punteggio->Ospiti !== '' ? 'data-saved-score' : '' ?>
                            >
                                <?php if ($can_edit) { ?>
                                    <div class="input-group mb-2">
                                        <input
                                            type="text"
                                            data-result="<?= $punteggio->Id ?>"
                                            class="form-control match-result-edit"
                                            pattern="[0-9]{1,8}\s{0,}-\s{0,}[0-9]{1,8}"
                                            aria-label="Punteggio casa e ospiti"
                                            placeholder="0 - 0"
                                            value="<?= htmlspecialchars(string: (string)$punteggio) ?>"
                                        >
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger match-result-remove"
                                            data-result="<?= $punteggio->Id ?>"
                                            title="Rimuovi punteggio"
                                            aria-label="Rimuovi punteggio"
                                        >
                                            <i  class="bi bi-trash3"
                                                aria-hidden="true"
                                            ></i>
                                        </button>
                                    </div>
                                <?php } else { ?>
                                    <?= htmlspecialchars((string)$punteggio) ?>
                                <?php } ?>
                            </li>
                        <?php } ?>
                        <?php if ($can_edit) { ?>
                            <li class="list-group-item">
                                <button
                                    type="button"
                                    class="btn btn-primary w-100 match-result-add"
                                    title="Aggiungi risultato"
                                    data-match="<?= $partita->Id ?>"
                                    <?= $sport->singleResult() ? 'data-single-result' : '' ?>
                                    <?= $sport->singleResult() && $partita->Punteggi !== [] ? 'disabled' : '' ?>
                                >
                                    <i  class="bi bi-plus"
                                        aria-hidden="true"
                                    ></i>
                                    Aggiungi risultato
                                </button>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
                <?php if ($can_edit && $partita->VincitoreATavolino === null && $partita->Data === $today) { ?>
                    <template data-board-template>
                        <?php require dirname(__DIR__) . '/Sport/match_results.php'; ?>
                    </template>
                <?php } ?>
            </dd>
        </dl>
    </div>
    <?php if ($can_edit) { ?>
        <?php require __DIR__ . '/PartitaTavolino.php'; ?>
    <?php } ?>
</article>
