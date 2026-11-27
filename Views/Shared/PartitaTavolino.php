<?php

use Amichiamoci\Models\Partita;

assert(isset($P) && is_string($P));
assert(isset($partita) && $partita instanceof Partita);
assert(isset($match_csrf_token) && is_string($match_csrf_token));

$choices = [
    0 => 'Nessun risultato a tavolino',
    $partita->Casa->Id => 'Vince ' . $partita->Casa->Nome,
    $partita->Ospiti->Id => 'Vince ' . $partita->Ospiti->Nome,
];

?>

<dialog
    id="match-forfeit-<?= $partita->Id ?>"
    class="match-forfeit bg-body text-body border rounded-3 p-3"
    aria-labelledby="match-forfeit-title-<?= $partita->Id ?>"
>
    <h1
        class="h5"
        id="match-forfeit-title-<?= $partita->Id ?>"
    >
        Risultato a tavolino
    </h1>
    <p class="text-break">
        <?= htmlspecialchars($partita->Casa->Nome) ?> – <?= htmlspecialchars($partita->Ospiti->Nome) ?>
    </p>
    <form
        method="post"
        action="<?= $P ?>/sport/match_results"
    >
        <input
            type="hidden"
            name="match_id"
            value="<?= $partita->Id ?>"
        >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($match_csrf_token) ?>"
        >
        <?php if (isset($match_forfeit_return)) { ?>
            <input
                type="hidden"
                name="return_calendar"
                value="<?= htmlspecialchars($match_forfeit_return) ?>"
            >
        <?php } ?>
        <div class="form-floating">
            <select
                class="form-select"
                id="match-forfeit-winner-<?= $partita->Id ?>"
                name="winner_team_id"
                required
            >
                <?php foreach ($choices as $id => $label) { ?>
                    <option
                        value="<?= $id ?>"
                        <?= ($partita->VincitoreATavolino ?? 0) === $id ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php } ?>
            </select>
            <label for="match-forfeit-winner-<?= $partita->Id ?>">
                Esito
            </label>
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
            <button
                type="button"
                class="btn btn-outline-secondary match-forfeit-cancel"
                title="Chiudi senza salvare il risultato a tavolino"
                autofocus
            >
                Annulla
            </button>
            <button
                type="submit"
                class="btn btn-primary"
                title="Salva o revoca il risultato a tavolino"
            >
                Salva esito
            </button>
        </div>
    </form>
</dialog>
