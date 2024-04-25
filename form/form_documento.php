<?php 
if (!isset($anagrafica)) {
    $anagrafica = new Anagrafica(null, null, null, null);
}
?>
<label for="doc_type">Tipo documento di RICONOSCIMENTO</label>
<select name="doc_type" id="doc_type" required 
    value="<?= $anagrafica->doc_type ?>" title="La tessera sanitaria non va bene">
    <optgroup label="Documenti di riconoscimento (hanno la foto)">
        <?php
            $tipi = TipoDocumento::GetAll($connection);
            foreach ($tipi as $tipo)
            {
                if ($tipo->id === $anagrafica->doc_type)
                {
                    ?>
                        <option value="<?= $tipo->id ?>" selected="selected"><?= htmlspecialchars($tipo->label) ?></option>
                    <?php
                } else {
                    ?>
                        <option value="<?= $tipo->id ?>"><?= htmlspecialchars($tipo->label) ?></option>
                    <?php
                }
            }
        ?>
    </optgroup>
    <optgroup label="La tessera sanitaria non va bene"></optgroup>
</select>

<label for="doc_code">Codice documento</label>
<input type="text" name="doc_code" id="doc_code" required value="<?= htmlspecialchars($anagrafica->doc_code) ?>">

<label for="doc_expires">Scadenza documento</label>
<input type="date" name="doc_expires" id="doc_doc_expires" required value="<?= htmlspecialchars($anagrafica->doc_expires) ?>" min="<?= date("Y-m-d") ?>">

<label for="doc_file">Documento (sia fronte che retro)</label>
<input type="file" name="doc_file" id="doc_file" required accept="<?= join(", ", ALLOWED_EXT_DOTS) ?>" />