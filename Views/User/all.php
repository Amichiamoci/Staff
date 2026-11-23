<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($users) && is_array($users), 'var $users not valid');

?>

<h1>Utenti (<?= count($users) ?>)</h1>
<p class="d-flex flex-wrap gap-3">
    <a  href="<?= $P ?>/user/new"
        title="Crea un nuovo account"
    >
        Nuovo utente
    </a>
    <a  href="<?= $P ?>/user/activity"
        title="Apri gli ultimi accessi di tutti gli account"
    >
        Ultimi accessi
    </a>
</p>

<?php if ($users === []) { ?>
    <p>Nessun account disponibile.</p>
<?php } else { ?>
    <div
        class="table-responsive"
        tabindex="0"
        role="region"
        aria-label="Tabella degli utenti"
    >
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Account</th>
                    <th scope="col">Profilo staff</th>
                    <th scope="col">Ruolo</th>
                    <th scope="col">Stato</th>
                    <th scope="col">Ultima attività</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user_info) { ?>
                    <tr id="user-<?= $user_info->Id ?>">
                        <th scope="row">
                            <a  href="<?= $P ?>/user/view?id=<?= $user_info->Id ?>"
                                title="Apri la scheda account e le azioni di gestione"
                            >
                                <?= htmlspecialchars($user_info->Name) ?>
                            </a>
                            <span class="text-body-secondary fw-normal">#<?= $user_info->Id ?></span>
                        </th>
                        <td>
                            <?php if (!empty($user_info->IdStaff) && !empty($user_info->RealName)) { ?>
                                <a  href="<?= $P ?>/staff/view?id=<?= $user_info->IdStaff ?>"
                                    title="Apri la scheda staff"
                                >
                                    <?= htmlspecialchars($user_info->RealName) ?>
                                </a>
                            <?php } else { ?>
                                Nessuna associazione
                            <?php } ?>
                        </td>
                        <td><?= $user_info->Admin ? 'Amministratore' : 'Utente' ?></td>
                        <td><?= $user_info->IsBanned ? 'Bloccato' : 'Abilitato' ?></td>
                        <td class="text-nowrap">
                            <?= empty($user_info->LoginTime) ? 'Mai collegato' : htmlspecialchars($user_info->TimeLoggedMessage()) ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
