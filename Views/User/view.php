<div class="card">
    <div class="card-header font-monospace user-select-none">
        #<?= $target->Id ?>
    </div>
    <div class="card-body">
        <div class="card-title font-monospace user-select-none">
            <?= htmlspecialchars(string: $target->Name) ?>
        </div>
        <?php if ($target->IsAdmin) { ?>
            <h6 class="card-subtitle mb-2 user-select-none text-body-secondary">
                Amministratore
            </h6>
        <?php } ?>
        <?php if ($target->IsBanned) { ?>
            <h6 class="card-subtitle mb-2 user-select-none text-body-secondary">
                Bloccato
            </h6>
        <?php } ?>

        <button class="btn btn-primary">
            Cambia password
        </button>

        <?php if ($user->Id === $target->Id) { ?>
            <a href="/user/logout" class="btn btn-primary" role="button">
                Esci
            </a>
        <?php } ?>


        <?php if ($user->IsAdmin) { ?>
            <div class="input-group">
                <a href="/user/ban?target_id=<?= $target->Id ?>" class="btn btn-outline-secondary" role="button">
                    Blocca
                </a>
                <a href="/user/restore?target_id=<?= $target->Id ?>" class="btn btn-outline-secondary" role="button">
                    Riabilita
                </a>
                <button class="btn btn-outline-secondary">
                    Cancella
                </button>
            </div>
        <?php } ?>
    </div>
</div>