<link rel="stylesheet" href="<?= $P ?>/Public/quill/quill.snow.css">
<script src="<?= $P ?>/Public/quill/quill.js" defer></script>
<script src="<?= $P ?>/js/email-editor.js" defer></script>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="mb-0">Invia email</h1>
    <a class="btn btn-outline-secondary" href="<?= $P ?>/email">Ultime 500 email</a>
</div>

<form method="post" action="<?= $P ?>/email/send" id="email-compose">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="mb-3">
        <label class="form-label" for="to">Destinatario</label>
        <input type="email" class="form-control" id="to" name="to" maxlength="64" required
            autocapitalize="none" spellcheck="false" value="<?= htmlspecialchars($to ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="subject">Oggetto</label>
        <input type="text" class="form-control" id="subject" name="subject" maxlength="128" required
            value="<?= htmlspecialchars($subject ?? '') ?>">
    </div>
    <div class="mb-3">
        <label id="email-body-label" for="body" class="form-label">Corpo del messaggio (HTML)</label>
        <textarea id="body" name="body" class="form-control font-monospace" rows="12" required><?= htmlspecialchars($body ?? '') ?></textarea>
        <div id="email-editor-wrapper" hidden>
            <div id="email-editor"></div>
        </div>
        <p id="email-body-error" class="text-danger mt-1" role="alert" hidden>Inserisci il corpo del messaggio.</p>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-envelope-arrow-up-fill" aria-hidden="true"></i>
        Invia email
    </button>
</form>
