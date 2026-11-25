<?php

use Amichiamoci\Models\UserActivity;

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($activity) && is_array($activity), 'var $activity not valid');
assert(isset($show_accounts) && is_bool($show_accounts), 'var $show_accounts not valid');

?>

<?php if ($activity === []) { ?>
    <p>Nessun accesso registrato.</p>
<?php } else { ?>
    <div
        class="table-responsive"
        tabindex="0"
        role="region"
        aria-label="Tabella degli accessi"
    >
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <?php if ($show_accounts) { ?>
                        <th scope="col">Account</th>
                    <?php } ?>
                    <th scope="col">Accesso</th>
                    <th scope="col">Ultima attività</th>
                    <th scope="col">Durata</th>
                    <th scope="col">Indirizzo IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activity as $login) { ?>
                    <?php
                    assert($login instanceof UserActivity);
                    $duration = $login->Duration();
                    $minutes = $duration === null ? null : $duration->days * 1440 + $duration->h * 60 + $duration->i;
                    ?>
                    <tr>
                        <?php if ($show_accounts) { ?>
                            <th scope="row">
                                <a  href="<?= $P ?>/user/view?id=<?= $login->UserId ?>"
                                    title="Apri la scheda account"
                                >
                                    <?= htmlspecialchars($login->UserName) ?>
                                </a>
                            </th>
                        <?php } ?>
                        <td class="text-nowrap"><?= htmlspecialchars($login->Start?->format('d/m/Y H:i') ?? '—') ?></td>
                        <td class="text-nowrap"><?= htmlspecialchars($login->LastHit?->format('d/m/Y H:i') ?? '—') ?></td>
                        <td class="text-nowrap"><?= $minutes === null ? '—' : $minutes . ' min' ?></td>
                        <td class="text-nowrap">
                            <?php $ip = $login->Ip; ?>
                            <?php require __DIR__ . '/../Shared/Ip-Address.php'; ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
