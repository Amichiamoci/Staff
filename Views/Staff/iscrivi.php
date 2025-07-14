<?php

use Amichiamoci\Models\Staff;
use Amichiamoci\Utils\File;

if (empty($parrocchia) && isset($staff) && $staff instanceof Staff)
{
    $parrocchia = $staff->Parrocchia->Id;
}
if (empty($taglia))
{
    $taglia = 'L';
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
    enctype="multipart/form-data"
    action="<?= $B ?>/staff/iscrivi"
>
    <input type="hidden" name="id" required value="<?= $target->Id?>">

    <?php if ($user->IsAdmin) { ?>
        <div class="form-floating mb-3">
            <select
                required
                class="form-control"
                id="edizione" 
                name="edizione"
            >
                <?php foreach ($edizioni as $edizione) { ?>
                    <option value="<?= $edizione->Id ?>" <?= ($edizione->Year === (int)date(format: 'Y')) ? 'selected' : ''?>>
                        <?= $edizione->Year ?>:
                        <?= htmlspecialchars(string: $edizione->Motto) ?>
                    </option>
                <?php } ?>
            </select>
            <label for="edizione">Edizione</label>
            <div class="invalid-feedback">
                Per favore, scegli un'edizione
            </div>
        </div>
    <?php } ?>

    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="parrocchia" 
            name="parrocchia"
        >
            <option value="">Scegli una parrocchia</option>
            <?php foreach ($parrocchie as $p) { ?>
                <option value="<?= $p->Id ?>"
                    <?= ($p->Id === $parrocchia) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $p->Nome) ?>
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
            id="taglia" 
            name="taglia"
        >
            <option value="">Scegli una taglia</option>
            <?php foreach ($taglie as $t) { ?>
                <option value="<?= htmlspecialchars(string: $t) ?>"
                    <?= ($t === $taglia) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $t) ?>
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
            <select <?php /*required*/ ?>
                class="form-control"
                id="tutore" 
                name="tutore"
            >
                <option value="">Scegli</option>
                <?php foreach ($adulti as $a) { ?>
                    <option value="<?= $a->Id ?>"
                        <?= (!empty($tutore) && $a->Id === $tutore) ? 'selected' : '' ?>
                    >
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
            type="file" 
            multiple 
            class="form-control" 
            id="certificato" 
            name="certificato[]"
            accept="<?= File::ALLOWED_EXT_DOTS() ?>"
        >
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

    <?php if (!empty($id_iscrizione)) { ?>
        <input type="hidden" name="id_iscrizione" value="<?= $id_iscrizione ?>">
    <?php } ?>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit" id="submit">
            <?php if (!empty($id_iscrizione)) { ?>
                Aggiorna iscrizione
                <i class="bi bi-person-fill-up"></i>
            <?php } else { ?>
                Iscrivi
                <i class="bi bi-person-add"></i>
            <?php } ?>
        </button>
    </div>
</form>