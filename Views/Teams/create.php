<?php

use Amichiamoci\Models\Iscrizione;

$iscritti_per_parrocchia = array_reduce(
    array: $iscritti,
    callback: function (array $carry, Iscrizione $i): array {
      $carry[$i->Parrocchia->Id][] = $i;
      return $carry;
    },
    initial: [],
);
?>

<h1>
    <?= htmlspecialchars(string: $title) ?>
</h1>

<form method="post" action="<?= $B ?>/teams/new">
    <?php if (!empty($id)) { ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php } ?>

    <div class="form-floating mb-3">
        <input 
            required
            type="text" 
            maxlength="128"
            class="form-control" 
            id="name" 
            name="name"
            value="<?=is_string(value: $nome) ? htmlspecialchars(string: $nome) : '' ?>"
        >
        <label for="name">Nome</label>
        <div class="invalid-feedback">
            Per favore, immetti un nome per la squadra
        </div>
        <div class="form-text user-select-none ms-2">
            NON è necessario che includa sport o nome della parrocchia
        </div>
    </div>

    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="church" 
            name="church"
        >
            <?php foreach ($parrocchie as $p) { ?>
                <option value="<?= $p->Id ?>" <?= ($p->Id === $parrocchia) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $p->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="church">Parrocchia</label>
        <div class="invalid-feedback">
            Per favore, scegli una tra le parrocchie proposte
        </div>
        <div class="form-text user-select-none ms-2">
            Una squadra appartiene ad UNA SOLA PARROCCHIA.
            Nonostante ciò, è possibile inserire membri iscritti anche per altre parrocchie.
        </div>
    </div>

    <div class="form-floating mb-3">
        <select
            required
            class="form-control"
            id="sport" 
            name="sport"
        >
            <option value="">Scegli uno sport</option>
            <?php foreach ($sport as $s) { ?>
                <option 
                    value="<?= $s->Id ?>"
                    <?= $s->Id === $sport_squadra ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(string: $s->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <label for="sport">Sport</label>
        <div class="invalid-feedback">
            Per favore, scegli uno  sport
        </div>
    </div>

    <?php if ($user->IsAdmin) { ?>
        <div class="form-floating mb-3">
            <select
                required
                class="form-control"
                id="edition" 
                name="edition"
            >
                <?php foreach ($edizioni as $e) { ?>
                    <option value="<?= $e->Id ?>" <?= ($e->Id === $edizione) ? 'selected' : '' ?>>
                        <?= $e->Year ?>: <?= htmlspecialchars(string: $e->Motto) ?>
                    </option>
                <?php } ?>
            </select>
            <label for="edition">Edizione</label>
        </div>
    <?php } else { ?>
        <input type="hidden" name="edition" value="<?= $edizione ?>">
    <?php } ?>

    <div class="form-floating mb-3">
        <textarea
            class="form-control"
            id="coach"
            name="coach"
            style="resize: none;"
            rows="3"
            placeholder="Nome1 Cognome1, Nome2 Cognome2"
        ><?= (isset($coach) && is_string(value: $coach)) ? htmlspecialchars(string: $coach) : '' ?></textarea>
        <label for="coach">Referenti squadra (elencare separati da virgola o andando a capo)</label>
        <div class="invalid-feedback">
            Per favore, indica dei referenti
        </div>
    </div>

    <h3>
        Membri
    </h3>
    <ul class="list-group overflow-y-scroll mb-3" style="max-height: 300px;">
        <?php foreach (array_keys(array: $iscritti_per_parrocchia) as $id_parrocchia) { ?>
            <li class="list-group-item user-select-none" id="church-<?= $id_parrocchia ?>">
                <strong>
                    <?= htmlspecialchars(
                        string: $iscritti_per_parrocchia[$id_parrocchia][0]->Parrocchia->Nome) 
                    ?>
                </strong>
            </li>
            <?php foreach ($iscritti_per_parrocchia[$id_parrocchia] as $iscritto) { ?>
                <li class="list-group-item user-select-none">
                    <div class="form-check">
                        <input 
                            type="checkbox"
                            class="form-check-input"  
                            name="members[]" 
                            id="member-<?= $iscritto->Id ?>" 
                            value="<?= $iscritto->Id ?>"
                            <?= 
                                in_array(needle: $iscritto->Id, haystack: $membri) ?
                                'checked' : '' 
                            ?>
                        >
                        <label for="member-<?= $iscritto->Id ?>" class="form-check-label">
                            <?= htmlspecialchars(string: $iscritto->Nome) ?>
                        </label>
                    </div>
                </li>
            <?php } ?>
        <?php } ?>
    </ul>

    <div class="col-12 mt-2">
        <button class="btn btn-primary" type="submit" disabled>
            Crea
        </button>
    </div>
</form>

<script>
    (() => {
        document.getElementById('church-<?= $parrocchia ?>').scrollIntoView();
    })()
</script>