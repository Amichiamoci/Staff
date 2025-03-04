<h1>
    Crea nuovo torneo
</h1>

<form 
    action="/sport/tournament_create" 
    method="post"
>
    <div class="form-floating mb-3">
        <select 
            name="edition" 
            id="edition"
            class="form-control"
            required
        >
            <?php

use Amichiamoci\Models\TipoTorneo;

 foreach ($edizioni as $edizione) { ?>
                <option value="<?= $edizione->Id ?>">
                    <?= $edizione->Year ?>
                </option>
            <?php } ?>
        </select>
        <label for="edition">Edizione</label>
        <div class="invalid-feedback">
            Per favore, seleziona un'edizione
        </div>
    </div>

    <div class="form-floating mb-3">
        <select 
            name="sport" 
            id="sport"
            class="form-control"
            required
        >
            <option value="">Scegli</option>
            <?php foreach ($sport as $s) { ?>
                <option value="<?= $s->Id ?>">
                    <?= htmlspecialchars(string: $s->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="sport">Sport</label>
        <div class="invalid-feedback">
            Per favore, seleziona uno sport
        </div>
    </div>

    <div class="form-floating mb-3">
        <select 
            name="type" 
            id="type"
            class="form-control"
            required
        >
            <?php foreach ($tipi_torneo as $tipo) { ?>
                <option value="<?= $tipo->Id ?>"
                    <?=($tipo->Id === TipoTorneo::$RoundRobin) ? 'selected' : ''?>>
                    <?= htmlspecialchars(string: $tipo->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="type">Tipologia</label>
        <div class="invalid-feedback">
            Per favore, seleziona una tipologia
        </div>
    </div>

    <div class="form-floating mb-3">
        <input 
            type="text" 
            name="name" 
            id="name" 
            class="form-control" 
            required
            maxlength="128">
        <label for="name">Nome</label>
        <div class="invalid-feedback">
            Per favore, indica un nome
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        Crea
    </button>
</form>