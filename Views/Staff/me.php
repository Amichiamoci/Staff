<?php
use Amichiamoci\Models\{User, Staff, Parrocchia};
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($parrocchie) && is_array($parrocchie), 'var $parrocchie not valid');
assert(!isset($staff) || $staff instanceof Staff, 'var $staff not valid');
if (isset($target)) {
    assert($target instanceof User, 'var $target not valid');
    assert(isset($anagrafiche) && is_array($anagrafiche), 'var $anagrafiche not valid');
}

?>
<?php if (isset($target)) { ?>
    <h1>Associa account staff</h1>
    <p><?= htmlspecialchars($target->Name) ?></p>
<?php } elseif (isset($staff)) { ?>
    <h1>Il tuo account staff</h1>
<?php } else { ?>
    <h1>Diventa staff</h1>
    <p>Chiedi a un amministratore di associare la tua anagrafica al tuo account.</p>
    <?php return; ?>
<?php } ?>

<form
    method="post"
    id="staff-form"
>
    <?php if (isset($target)) { ?>
        <input
            type="hidden"
            name="account"
            value="<?= $target->Id ?>"
        >
        <div class="form-floating mb-3">
            <select
                id="anagrafica"
                name="anagrafica"
                class="form-select"
                required
            >
                <option value="">Scegli</option>
                <?php foreach ($anagrafiche as $anagrafica) {
                    if (!($anagrafica instanceof AnagraficaBase)) continue;
                    ?>
                    <option value="<?= $anagrafica->Id ?>">
                        <?= htmlspecialchars($anagrafica->Nome . ' ' . $anagrafica->Cognome) ?>
                    </option>
                <?php } ?>
            </select>
            <label for="anagrafica">Dati anagrafici</label>
        </div>
    <?php } ?>
    <div class="form-floating mb-3">
        <select
            id="parrocchia"
            name="parrocchia"
            required
            class="form-control"
            title="Parrocchia"
        >
            <option value="">Scegli</option>
            <?php foreach ($parrocchie as $parrocchia) {
                if (!($parrocchia instanceof Parrocchia)) continue;
            ?>
                <option
                    value="<?= $parrocchia->Id ?>"
                    <?= (!isset($target) && isset($staff) &&
                        ($staff instanceof Staff) &&
                        $staff->Parrocchia->Id === $parrocchia->Id) ? "selected" : "" ?>
                >
                    <?= htmlspecialchars(string: $parrocchia->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="parrocchia">Parrocchia</label>
        <div class="invalid-feedback">
            Per favore, seleziona una parrocchia
        </div>
        <div class="form-text user-select-none ms-2">
            Potrai cambiare la tua scelta in seguito
        </div>
    </div>

    <div class="col-12 mt-2">
        <button
            class="btn btn-primary"
            type="submit"
            title="Salva i dati dello staffista"
        >
            <?php if (!isset($target)) { ?>
                Modifica i dati
            <?php } else { ?>
                Associa account
            <?php } ?>
        </button>
    </div>
</form>
