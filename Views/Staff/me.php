<?php
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\Staff;
use Amichiamoci\Models\Templates\Anagrafica as AnagraficaBase;
    if (isset($staff)) { 
?>
    <h1>
        Il tuo account staff
    </h1>
<?php } else { ?>
    <h1>
        Diventa staff
    </h1>
<?php } ?>

<form method="post" id="staff-form">
    <div class="form-floating mb-3">
        <select 
            id="anagrafica" name="anagrafica"
            required
            class="form-control"
            <?= (isset($user->IdAnagrafica) ? "disabled" : "") ?>
            title="Dati anagrafici">
            <option value="">Scegli</option>
            <?php foreach ($anagrafiche as $anagrafica) {
                if (!($anagrafica instanceof AnagraficaBase)) continue;
            ?>
                <option 
                    value="<?= $anagrafica->Id ?>"
                    <?= ($anagrafica->Id === $user->IdAnagrafica ? "selected" : "") ?>>
                    <?= htmlspecialchars(string: $anagrafica->Nome . " " . $anagrafica->Cognome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="anagrafica">Dati Anagrafici</label>
        <div class="invalid-feedback">
            Per favore, seleziona una persona
        </div>
        <div class="form-text user-select-none ms-2">
            Non hai mai partecipato come concorrente?
            Registra prima i tuoi dati
            <a href="" class="link-underline link-underline-opacity-0">qui</a>
        </div>
    </div>
    <div class="form-floating mb-3">
        <select 
            id="parrocchia" name="parrocchia"
            required
            class="form-control"
            title="Parrocchia">
            <option value="">Scegli</option>
            <?php foreach ($parrocchie as $parrocchia) {
                if (!($parrocchia instanceof Parrocchia)) continue;
            ?>
                <option 
                    value="<?= $parrocchia->Id ?>"
                    <?= ((isset($staff) && 
                        ($staff instanceof Staff) && 
                        $staff->Parrocchia->Id === $parrocchia->Id) ? "selected" : "") ?>>
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
        <button class="btn btn-primary" type="submit">
            <?php if (isset($staff)) { ?>
                Modifica i dati
            <?php } else { ?>
                Diventa uno staff
            <?php } ?>
        </button>
    </div>
</form>

<script>
    $().ready(function() {
        $("#staff-form").validate();
    });
</script>