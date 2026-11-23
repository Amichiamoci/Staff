<?php 
use Amichiamoci\Models\User;

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($P) && is_string($P), 'var $P not valid');

assert(isset($target) && ($target instanceof User), 'var $target not valid');
assert(isset($activity) && is_array($activity), 'var $activity not valid');
?>

<div class="card m-1">
    <div class="card-header font-monospace user-select-none">
        #<?= $target->Id ?>
    </div>
    <div class="card-body">
        <div class="card-title font-monospace user-select-none">
            <?= htmlspecialchars(string: $target->Name) ?>
        </div>
        <?php if ($target->Admin) { ?>
            <h6 class="card-subtitle mb-2 user-select-none text-body-secondary">
                Amministratore
            </h6>
        <?php } ?>
        <?php if ($target->IsBanned) { ?>
            <h6 class="card-subtitle mb-2 user-select-none text-body-secondary">
                Bloccato
            </h6>
        <?php } ?>

        <?php if ($user->Admin && !$target->HasAdditionalData()) { ?>
            <a  class="btn btn-secondary"
                href="<?= $P ?>/staff/link_account?account=<?= $target->Id ?>"
                title="Associa anagrafica staff"
            >
                Associa anagrafica staff
            </a>
        <?php } ?>

        <?php if ($user->Id === $target->Id) { ?>
            <button
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modal-change-password"
                title="Cambia password"
            >
                Cambia password
            </button>
            <div
                class="modal fade"
                id="modal-change-password"
                tabindex="-1"
                aria-hidden="true"
                aria-labelledby="modal-change-password-label"
            >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"
                                id="modal-change-password-label"
                            >
                                Cambia Password
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Chiudi"
                                title="Chiudi"
                            ></button>
                        </div>
                        <div class="modal-body">
                            <form
                                method="POST"
                                action="<?= $P ?>/user/update"
                            >
                                <div class="form-floating mb-2">
                                    <input
                                        required
                                        type="text"
                                        class="form-control"
                                        id="new_username"
                                        name="new_username"
                                        placeholder="Nome utente"
                                        value="<?= htmlspecialchars(string: $user->Name) ?>"
                                    >
                                    <label for="new_username">Nome utente</label>
                                    <div class="invalid-feedback">
                                        Per favore, immetti un nuovo nome utente
                                    </div>
                                </div>
                                <div class="form-floating mb-2">
                                    <input
                                        required
                                        type="password"
                                        class="form-control"
                                        id="current_password"
                                        name="current_password"
                                        placeholder="Password"
                                        autocomplete="off"
                                    >
                                    <label for="current_password">Password attuale</label>
                                    <div class="invalid-feedback">
                                        Per favore, immetti la tua password
                                    </div>
                                </div>
                                <div class="form-floating mb-2">
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="new_password"
                                        name="new_password"
                                        placeholder="Password"
                                        autocomplete="off"
                                    >
                                    <label for="new_password">Nuova password</label>
                                    <div class="invalid-feedback">
                                        Per favore, immetti una nuova password password
                                    </div>
                                    <div class="form-text user-select-none ms-2">
                                        Lasciare vuoto se non si intende cambiare la password
                                    </div>
                                </div>
                                
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    title="Invia"
                                >
                                    Invia
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <form
                action="<?= $P ?>/user/logout"
                method="post"
                class="d-inline-block"
            >
                <button
                    type="submit"
                    class="btn btn-primary"
                    title="Esci"
                >Esci</button>
            </form>
        <?php } ?>


        <?php if ($user->Admin) { ?>
            <div class="input-group m-1">
                <form
                    action="<?= $P ?>/user/ban"
                    method="post"
                    class="d-inline-block"
                >
                    <input
                        type="hidden"
                        name="target_id"
                        value="<?= $target->Id ?>"
                    >
                    <button
                        type="submit"
                        class="btn btn-outline-secondary"
                        title="Blocca"
                    >Blocca</button>
                </form>
                <form
                    action="<?= $P ?>/user/restore"
                    method="post"
                    class="d-inline-block"
                >
                    <input
                        type="hidden"
                        name="target_id"
                        value="<?= $target->Id ?>"
                    >
                    <button
                        type="submit"
                        class="btn btn-outline-secondary"
                        title="Riabilita"
                    >Riabilita</button>
                </form>
            </div>
            
            <?php if ($user->Id !== $target->Id) { ?>
                <form
                    action="<?= $P ?>/user/delete"
                    method="POST"
                    class="d-inline-block m-1"
                >
                    <input
                        type="hidden"
                        name="target_id"
                        value="<?= $target->Id ?>"
                    >
                    <button
                        type="submit"
                        class="btn btn-outline-danger"
                        title="Elimina l'utente"
                        data-confirm="Sei sicuro di voler eliminare <?= htmlspecialchars(string: $target->Name) ?>? Questa azione non potrà essere annullata"
                        data-confirm-btn="Sì, Cancella"
                        data-cancel-btn="No, ci ho ripensato"
                    >
                        Elimina utente
                    </button>
                </form>
                <form
                    action="<?= $P ?>/user/reset"
                    method="POST"
                    class="d-inline-block m-1"
                >
                    <input
                        type="hidden"
                        name="target_id"
                        value="<?= $target->Id ?>"
                    >
                    <button
                        type="submit"
                        class="btn btn-outline-info"
                        title="Reimposta la password"
                        data-confirm="Sicuro di voler resettare la password di <?= htmlspecialchars(string: $target->Name) ?>?"
                        data-confirm-btn="Sì"
                        data-cancel-btn="Annulla"
                    >
                        Resetta password
                    </button>
                </form>
            <?php } ?>
        <?php } ?>
    </div>
</div>
<div class="card m-1">
    <div class="card-header">
        Lista dei login
    </div>
    <ul class="list list-inline mt-3 card-body">
        <?php foreach ($activity as $login) { ?>
            <li class="d-flex justify-content-between m-2 border border-1 p-1"
                style="max-width: 800px;"
            >
                <?php require __DIR__ . '/LoginRender.php'; ?>
            </li>
        <?php } ?>
    </ul>
</div>