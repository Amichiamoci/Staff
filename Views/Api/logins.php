<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($logins) && is_array($logins), 'var $logins not valid');
assert(isset($page) && is_int($page), 'var $page not valid');

?>

<header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <h1 class="mb-0">Accessi Web App</h1>
    <a  href="<?= $P ?>/api/admin"
        class="btn btn-outline-primary"
        title="Apri la gestione dei token Web App"
    >
        Token Web App
    </a>
</header>

<?php if ($logins === []) { ?>
    <p>Nessun accesso da mostrare.</p>
<?php } else { ?>
    <div
        class="table-responsive"
        tabindex="0"
        role="region"
        aria-label="Registro degli accessi Web App"
    >
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Email</th>
                    <th scope="col">Data e ora</th>
                    <th scope="col">Token applicazione</th>
                    <th scope="col">Indirizzo IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logins as $login) { ?>
                    <tr>
                        <th scope="row">
                            <a  href="mailto:<?= htmlspecialchars($login['email']) ?>"
                                class="link-info link-underline link-underline-opacity-0 user-selct-0"
                                title="Invia un'email a questo utente"
                            >
                                <i class="bi bi-envelope-at"></i>
                            </a>

                            <?php if ($login['person_id'] !== null) { ?>
                                <a  href="<?= $P ?>/staff/edit_anagrafica?id=<?= (int)$login['person_id'] ?>"
                                    class="link-success link-underline link-underline-opacity-0 user-selct-text"
                                    title="Apri la scheda anagrafica"
                                >
                                    <?= htmlspecialchars($login['email']) ?>
                                </a>
                            <?php } else { ?>
                                <?= htmlspecialchars($login['email']) ?>
                            <?php } ?>
                        </th>
                        <td class="text-nowrap"><?= htmlspecialchars(substr($login['logged_at'], 0, 19)) ?></td>
                        <td>
                            #<?= (int)$login['token_id'] ?>
                            <?= htmlspecialchars($login['token_name'] ?? '(revocato)') ?>
                        </td>
                        <td>
                            <?php $ip = $login['ip_address']; ?>
                            <?php require __DIR__ . '/../Shared/Ip-Address.php'; ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php if ($page > 1 || isset($next)) { ?>
    <nav
        class="d-flex gap-2"
        aria-label="Pagine degli accessi"
    >
        <?php if ($page > 1) { ?>
            <a  href="<?= $P ?>/api/logins"
                class="btn btn-outline-primary"
                title="Torna agli accessi più recenti"
            >
                Accessi più recenti
            </a>
        <?php } ?>
        <?php if (isset($next)) { ?>
            <a  href="<?= $P ?>/api/logins?page=<?= $next ?>"
                class="btn btn-outline-primary"
                title="Mostra gli accessi precedenti"
            >
                Accessi precedenti
            </a>
        <?php } ?>
    </nav>
<?php } ?>
