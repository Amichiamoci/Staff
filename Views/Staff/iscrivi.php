<?php

use Amichiamoci\Models\Staff;
$default_parrocchia = 0;
if (isset($staff) && $staff instanceof Staff) {
    $default_parrocchia = $staff->Parrocchia->Id;
}
?>
<h1>
    Iscrivi <?= htmlspecialchars(string: $target->Nome . ' ' . $target->Cognome) ?>
</h1>
<p>
    Iscrivi <?= htmlspecialchars(string: $target->Nome) ?> ad <?= SITE_NAME ?> <?= date(format: 'Y')?>
</p>

<form 
    method="post" 
    id="iscrivi-form" 
    enctype="multipart/form-data"
    action="/staff/iscrivi">
    <input type="hidden" name="id" required value="<?= $target->Id?> ">

    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="parrocchia" name="parrocchia">
            <option value="">Scegli una parrocchia</option>
            <?php foreach ($parrocchie as $parrocchia) { ?>
                <option value="<?= $parrocchia->Id ?>"
                    <?= ($parrocchia->Id === $default_parrocchia) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $parrocchia->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="parrocchia">Parrocchia</label>
        <div class="invalid-feedback">
            Per favore, scegli una Parrocchia
        </div>
    </div>

    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="taglia" name="taglia">
            <option value="">Scegli una parrocchia</option>
            <?php foreach ($taglie as $taglia) { ?>
                <option value="<?= htmlspecialchars(string: $taglia) ?>"
                    <?= ($taglia === 'L') ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $taglia) ?>
                </option>
            <?php } ?>
        </select>
        <label for="taglia">Taglia</label>
        <div class="invalid-feedback">
            Per favore, scegli una Taglia
        </div>
    </div>

    <?php if ($target->Eta < 18) { ?>
        <div class="form-floating mb-3">
            <select
                required
                class="form-control"
                id="tutore" name="tutore">
                <option value="">Scegli</option>
                <?php foreach ($adulti as $a) { ?>
                    <option value="<?= $a->Id ?>">
                        <?= htmlspecialchars(string: $a->Cognome . ' ' . $a->Nome) ?>
                    </option>
                <?php } ?>
            </select>
            <label for="tutore">Genitore/Tutore</label>
            <div class="invalid-feedback">
                Per favore, indica un genitore o un tutore per <?= $target->Nome ?>
                (l'anagrafica del genitore/tutore deve essere già presente nel sistema)
            </div>
        </div>
    <?php } ?>

    <div class="form-floating mb-3">
        <input 
            type="file" multiple 
            class="form-control" 
            id="certificato" name="certificato[]"
            accept="">
        <label for="certificato">Certificato medico</label>
        <div class="invalid-feedback">
            Per favore, immetti una o più foto del certificato
        </div>
        <div class="form-text user-select-none ms-2">
            È possibile omettere adesso questo file, tuttavia l'iscrizione non sarà convalidata fino a che il
            certificato non sarà consegnato.
            <strong>Chi non è in regola col certificato non scende in campo</strong>.<br>
            È richiesto un certificato ALMENO di tipo non agonistico
        </div>
    </div>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit" id="submit">
            Aggiungi
            <i class="bi bi-person-add"></i>
        </button>
    </div>
</form>