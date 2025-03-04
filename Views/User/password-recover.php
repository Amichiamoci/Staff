<h1>
    Recupera la tua password
</h1>

<form method="post" id="password-recover-form">
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="username" name="username" 
            placeholder="..."
            autocomplete="off">
        <label for="username">Utente</label>
        <div class="invalid-feedback">
            Per favore, immetti un utente
        </div>
    </div>

    <div class="col-12 mt-2">
        <button 
            class="btn btn-primary g-recaptcha" 
            type="submit"
        <?php if (!empty(RECAPTCHA_PUBLIC_KEY)) { ?>
            data-sitekey="<?= RECAPTCHA_PUBLIC_KEY ?>" 
            data-callback="recaptchaCallback"
            data-action="password_recover"
        <?php } ?>
        >
            Inviami un link di recupero
        </button>
    </div>
</form>

<?php if (!empty(RECAPTCHA_PUBLIC_KEY)) { ?>
    <script>
        function recaptchaCallback() {
            $('#password-recover-form').submit();
        }
    </script>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?= RECAPTCHA_PUBLIC_KEY ?>" async defer></script>
<?php } ?>