<?php

use Amichiamoci\Models\Anagrafica;
use Amichiamoci\Utils\File;

$is_editing = isset($anagrafica) && ($anagrafica instanceof Anagrafica);

if ($is_editing) {
?>
    <h1>
        Modifica dati
    </h1>
    <h4 class="user-select-none">
        Aggiorna le generalità di <?= htmlspecialchars(string: $anagrafica->Nome) ?>
    </h4>
<?php } else { ?>
    <h1>
        Aggiungi persona
    </h1>
<?php } ?>

<form 
    method="post" 
    id="anagrafica-form" 
    enctype="multipart/form-data"
    action="<?= $B ?>/staff/new_anagrafica">

    <?php if ($is_editing) { ?>
        <input type="hidden" name="id" required value="<?= $anagrafica->Id?> ">
    <?php } ?>

    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="nome" name="nome" 
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->Nome) : '' ?>">
        <label for="nome">Nome</label>
        <div class="invalid-feedback">
            Per favore, immetti un nome
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="cognome" name="cognome" 
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->Cognome) : '' ?>">
        <label for="cognome">Cognome</label>
        <div class="invalid-feedback">
            Per favore, immetti un cognome
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="cf" name="cf" 
            pattern="[a-zA-Z]{6}[0-9]{2}[a-zA-Z][0-9]{2}[a-zA-Z][0-9]{3}[a-zA-Z]"
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->FiscalCode) : '' ?>">
        <label for="cf">Codice Fiscale</label>
        <div class="invalid-feedback">
            Per favore, immetti un codice fiscale
        </div>
    </div>

    <div class="alert alert-warning d-none" role="alert">
        <strong class="user-select-none">
            <i class="bi bi-exclamation-triangle"></i>
        </strong>    
        <p id="error-body"></p>
    </div>
    
    <div class="form-floating mb-3">
        <input 
            required
            type="date" 
            class="form-control" 
            id="compleanno" name="compleanno" 
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->AmericanBirthDay()) : '' ?>"
            max="<?= date(format: "Y-m-d") ?>">
        <label for="compleanno">Data di nascita</label>
        <div class="invalid-feedback">
            Per favore, immetti una data di nascita
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="provenienza" name="provenienza" 
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->From) : '' ?>">
        <label for="provenienza">Luogo di nascita</label>
        <div class="invalid-feedback">
            Per favore, immetti un luogo di nascita
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="email" 
            class="form-control" 
            id="email" name="email" 
            value="<?= ($is_editing && !empty($anagrafica->Email)) ? htmlspecialchars(string: $anagrafica->Email) : '' ?>">
        <label for="email">Email</label>
        <div class="invalid-feedback">
            Per favore, immetti un'email
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            type="tel" 
            class="form-control" 
            id="telefono" name="telefono" 
            value="<?= ($is_editing && !empty($anagrafica->Phone)) ? htmlspecialchars(string: $anagrafica->Phone) : '' ?>">
        <label for="telefono">Numero di telefono</label>
        <div class="invalid-feedback">
            Per favore, immetti un numero di telefono
        </div>
    </div>
    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="doc_type" name="doc_type">
            <?php foreach ($tipi_documento as $tipo) { ?>
                <option value="<?= $tipo->Id ?>"
                    <?= (($is_editing && $anagrafica->DocumentType === $tipo->Id) || $tipo->Id === 1) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $tipo->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="doc_type">Tipo documento</label>
        <div class="invalid-feedback">
            Per favore, scegli un tipo di documento
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            class="form-control" 
            id="doc_code" name="doc_code" 
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->DocumentCode) : '' ?>">
        <label for="doc_code">Codice Documento</label>
        <div class="invalid-feedback">
            Per favore, immetti il codice del documento
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="date" 
            class="form-control" 
            id="doc_expires" name="doc_expires" 
            value="<?= $is_editing ? htmlspecialchars(string: $anagrafica->DocumentExpiration) : '' ?>"
            <?= (!$is_editing) ? 'min="' . date(format: "Y-m-d") . '"' : '' ?>>
        <label for="doc_expires">Scadenza Documento</label>
        <div class="invalid-feedback">
            Per favore, immetti la scadenza del documento
        </div>
    </div>
    <div class="form-floating mb-3">
        <input 
            required
            type="file" multiple 
            class="form-control" 
            id="doc" name="doc[]"
            accept="<?= File::ALLOWED_EXT_DOTS() ?>">
        <label for="doc">Documento</label>
        <div class="invalid-feedback">
            Per favore, immetti una o più foto del documento
        </div>
        <div class="form-text user-select-none ms-2">
            Allegare foto sia del fronte che del retro del documento. Possibilmente in un unico file
        </div>
    </div>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit" id="submit">
            <?php if ($is_editing) { ?>
                Modifica
                <i class="bi bi-pencil"></i>
            <?php } else { ?>
                Aggiungi
                <i class="bi bi-person-add"></i>
            <?php } ?>
        </button>
    </div>
</form>

<script src="<?= $B ?>/Public/js/codicefiscale.js" defer></script>
<script src="<?= $B ?>/Public/js/anagrafica.js" defer></script>