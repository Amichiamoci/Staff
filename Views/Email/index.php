<?php

use Amichiamoci\Models\Email;

?>

<h1>
    Le ultime 2000 email
</h1>
<div class="row">
    <?php foreach ($emails as $email) {
        if (!($email instanceof Email)) continue;
    ?>
        <div class="col col-sm-6 col-md-4">
            <div class="card mb-1">
                <div class="card-header font-monospace user-select-none">
                    <a href="/email/view?id=<?= $email->Id ?>" 
                        class="text-reset link-underline link-underline-opacity-0"
                        title="Vedi contenuto">
                        #<?= $email->Id ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="card-title">
                        <a 
                            href="mailto:<?= htmlspecialchars(string: $email->Receiver) ?>"
                            class="link-underline link-underline-opacity-0 text-reset">
                            <strong>
                                <?= htmlspecialchars(string: $email->Receiver) ?>
                            </strong>
                        </a>
                    </div>
                    <dl class="row">
                        <dt class="col-sm-4 text-nowrap">
                            Oggetto
                        </dt>
                        <dd class="col-sm-8">
                            <a href="/email/view?id=<?= $email->Id ?>" 
                                class="text-reset link-underline link-underline-opacity-0"
                                title="Vedi contenuto">
                                <strong>
                                    <?= htmlspecialchars(string: $email->Subject) ?>
                                </strong>
                            </a>
                        </dd>

                        <dt class="col-sm-4 text-nowrap">
                            <?php if ($email->Received) { ?>
                                Inviata
                            <?php } else { ?>
                                Non ricevuta
                            <?php } ?>
                        </dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars(string: $email->Sent) ?>
                        </dd>

                        <?php if (!empty($email->Opened)) { ?>
                            <dt class="col-sm-4 text-nowrap">
                                Aperta
                            </dt>
                            <dd class="col-sm-8">
                                <?= htmlspecialchars(string: $email->Opened) ?>
                            </dd>
                        <?php } ?>
                    </dl>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
