<?php

use Amichiamoci\Models\Edizione;
?>
<h1>
    Edizioni di <?= SITE_NAME ?>
</h1>
<ul class="listgroup-flush">
    <?php foreach ($edizioni as $e) { 
        if (!($e instanceof Edizione)) 
            continue; 
    ?>
        <li class="list-group-item">
            <strong><?= $e->Year ?></strong>
            "<span class="font-italic">
                <?= htmlspecialchars(string: $e->Motto) ?>
            </span>"
        </li>
    <?php } ?>
</ul>
<?php if ($user->Admin) { ?>
    <hr>
    <p>
        Aggiungi una nuova edizione o modifica una esistente
    </p>
    
    <form method="post">

        <div class="form-floating mb-3">
            <select
                required
                class="form-control"
                id="anno" name="anno"
            >
                <option value="">Scegli un anno</option>
                <?php 
                    $current_year = (int)date(format: "Y");
                    $anni = [
                        $current_year - 2, $current_year - 1, $current_year, $current_year + 1, $current_year + 2
                    ];
                    foreach ($anni as $anno) { 
                ?>
                    <option value="<?= $anno ?>"><?= $anno ?></option>
                <?php } ?>
            </select>
            <label for="anno">Anno</label>
            <div class="invalid-feedback">
                Per favore, indica un anno tra i proposti
            </div>
        </div>

        <div class="form-floating mb-3">
            <input
                type="text"
                required
                class="form-control"
                id="motto" name="motto"
                size="35"
                maxlength="256"
            >
            <label for="motto">Motto</label>
            <div class="invalid-feedback">
                Per favore, scrivi un motto di massimo 256 caratteri
            </div>
        </div>

        <div class="col-12 mt-2">
            <button class="btn btn-primary" type="submit" id="submit">
                Invia
            </button>
        </div>
    </form>
<?php } ?>