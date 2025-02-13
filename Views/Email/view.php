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
        class="link-underline link-underline-opacity-0 text-reset font-monospace">
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
    style="width: 100%;height: auto;max-height: 50vh;" 
    class="border border-1"
    id="iframe"
    title="Visualizzazione dell'email"></iframe>
<!-- We cannot just HTML escape the content and put it here! -->
<script>
    (() => {
        const iframe = document.getElementById('iframe');
        const iframeDoc = iframe.contentWindow.document;
        if (!iframeDoc) {
            console.warn('Impossibile effettuare il rendering');
            iframe.classList.add('d-none');
            return;
        }
        iframe.setAttribute('sandbox', 'allow-forms');
        iframeDoc.open(); 
        iframeDoc.write('<?= $content_escaped ?>'); // Assuming js-escaped content
        iframeDoc.close();
    })();
</script>

<h3>
    Corpo effettivo dell'email:
</h3>
<pre><code class="language-html"><?= htmlspecialchars(string: $email->Content) ?></code></pre>  

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/html.min.js"></script>
<script>
    (() => {
        $('head').append( $('<link rel="stylesheet" type="text/css">').attr('href', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css') );
        hljs.highlightAll();
    })();
</script>