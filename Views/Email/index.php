<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 id="email-list-heading" class="mb-0"><?= htmlspecialchars($heading) ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <?php if (isset($recipients)) { ?>
            <a class="btn btn-outline-secondary" href="<?= $P ?>/email">Ultime 500 email</a>
        <?php } else { ?>
            <a class="btn btn-outline-secondary" href="<?= $P ?>/email/history">Storico per destinatario</a>
        <?php } ?>
        <a class="btn btn-primary" href="<?= $P ?>/email/send">Invia email</a>
    </div>
</div>

<?php if (isset($recipients) && $recipients !== []) { ?>
    <search class="d-block mb-4">
        <form method="get" action="<?= $P ?>/email/history">
            <label class="form-label" for="email-recipient">Destinatario</label>
            <div class="d-flex flex-wrap align-items-start gap-2">
                <select class="form-select w-auto mw-100" id="email-recipient" name="recipient" required>
                    <option value="">Seleziona un indirizzo</option>
                    <?php foreach ($recipients as $address) { ?>
                        <option value="<?= htmlspecialchars($address) ?>" <?= $address === $recipient ? 'selected' : '' ?>>
                            <?= htmlspecialchars($address) ?>
                        </option>
                    <?php } ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Mostra storico</button>
            </div>
        </form>
    </search>
<?php } ?>

<?php if ($emails === []) { ?>
    <?php if (!isset($recipients) || $recipients === []) { ?>
        <p class="text-body-secondary">Nessuna email registrata.</p>
    <?php } elseif (!empty($recipient)) { ?>
        <p class="text-body-secondary">Nessuna email trovata per <?= htmlspecialchars($recipient) ?>.</p>
    <?php } ?>
<?php } else { ?>
    <div class="table-responsive email-log-table" tabindex="0" role="region" aria-labelledby="email-list-heading">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light sticky-top">
                <tr>
                    <th scope="col">Oggetto</th>
                    <th scope="col">Destinatario</th>
                    <th scope="col">Data di invio</th>
                    <th scope="col">Stato</th>
                    <th scope="col">Apertura</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emails as $email) { ?>
                    <tr>
                        <th scope="row">
                            <a href="<?= $P ?>/email/view?id=<?= $email->Id ?>">
                                <?= htmlspecialchars($email->Subject !== '' ? $email->Subject : '(Senza oggetto)') ?>
                            </a>
                        </th>
                        <td>
                            <?php if (!isset($recipients) && $email->Receiver !== '') { ?>
                                <a href="<?= $P ?>/email/history?recipient=<?= rawurlencode($email->Receiver) ?>">
                                    <?= htmlspecialchars($email->Receiver) ?>
                                </a>
                            <?php } else { ?>
                                <?= htmlspecialchars($email->Receiver) ?>
                            <?php } ?>
                        </td>
                        <td class="text-nowrap"><?= htmlspecialchars($email->Sent) ?></td>
                        <td class="text-nowrap">
                            <span class="badge <?= $email->Received ? 'text-bg-success' : 'text-bg-warning' ?>">
                                <?= $email->Received ? 'Inviata' : 'Non ricevuta' ?>
                            </span>
                        </td>
                        <td class="text-nowrap"><?= htmlspecialchars($email->Opened ?? 'Non ancora') ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>
