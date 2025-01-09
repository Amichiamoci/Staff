<div class="row">
    <div class="col">
        <h2>
            Nome utente
        </h2>
        <p class="font-monospace user-select-none">
    	    <?= htmlspecialchars(string: $target->Name) ?>
        </p>
    </div>
    <div class="col">
        <h4>
            ID utente
        </h4>
        <p class="font-monospace">
    	    <?= $target->Id ?>
        </p>
    </div>
</div>
<?php if ($target->IsAdmin) { ?>
    <div class="row">
        <div class="col">
            Riconosciuto come amministratore di sistema
        </div>
    </div>
<?php } ?>
<div class="row">
    <div class="col">
        <button class="btn btn-primary">
            Cambia password
        </button>
    </div>
    <?php if ($user->Id === $target->Id) { ?>
        <div class="col">
            <a href="/user/logout" class="btn btn-primary" role="button">
                Esci
            </a>
        </div>
    <?php } ?>
    <?php if ($user->IsAdmin) { ?>
        <div class="col">
            <a href="/user/ban?target_id=<?= $target->Id ?>" class="btn btn-secondary" role="button">
                Blocca
            </a>
        </div>
        <div class="col">
            <a href="/user/restore?target_id=<?= $target->Id ?>" class="btn btn-secondary" role="button">
                Riabilita
            </a>
        </div>
        <div class="col">
            <button class="btn btn-secondary">
                Cancella
            </button>
        </div>
    <?php } ?>
</div>