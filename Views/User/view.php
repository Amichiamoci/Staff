<?php

use Amichiamoci\Models\{Staff, StaffBase, User};

assert(isset($user) && $user instanceof User, 'var $user not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($target) && $target instanceof User, 'var $target not valid');
assert(isset($activity) && is_array($activity), 'var $activity not valid');
assert(!isset($linked_staff) || $linked_staff instanceof StaffBase, 'var $linked_staff not valid');

$is_owner = $user->Id === $target->Id;

?>

<h1><?= $is_owner ? 'Il mio account' : htmlspecialchars($target->Name) ?></h1>
<?php if ($user->Admin) { ?>
    <p class="d-flex flex-wrap gap-3">
        <a  href="<?= $P ?>/user/all"
            title="Apri la lista degli account"
        >
            Tutti gli utenti
        </a>
        <a  href="<?= $P ?>/user/activity"
            title="Apri gli ultimi accessi di tutti gli account"
        >
            Ultimi accessi
        </a>
    </p>
<?php } ?>

<div class="row g-3 mb-4">
    <section class="col-lg-6">
        <div class="border rounded h-100 p-3">
            <h2 class="h4">Dati account</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Nome utente</dt>
                <dd class="col-sm-7 text-break"><?= htmlspecialchars($target->Name) ?></dd>
                <dt class="col-sm-5">Ruolo</dt>
                <dd class="col-sm-7"><?= $target->Admin ? 'Amministratore' : 'Utente' ?></dd>
                <dt class="col-sm-5">Stato account</dt>
                <dd class="col-sm-7"><?= $target->IsBanned ? 'Bloccato' : 'Abilitato' ?></dd>
                <dt class="col-sm-5">ID account</dt>
                <dd class="col-sm-7"><?= $target->Id ?></dd>
            </dl>
            <?php if ($is_owner) { ?>
                <?php assert(isset($csrf_token) && is_string($csrf_token), 'var $csrf_token not valid'); ?>
                <details class="mt-2">
                    <summary>Modifica nome utente o password</summary>
                    <form
                        method="post"
                        action="<?= $P ?>/user/update"
                        class="mt-3"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrf_token) ?>"
                        >
                        <div class="form-floating mb-3">
                            <input
                                type="text"
                                class="form-control"
                                id="new_username"
                                placeholder="Nome utente"
                                name="new_username"
                                autocomplete="username"
                                value="<?= htmlspecialchars($target->Name) ?>"
                                required
                            >
                            <label for="new_username">Nome utente</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input
                                type="password"
                                class="form-control"
                                id="current_password"
                                placeholder="Password attuale"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                            <label for="current_password">Password attuale</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input
                                type="password"
                                class="form-control"
                                id="new_password"
                                placeholder="Nuova password (facoltativa)"
                                name="new_password"
                                autocomplete="new-password"
                                minlength="6"
                            >
                            <label for="new_password">Nuova password (facoltativa)</label>
                        </div>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            title="Salva le modifiche al tuo account"
                        >
                            Salva modifiche
                        </button>
                    </form>
                </details>
                <form
                    action="<?= $P ?>/user/logout"
                    method="post"
                    class="mt-3"
                >
                    <button
                        type="submit"
                        class="btn btn-outline-secondary"
                        title="Esci dal tuo account"
                    >
                        Esci
                    </button>
                </form>
            <?php } ?>
        </div>
    </section>

    <section class="col-lg-6">
        <div class="border rounded h-100 p-3">
            <h2 class="h4">Profilo staff</h2>
            <?php if (isset($linked_staff)) { ?>
                <dl class="row mb-2">
                    <dt class="col-sm-5">Nome e cognome</dt>
                    <dd class="col-sm-7">
                        <a  href="<?= $P ?>/staff/view?id=<?= $linked_staff->Id ?>"
                            title="Apri la scheda staff"
                        >
                            <?= htmlspecialchars($linked_staff->Nome) ?>
                        </a>
                    </dd>
                    <?php if ($linked_staff instanceof Staff) { ?>
                        <?php $edition_year = date('Y'); ?>
                        <dt class="col-sm-5">Parrocchia</dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars($linked_staff->Parrocchia->Nome) ?>
                        </dd>
                        <dt class="col-sm-5">Commissioni (<?= $edition_year ?>)</dt>
                        <dd class="col-sm-7">
                            <?= $linked_staff->Commissioni ? htmlspecialchars(implode(', ', $linked_staff->Commissioni)) : 'Nessuna' ?>
                        </dd>
                        <?php if (isset($linked_staff->Taglia)) { ?>
                            <dt class="col-sm-5">Taglia maglia (<?= $edition_year ?>)</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($linked_staff->Taglia->value) ?></dd>
                        <?php } ?>
                    <?php } ?>
                </dl>
                <?php if ($is_owner && $linked_staff instanceof Staff) { ?>
                    <a  href="<?= $P ?>/staff/me"
                        title="Apri le impostazioni del tuo profilo staff"
                    >
                        Modifica parrocchia
                    </a>
                <?php } ?>
            <?php } elseif ($user->Admin && !$target->HasAdditionalData()) { ?>
                <p>Nessun profilo staff associato.</p>
                <a  href="<?= $P ?>/staff/link_account?account=<?= $target->Id ?>"
                    title="Associa un’anagrafica staff a questo account"
                >
                    Associa anagrafica staff
                </a>
            <?php } else { ?>
                <p class="mb-0">
                    Nessun profilo staff disponibile.
                    <?php if ($is_owner && !$user->Admin) { ?>
                        Chiedi a un amministratore di associare la tua anagrafica.
                    <?php } ?>
                </p>
            <?php } ?>
        </div>
    </section>
</div>

<?php if ($user->Admin && !$is_owner) { ?>
    <?php require __DIR__ . '/Manage.php'; ?>
<?php } ?>

<section>
    <h2 class="h4">Storico accessi</h2>
    <?php
    $show_accounts = false;
    require __DIR__ . '/LoginRender.php';
    ?>
</section>
