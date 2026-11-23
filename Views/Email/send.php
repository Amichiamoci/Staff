<h1>
    Invia Email
</h1>

<form method="post">
    <div class="form-floating mb-3">
        <input 
            required
            type="email"
            class="form-control" 
            id="to"
            name="to"
            value="<?= htmlspecialchars($to ?? '') ?>"
        >
        <label for="to">Destinatario</label>
        <div class="invalid-feedback">
            Per favore, immetti un destinatario
        </div>
    </div>

    <div class="form-floating mb-3">
        <input 
            required
            type="text" class="form-control" 
            id="subject"
            name="subject"
            value="<?= htmlspecialchars($subject ?? '') ?>"
        >
        <label for="subject">Oggetto</label>
        <div class="invalid-feedback">
            Per favore, immetti un oggetto
        </div>
    </div>
    <div class="mb-3">
        <label for="body" class="form-label">Corpo del messaggio (HTML)</label>
        <textarea id="body" name="body" class="form-control font-monospace" rows="10" required
            data-editor-license-key="<?= htmlspecialchars($editorLicenseKey) ?>"
        ><?= htmlspecialchars($body ?? '') ?></textarea>
        <p id="email-body-error" class="text-danger mt-1" role="alert" hidden>Inserisci il corpo del messaggio.</p>
    </div>

    <button 
        type="submit"
        class="btn btn-primary m-1"
        title="Invia email"
        data-confirm="Sicuro di voler inviare l'email? L'operazione non sarà reversibile"
        data-confirm-btn="Sì"
        data-cancel-btn="Annulla"
    >
        Invia
        <i class="bi bi-envelope-arrow-up-fill"></i>
    </button>
</form>

<?php if ($editorLicenseKey !== '') { ?>
    <link rel="stylesheet"
        href="https://cdn.ckeditor.com/ckeditor5/49.1.0/ckeditor5.css"
        integrity="sha384-Ik86sZFfK6DFSEqNb0YngU5v3igL9OXn0BRTeWz8HuqB8AGVR6ZQBwfkoRKvCvfD"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <script defer
        src="https://cdn.ckeditor.com/ckeditor5/49.1.0/ckeditor5.umd.js"
        integrity="sha384-pvS9kI6PwqtRN6Gd07beavMP8NvNjO5Abp96fjdsxAkmcGfzN0IPjkfWySTuBXUz"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script defer
        src="https://cdn.ckeditor.com/ckeditor5/49.1.0/translations/it.umd.js"
        integrity="sha384-FGjgPBewHs6FRTPCK+2y/e/yqoN1jS7ewB0Q0HFOgYb134TTZLjCs/Kb6ZzF5iXq"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="<?= $P ?>/js/email-editor.js" defer></script>
<?php } ?>
