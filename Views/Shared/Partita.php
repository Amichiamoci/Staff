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
                class="text-reset"
                target="_blank"
            ><?= htmlspecialchars(string: $partita->Casa->Nome) ?></a>
            vs
            <a href="<?= $B ?>/church?id=<?= $partita->Ospiti->Parrocchia->Id ?>"
                title="Vai alla parrocchia"
                class="text-reset"
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
                        class="form-control"
                        id="date-match-<?= $partita->Id ?>" 
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Data ? htmlspecialchars(string: $partita->Data) : '' ?>">  
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
                        class="form-control"
                        id="time-match-<?= $partita->Id ?>" 
                        data-match="<?= $partita->Id ?>"
                        value="<?= $partita->Orario ? htmlspecialchars(string: $partita->Orario) : '' ?>">  
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
                        id="field-match-<?= $partita->Id ?>" 
                        data-match="<?= $partita->Id ?>"
                        class="match-selector form-control"
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
        </dl>
    </div>
</div>