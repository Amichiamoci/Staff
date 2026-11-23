<?php

assert(isset($P) && is_string($P));
assert(isset($person_id) && is_int($person_id));
assert(isset($subscription_id) && is_int($subscription_id));
assert(isset($csrf_token) && is_string($csrf_token));
?>
<h1>Carica certificato medico</h1>
<form
    method="post"
    enctype="multipart/form-data"
    action="<?= $P ?>/staff/iscrivi"
>
    <input
        type="hidden"
        name="id"
        value="<?= $person_id ?>"
    >
    <input
        type="hidden"
        name="id_iscrizione"
        value="<?= $subscription_id ?>"
    >
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrf_token) ?>"
    >
    <div class="mb-3">
        <label for="certificato">Certificato medico</label>
        <input
            type="file"
            class="form-control"
            id="certificato"
            name="certificato"
            accept=".pdf,.jpg,.jpeg,.png,.webp"
            required
        >
    </div>
    <button
        type="submit"
        class="btn btn-primary"
        title="Carica il certificato medico"
    >
        Carica certificato
    </button>
</form>
