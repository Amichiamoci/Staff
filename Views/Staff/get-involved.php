<h1>
    Partecipa all'edizione corrente di <?= SITE_NAME ?>
</h1>
<form method="post">
    <?php if ($user->Admin) { ?>
        <div class="form-floating mb-3">
            <select
                required
                class="form-control"
                id="edition" 
                name="edition"
            >
                <option value="">Scegli un'edizione</option>
                <?php foreach ($edizioni as $edizione) { ?>
                    <option value="<?= $edizione->Id ?>"
                        <?= (isset($edizione_corrente) && $edizione->Id === $edizione_corrente->Id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(string: $edizione->Motto) ?>
                    </option>
                <?php } ?>
            </select>
            <label for="edition">Edizione</label>
            <div class="invalid-feedback">
                Per favore, scegli un'edizione
            </div>
        </div>
    <?php } else { ?>
        <input type="hidden" name="edition" required value="<?= $edizione_corrente->Id ?>">
    <?php } ?>
    
    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="t_shirt"
            name="t_shirt"
        >
            <option value="">Scegli una taglia</option>
            <?php foreach ($taglie as $taglia) { ?>
                <option value="<?= htmlspecialchars(string: $taglia) ?>"
                    <?= $staff->Taglia === $taglia ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(string: $taglia) ?>
                </option>
            <?php } ?>
        </select>
        <label for="t_shirt">Maglia</label>
        <div class="invalid-feedback">
            Per favore, scegli una taglia
        </div>
    </div>

    <h3>
        Commissioni
    </h3>
    <ul class="list-group overflow-y-scroll mb-3" style="max-height: 300px;">
        <?php foreach ($commissioni as $commissione) { ?>
            <li class="list-group-item user-select-none">
                <div class="form-check">
                    <input 
                        type="checkbox"
                        class="form-check-input"  
                        name="roles[]"
                        id="role-<?= $commissione->Id ?>" 
                        value="<?= $commissione->Id ?>"
                        <?= ($user->Admin && $commissione->Nome === 'App e sito') || 
                            $staff->InCommissione(commissione: $commissione->Nome) ? 
                            'checked' : ''
                        ?>
                    >
                    <label for="role-<?= $commissione->Id ?>" class="form-check-label">
                        <?= htmlspecialchars(string: $commissione->Nome) ?>
                    </label>
                </div>
            </li>
        <?php } ?>
    </ul>

    <div class="form-check">
        <input 
            class="form-check-input"
            type="checkbox"
            value=""
            id="church_manager"
            name="church_manager"
            <?= $staff->Referente ? 'checked' : '' ?>
        >
        <label class="form-check-label" for="church_manager">
            Sarò referente parrocchiale
        </label>
    </div>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit" id="submit">
            Partecipa
        </button>
    </div>
</form>