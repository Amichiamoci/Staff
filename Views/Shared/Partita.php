<?php
    use Amichiamoci\Models\Partita;
    if (!($partita instanceof Partita)) {
        throw new \Exception(message: 'Invalid variable $partita');
    }
    $can_edit = $user->IsAdmin || (isset($staff) && $staff->InCommissione(commissione: 'Tornei'));
?>

<div class="card" id="match-<?= $partita->Id ?>">
    <?php if ($user->IsAdmin) { ?>
        <div class="card-header font-monospace">
            #<?= $partita->Id ?>
        </div>
    <?php } ?>
    <div class="card-body">
        <div class="card-title text-center">
            <a href="<?= $B ?>/church?id=<?= $partita->Casa->Parrocchia->Id ?>"
                title="Vai alla parrocchia"
                class="text-reset fw-bold"
                target="_blank"
            ><?= htmlspecialchars(string: $partita->Casa->Nome) ?></a>
            vs
            <a href="<?= $B ?>/church?id=<?= $partita->Ospiti->Parrocchia->Id ?>"
                title="Vai alla parrocchia"
                class="text-reset fw-bold"
                target="_blank"
            ><?= htmlspecialchars(string: $partita->Ospiti->Nome) ?></a>
        </div>

        <dl class="row">
            <dt class="col-sm-4 text-nowrap text-secondary user-select-none">
                Sport
            </dt>
            <dd class="col-sm-8 text-secondary user-select-none">
                <?= htmlspecialchars(string: $partita->Casa->Sport->Nome) ?>
            </dd>

            <dt class="col-sm-4">
                Data
            </dt>
            <dd class="col-sm-8">
                <?php if ($can_edit) { ?>
                    <input 
                        type="date" 
                        class="form-control match-date-selector"
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Data ? htmlspecialchars(string: $partita->Data) : '' ?>"
                        min="<?=date(format: 'Y') ?>-09-01"
                        max="<?=date(format: 'Y') ?>-10-31"
                        title="Imposta la data della partita">  
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
                        title="Imposta l'orario della partita">  
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
                <ul class="list-group-flush m-0 p-0" data-match="<?= $partita->Id ?>">
                    <?php foreach ($partita->Punteggi as $punteggio) { ?>
                        <li class="list-group-item" data-result="<?= $punteggio->Id ?>">
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
                        </li>
                    <?php } ?>
                    <li class="list-group-item">
                        <button 
                            type="button"
                            class="btn btn-outline-primary w-100 match-result-add"
                            title="Aggiungi risultato"
                            data-match="<?= $partita->Id ?>"
                        >
                            <i class="bi bi-plus"></i>
                        </button>
                    </li>
                </ul>
            </dd>
        </dl>
    </div>
</div>