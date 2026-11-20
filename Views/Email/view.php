<?php
    use Amichiamoci\Models\Email;
    if (!($email instanceof Email)) {
        throw new \Exception(message: 'Invalid object');
    }
?>

<h2>
    Email a 
    <a 
        href="mailto:<?= htmlspecialchars(string: $email->Receiver) ?>"
        class="link-underline link-underline-opacity-0 text-reset font-monospace"
    >
        <?= htmlspecialchars(string: $email->Receiver) ?>
    </a>
</h2>

<dl class="row">
    <dt class="col-sm-4 text-nowrap">
        Oggetto
    </dt>
    <dd class="col-sm-8">
        <?= htmlspecialchars(string: $email->Subject) ?>
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

<iframe
    class="email-preview border"
    title="Visualizzazione dell'email"
    sandbox
    srcdoc="<?= htmlspecialchars(
        '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; img-src data:">'
        . $email_preview
    ) ?>"
></iframe>

<h3>Corpo effettivo dell'email</h3>
<pre><code><?= htmlspecialchars($email->Content) ?></code></pre>
