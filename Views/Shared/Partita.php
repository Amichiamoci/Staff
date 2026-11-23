<?php

use Amichiamoci\Models\{Partita, User};

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($partita) && ($partita instanceof Partita), 'var $partita not valid');
assert(isset($campi) && is_array($campi), 'var $campi not valid');

$can_edit = $user->Admin;
$sport = $partita->getSport();
$sport_icon = $sport->getIcon();
?>

<article
    class="card"
    id="match-<?= $partita->Id ?>"
>
    <header class="card-header d-flex align-items-start gap-2">
        <?php if ($sport_icon !== '') { ?>
            <span aria-hidden="true"><?= htmlspecialchars($sport_icon) ?></span>
        <?php } ?>
        <div class="flex-grow-1 text-break">
            <strong><?= htmlspecialchars($sport->Nome) ?></strong>
            <?php if ($can_edit) { ?>
                <div class="small text-body-secondary font-monospace">#<?= $partita->Id ?></div>
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
            </div>
        <?php } ?>
    </header>
    <div class="card-body">
        <div class="card-title text-center text-break">
            <a  href="<?= $P ?>/teams/details?id=<?= $partita->Casa->Id ?>"
                class="text-reset fw-semibold link-underline link-underline-opacity-0"
                title="Vedi la distinta di <?= htmlspecialchars($partita->Casa->Nome) ?>"
            >
                <i  class="bi bi-clipboard2-pulse"
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
                <i  class="bi bi-clipboard2-pulse"
                    aria-hidden="true"
                ></i>
            </a>
        </div>

        <dl class="row">
            <dt class="col-sm-4">
                Data
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <input
                        type="date"
                        id="match-date-<?= $partita->Id ?>"
                        class="form-control match-date-selector"
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Data ? htmlspecialchars(string: $partita->Data) : '' ?>"
                        min="<?= date(format: 'Y') ?>-09-01"
                        max="<?= date(format: 'Y') ?>-10-31"
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

            <dt class="col-sm-4">
                Ora
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <input
                        type="time"
                        class="form-control match-time-selector"
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Orario ? htmlspecialchars(string: $partita->Orario) : '' ?>"
                        min="12:00"
                        max="23:00"
                        step="300"
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

            <dt class="col-sm-4">
                Campo
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <select
                        data-match="<?= $partita->Id ?>"
                        class="match-field-selector form-control"
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
            <dd class="col-sm-8">
                <ul class="list-group-flush m-0 p-0"
                    data-match="<?= $partita->Id ?>"
                >
                    <?php foreach ($partita->Punteggi as $punteggio) { ?>
                        <li class="list-group-item"
                            data-result="<?= $punteggio->Id ?>"
                        >
                            <?php if ($can_edit) { ?>
                                <div class="input-group mb-2">
                                    <input
                                        type="text"
                                        data-result="<?= $punteggio->Id ?>"
                                        class="form-control match-result-edit"
                                        pattern="[0-9]{1,2}\s{0,}-\s{0,}[0-9]{1,2}"
                                        placeholder="0 - 0"
                                        value="<?= htmlspecialchars(string: (string)$punteggio) ?>"
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger match-result-remove"
                                        data-result="<?= $punteggio->Id ?>"
                                        title="Rimuovi punteggio"
                                    >
                                        <i class="bi bi-trash3"></i>
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
                            >
                                <i class="bi bi-plus"></i>
                            </button>
                        </li>
                    <?php } ?>
                </ul>
            </dd>
        </dl>
    </div>
</article>
