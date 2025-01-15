<h1>
    Crea nuovo utente
</h1>

<form method="post" id="login-form">
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="email" name="email" 
            placeholder="abc@site.tld"
            value="<?= empty($email) ? '' : htmlspecialchars(string: $email) ?>">
        <label for="email">Email</label>
        <div class="invalid-feedback">
            Per favore, immetti un'email
        </div>
    </div>

    <div class="form-check">
        <input class="form-check-input" type="checkbox" value="" id="admin" name="admin">
        <label class="form-check-label" for="admin">
            Utente amministratore
        </label>
    </div>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit">
            Crea
        </button>
    </div>
</form>

<script>
    $().ready(function() {
        $("#login-form").validate();
    });
</script>