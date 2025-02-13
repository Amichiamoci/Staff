<h1>
    Effettua il login
</h1>

<form method="post">
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="username" name="username" 
            placeholder="..."
            value="<?= empty($username) ? '' : htmlspecialchars(string: $username) ?>">
        <label for="username">Utente</label>
        <div class="invalid-feedback">
            Per favore, immetti un utente
        </div>
    </div>
    <div class="form-floating">
        <input 
            required
            type="password" class="form-control" 
            id="password" name="password" 
            placeholder="Password"
            value="<?= empty($password) ? '' : htmlspecialchars(string: $password) ?>">
        <label for="password">Password</label>
        <div class="invalid-feedback">
            Per favore, immetti una password
        </div>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="col mt-2">
            <div class="alert alert-warning user-select-none" role="alert">
                <strong>
                    <i class="bi bi-exclamation-triangle"></i>
                </strong>    
                <?= htmlspecialchars(string: $message) ?>
            </div>
        </div>
    <?php } ?>

    <div class="col m-1">
        <div class="cf-turnstile" data-sitekey="<?= CF_TURNSTILE_TOKEN ?>"></div>
    </div>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit">
            Accedi
        </button>
    </div>
</form>