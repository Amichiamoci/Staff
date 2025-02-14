<?php 
if (!isset($target) || !($target instanceof Amichiamoci\Models\User)) {
    throw new Exception(message: '$target variable not set!');
}
?>

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

        <?php if ($user->Id === $target->Id) { ?>
            <button 
                class="btn btn-primary"
                data-bs-toggle="modal" 
                data-bs-target="#modal-change-password"
            >
                Cambia password
            </button>
            <div class="modal fade" id="modal-change-password" 
                tabindex="-1" 
                aria-hidden="true"
                aria-labelledby="modal-change-password-label">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modal-change-password-label">
                                Cambia Password
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="/user/update">
                                <div class="form-floating mb-2">
                                    <input 
                                        required
                                        type="text" class="form-control" 
                                        id="new_username" name="new_username" 
                                        placeholder="Nome utente"
                                        value="<?= htmlspecialchars(string: $user->Name) ?>">
                                    <label for="new_username">Nome utente</label>
                                    <div class="invalid-feedback">
                                        Per favore, immetti un nuovo nome utente
                                    </div>
                                </div>
                                <div class="form-floating mb-2">
                                    <input 
                                        required
                                        type="password" class="form-control" 
                                        id="current_password" name="current_password" 
                                        placeholder="Password"
                                        autocomplete="off">
                                    <label for="current_password">Password attuale</label>
                                    <div class="invalid-feedback">
                                        Per favore, immetti la tua password
                                    </div>
                                </div>
                                <div class="form-floating mb-2">
                                    <input 
                                        type="password" class="form-control" 
                                        id="new_password" name="new_password" 
                                        placeholder="Password"
                                        autocomplete="off">
                                    <label for="new_password">Nuova password</label>
                                    <div class="invalid-feedback">
                                        Per favore, immetti una nuova password password
                                    </div>
                                    <div class="form-text user-select-none ms-2">
                                        Lasciare vuoto se non si intende cambiare la password
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    Invia
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <a href="/user/logout" class="btn btn-primary" role="button">
                Esci
            </a>
        <?php } ?>


        <?php if ($user->IsAdmin) { ?>
            <div class="input-group m-1">
                <a href="/user/ban?target_id=<?= $target->Id ?>" class="btn btn-outline-secondary" role="button">
                    Blocca
                </a>
                <a href="/user/restore?target_id=<?= $target->Id ?>" class="btn btn-outline-secondary" role="button">
                    Riabilita
                </a>

                <?php if ($user->Id !== $target->Id) { ?>
                    <form action="/user/reset" method="POST">
                        <input type="hidden" name="target_id" value="<?= $target->Id ?>">
                        <button 
                            type="submit"
                            class="btn btn-outline-secondary"
                            title="Reimposta la password"
                            data-confirm="Sicuro di voler resettare la password di <?= htmlspecialchars(string: $target->Name) ?>?"
                            data-confirm-btn="Sì"
                            data-cancel-btn="Annulla"
                        >
                            Resetta password
                        </button>
                    </form>
                <?php } ?>

                <button class="btn btn-outline-secondary">
                    Cancella
                </button>
            </div>
        <?php } ?>
    </div>
</div>