<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($csrfToken) && is_string($csrfToken), 'var $csrfToken not valid');
assert(!isset($to) || is_string($to), 'var $to not valid');
assert(!isset($subject) || is_string($subject), 'var $subject not valid');
assert(!isset($body) || is_string($body), 'var $body not valid');

?>

<link
    rel="stylesheet"
    href="<?= $P ?>/quill/quill.snow.css"
>
<script
    src="<?= $P ?>/quill/quill.js"
    defer
></script>
<script
    src="<?= $P ?>/js/email-editor.js"
    defer
></script>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="mb-0">Invia email</h1>
    <a  class="btn btn-outline-secondary"
        href="<?= $P ?>/email"
        title="Ultime 500 email"
    >Ultime 500 email</a>
</div>

<form
    method="post"
    action="<?= $P ?>/email/send"
    id="email-compose"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrfToken) ?>"
    >

    <div class="form-floating mb-3">
        <input
            type="email"
            class="form-control"
            id="to"
            placeholder="Destinatario"
            name="to"
            maxlength="64"
            required
            autocapitalize="none"
            spellcheck="false"
            value="<?= htmlspecialchars($to ?? '') ?>"
        >
        <label for="to">Destinatario</label>
    </div>
    <div class="form-floating mb-3">
        <input
            type="text"
            class="form-control"
            id="subject"
            placeholder="Oggetto"
            name="subject"
            maxlength="128"
            required
            value="<?= htmlspecialchars($subject ?? '') ?>"
        >
        <label for="subject">Oggetto</label>
    </div>
    <div class="mb-3">
        <div id="email-body-fallback">
            <textarea
                id="body"
                name="body"
                class="form-control font-monospace"
                rows="12"
                required
            ><?= htmlspecialchars($body ?? '') ?></textarea>
        </div>
        <div
            id="email-editor-wrapper"
            hidden
        >
            <div
                id="email-body-label"
                class="form-label"
            >
                Corpo del messaggio
            </div>
            <div id="email-editor"></div>
        </div>
        <p  id="email-body-error"
            class="text-danger mt-1"
            role="alert"
            hidden
        >Inserisci il corpo del messaggio.</p>
    </div>

    <button
        type="submit"
        class="btn btn-primary"
        title="Invia email"
    >
        <i  class="bi bi-envelope-arrow-up-fill"
            aria-hidden="true"
        ></i>
        Invia email
    </button>
</form>
