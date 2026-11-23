<?php

assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($message) && is_string($message), 'var $message not valid');
assert(!isset($username) || is_string($username), 'var $username not valid');

?>

<h1>
    Effettua il login
</h1>

<form
    method="post"
    id="login-form"
>
    <div class="form-floating mb-3">
        <input
            required
            type="text"
            class="form-control"
            id="username"
            autocomplete="username"
            name="username"
            placeholder="..."
            value="<?= empty($username) ? '' : htmlspecialchars(string: $username) ?>"
        >
        <label for="username">Utente</label>
        <div class="invalid-feedback">
            Per favore, immetti un utente
        </div>
    </div>
    <div class="form-floating">
        <input
            required
            type="password"
            class="form-control"
            id="password"
            name="password"
            placeholder="Password"
            autocomplete="current-password"
        >
        <label for="password">Password</label>
        <div class="invalid-feedback">
            Per favore, immetti una password
        </div>
        <div class="form-text">
            Password dimenticata?
            Clicca
            <a  href="<?= $P ?>/user/password_recover"
                class="link-underline link-underline-opacity-0 link-primary"
                title="Recupera la password"
            >
                qui
            </a>
        </div>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="col mt-2">
            <div
                class="alert alert-warning user-select-none"
                role="alert"
            >
                <strong>
                    <i class="bi bi-exclamation-triangle"></i>
                </strong>    
                <?= htmlspecialchars(string: $message) ?>
            </div>
        </div>
    <?php } ?>

    <div class="col-12 mt-2">
        <button
            class="btn btn-primary g-recaptcha"
            type="submit"
            <?php if (!empty(RECAPTCHA_PUBLIC_KEY)) { ?>
            data-sitekey="<?= RECAPTCHA_PUBLIC_KEY ?>"
            data-callback="recaptchaCallback"
            data-action="login"
            <?php } ?>
            title="Accedi"
        >
            Accedi
        </button>
    </div>
</form>

<?php if (!empty(RECAPTCHA_PUBLIC_KEY)) { ?>
    <script>
        function recaptchaCallback() {
            $('#login-form').submit();
        }
    </script>
    <script
        src="https://www.google.com/recaptcha/enterprise.js?render=<?= RECAPTCHA_PUBLIC_KEY ?>"
        async
        defer
    ></script>
<?php } ?>