<h1>
    Reimposta la password
</h1>

<form method="post" id="password-reset-form">
    <input type="hidden" name="value" value="<?= htmlspecialchars(string: $value) ?>">

    <?php if (isset($secret) && strlen(string: $secret) > 0) { ?>
        <input type="hidden" name="secret" value="<?= htmlspecialchars(string: $secret) ?>">
    <?php } else { ?>
        <div class="form-floating mb-3">
            <input 
                required
                type="text" 
                class="form-control" 
                id="secret" name="secret" 
                placeholder="..."
                autocomplete="off">
            <label for="secret">Codice</label>
            <div class="invalid-feedback">
                Per favore, immetti il codice ricevuto per mail
            </div>
        </div>
    <?php } ?>

    <div class="form-floating mb-3">
        <input 
            required
            type="password" 
            class="form-control" 
            id="password" name="password"
            minlength="8">
        <label for="password">Nuova Password</label>
        <div class="invalid-feedback">
            Per favore, immetti una password valida
        </div>
    </div>

    <div class="col-12 mt-2">
        <button 
            class="btn btn-primary g-recaptcha" 
            type="submit">
            Aggiorna password
        </button>
    </div>
</form>