<?php
use Amichiamoci\Models\Campo;

assert(isset($P) && is_string($P));
assert(isset($csrf_token) && is_string($csrf_token));
assert(!isset($campo) || ($campo instanceof Campo));
?>

<h1><?= !isset($campo) ? 'Nuovo campo' : 'Modifica campo' ?></h1>

<form
    id="campo-form"
    action="<?= $P ?>/sport/campo_edit"
    method="post"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($csrf_token) ?>"
    >
    <?php if (isset($campo)) { ?>
        <input
            type="hidden"
            name="id"
            value="<?= $campo->Id ?>"
        >
    <?php } ?>
    <div class="form-floating mb-3">
        <input
            type="text"
            id="campo-nome"
            name="nome"
            class="form-control"
            placeholder="Nome"
            value="<?= htmlspecialchars($campo->Nome ?? '') ?>"
            maxlength="128"
            required
        >
        <label for="campo-nome">Nome</label>
    </div>
    <div class="form-floating mb-3">
        <input
            type="text"
            id="campo-indirizzo"
            name="indirizzo"
            class="form-control"
            placeholder="Indirizzo"
            value="<?= htmlspecialchars($campo->Indirizzo ?? '') ?>"
            autocomplete="street-address"
            maxlength="256"
            required
        >
        <label for="campo-indirizzo">Indirizzo</label>
    </div>
    <fieldset>
        <legend>Posizione</legend>
        <p id="campo-coordinate-help">
            Inserisci entrambe le coordinate oppure lascia entrambi i campi vuoti.
        </p>
        <div class="row g-3 mb-3">
            <?php foreach ([
                'latitudine' => ['Latitudine', 90, $campo->Latitudine ?? null],
                'longitudine' => ['Longitudine', 180, $campo->Longitudine ?? null],
            ] as $name => [$label, $limit, $value]) { ?>
                <div class="col-sm-6">
                    <div class="form-floating">
                        <input
                            type="number"
                            id="campo-<?= $name ?>"
                            name="<?= $name ?>"
                            class="form-control"
                            placeholder="<?= $label ?>"
                            value="<?= htmlspecialchars((string)$value) ?>"
                            min="<?= -$limit ?>"
                            max="<?= $limit ?>"
                            step="any"
                            aria-describedby="campo-coordinate-help"
                        >
                        <label for="campo-<?= $name ?>"><?= $label ?></label>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php require __DIR__ . '/campi-map.php'; ?>
    </fieldset>
    <button
        type="submit"
        class="btn btn-primary"
        title="Salva il campo sportivo"
    >
        Salva
    </button>
    <a  href="<?= $P ?>/sport/campi"
        class="btn btn-outline-secondary"
        title="Torna all’elenco dei campi"
    >
        Annulla
    </a>
</form>
