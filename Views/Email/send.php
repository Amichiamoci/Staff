<h1>
    Invia Email
</h1>

<form method="post">
    <div class="form-floating mb-3">
        <input 
            required
            type="email" class="form-control" 
            id="to"
            name="to" 
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
        >
        <label for="subject">Oggetto</label>
        <div class="invalid-feedback">
            Per favore, immetti un oggetto
        </div>
    </div>
<!--
    <input type="hidden" name="body" id="body" required>
    <div id="body-editor"></div>
-->
    <textarea 
        name="body" 
        id="body" 
        required
        rows="10" cols="80"></textarea>

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
<script 
    src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js" 
    crossorigin="anonymous" 
    referrerpolicy="no-referrer"
    defer></script>
<script src="<?= $B ?>/Public/js/email.js" defer></script>