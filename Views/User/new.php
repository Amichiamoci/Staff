<?php

assert(!isset($email) || is_string($email), 'var $email not valid');
assert(isset($admin) && is_bool($admin), 'var $admin not valid');
assert(isset($csrf_token) && is_string($csrf_token), 'var $csrf_token not valid');

?>

<h1>
    Crea nuovo utente
</h1>

<form method="post">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrf_token) ?>"
    >
    <div class="form-floating mb-3">
        <input
            required
            type="email"
            maxlength="64"
            class="form-control"
            id="email"
            name="email"
            placeholder="abc@site.tld"
            value="<?= empty($email) ? '' : htmlspecialchars(string: $email) ?>"
        >
        <label for="email">Email</label>
        <div class="invalid-feedback">
            Per favore, immetti un'email
        </div>
    </div>

    <div class="form-check">
        <input
            class="form-check-input"
            type="checkbox"
            value="1"
            <?= $admin ? 'checked' : '' ?>
            id="admin"
            name="admin"
        >
        <label
            class="form-check-label"
            for="admin"
        >
            Utente amministratore
        </label>
    </div>

    <div class="col-12 mt-2">
        <button
            class="btn btn-primary"
            type="submit"
            title="Crea"
        >
            Crea
        </button>
    </div>
</form>