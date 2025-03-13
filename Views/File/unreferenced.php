<?php
use Amichiamoci\Utils\File;
?>
<h1>
    Cancella file non più in uso (<?= count(value: $files) ?>)
</h1>

<form method="post">
    <button 
        type="submit" 
        class="btn btn-primary"
        data-confirm="Sicuro di voler cancellare i files? Questa azione non può essere annullata"
        data-confirm-btn="Sì, cancella"
        data-cancel-btn="Annulla"
    >
        <i class="bi bi-exclamation-triangle"></i>
        Avvia la cancellazione
    </button>
</form>

<ul class="list-group m-2">
    <?php foreach ($files as $file) { ?>
        <li class="list-group-item">
            <a 
                href="<?= File::GetExportUrl(path: $file) ?>"
                class="link-secondary link-underline link-underline-opacity-0 font-monospace"
                title="Scarica il file"
                download>
                <?= htmlspecialchars(string: $file) ?>
            </a>
        </li>
    <?php } ?>
</ul>