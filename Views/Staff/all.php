<?php

use Amichiamoci\Models\{Staff, User};

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($user) && $user instanceof User, 'var $user not valid');
assert(isset($title) && is_string($title), 'var $title not valid');
assert(isset($staffs) && is_array($staffs), 'var $staffs not valid');
assert(isset($current_only) && is_bool($current_only), 'var $current_only not valid');

if (!$current_only) {
    assert(isset($current_staff) && is_array($current_staff), 'var $current_staff not valid');
}

?>

<h1><?= htmlspecialchars($title) ?> (<?= count($staffs) ?>)</h1>
<nav aria-label="Elenco staff">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a  href="<?= $P ?>/staff/all"
                class="nav-link<?= $current_only ? '' : ' active' ?>"
                <?= $current_only ? '' : 'aria-current="page"' ?>
                title="Mostra tutti i profili staff"
            >
                Tutti gli staffisti
            </a>
        </li>
        <li class="nav-item">
            <a  href="<?= $P ?>/staff/current"
                class="nav-link<?= $current_only ? ' active' : '' ?>"
                <?= $current_only ? 'aria-current="page"' : '' ?>
                title="Mostra gli staffisti dell’edizione corrente"
            >
                Edizione corrente
            </a>
        </li>
    </ul>
</nav>

<?php if ($staffs === []) { ?>
    <p>Nessuno staffista da mostrare.</p>
<?php } else { ?>
    <div
        class="table-responsive"
        tabindex="0"
        role="region"
        aria-label="Tabella degli staffisti"
    >
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Staffista</th>
                    <?php if (!$current_only) { ?>
                        <th scope="col">Edizione corrente</th>
                    <?php } ?>
                    <th scope="col">Parrocchia</th>
                    <th scope="col">Commissioni</th>
                    <th scope="col">Taglia maglia</th>
                    <?php if ($user->Admin) { ?>
                        <th scope="col">Codice fiscale</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffs as $staff_info) { ?>
                    <?php $current = $current_only ? $staff_info : ($current_staff[$staff_info->Id] ?? null); ?>
                    <tr id="staff-<?= $staff_info->Id ?>">
                        <th scope="row">
                            <a  href="<?= $P ?>/staff/view?id=<?= $staff_info->Id ?>"
                                title="Apri la scheda staff"
                            >
                                <?= htmlspecialchars($staff_info->Nome) ?>
                            </a>
                            <span class="text-body-secondary fw-normal">#<?= $staff_info->Id ?></span>
                        </th>
                        <?php if (!$current_only) { ?>
                            <td><?= $current === null ? 'Non iscritto' : 'Iscritto' ?></td>
                        <?php } ?>
                        <td>
                            <?php if ($current instanceof Staff) { ?>
                                <a  href="<?= $P ?>/church?id=<?= $current->Parrocchia->Id ?>"
                                    title="Apri la scheda della parrocchia"
                                >
                                    <?= htmlspecialchars($current->Parrocchia->Nome) ?>
                                </a>
                                <?= $current->Referente ? ' · Referente' : '' ?>
                            <?php } else { ?>
                                —
                            <?php } ?>
                        </td>
                        <td><?= htmlspecialchars(implode(', ', $current?->Commissioni ?? []) ?: '—') ?></td>
                        <td><?= htmlspecialchars($current?->Taglia?->value ?? '—') ?></td>
                        <?php if ($user->Admin) { ?>
                            <td class="font-monospace"><?= htmlspecialchars($current?->CodiceFiscale ?? '—') ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
