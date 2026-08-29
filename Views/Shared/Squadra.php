<?php

use Amichiamoci\Models\Squadra;
use Amichiamoci\Models\User;

if (!isset($squadra) || !($squadra instanceof Squadra)) {
    throw new \Exception(message: 'var $squadra not valid');
}

if (!isset($user) || !($user instanceof User)) {
    throw new \Exception(message: 'var $user not valid');
}

if (!isset($anno) || !is_int(value: $anno)) {
    $anno = (int) date(format: 'Y');
}

$allow_edits = true;
// $allow_edits = $user->Admin;
?>

<div class="card" id="squadra-<?= $squadra->Id ?>">
    <div class="card-header user-select-none">
        <strong>
            <?= htmlspecialchars(string: $squadra->Nome) ?>
        </strong>
        <a 
            <?php if ($allow_edits) { ?>
                href="<?= $P ?>/teams/edit?id=<?= $squadra->Id ?>"
                class="link-underline link-underline-opacity-0 link-primary text-end"
                title="Modifica <?= htmlspecialchars(string: $squadra->Nome) ?>"
            <?php } else { ?>
                href="javascript:alert('Non più possibile!')"
                class="link-underline link-underline-opacity-0 link-secondary text-end"
                title="Non più possibile modificare la squadra"
            <?php } ?>
        >
            <i class="bi bi-pencil-square"></i>
        </a>
        <?php if ($user->Admin) { ?>
            <form action="<?= $P ?>/teams/delete" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= $squadra->Id ?>" required>
                <input type="hidden" name="year" value="<?= $anno ?>">
                <input type="hidden" name="church" value="<?= $squadra->Parrocchia->Id ?>">
                <button 
                    type="submit" 
                    class="btn btn-link link-underline link-underline-opacity-0 link-danger p-0" 
                    title="Elimina"
                    data-confirm="Sicuro di voler cancellare la squadra?"
                    data-confirm-btn="Sì, cancella"
                    data-cancel-btn="Annulla"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </form>
        <?php } ?>
    </div>
    <div class="card-body p-1">
        <?php if (is_string(value: $squadra->Referenti)) { ?>
            <h4 class="ms-1">
                Referenti
                <small>(staff esclusi)</small>
            </h4>
            <p class="ms-2">
                <?php foreach (
                    explode(
                        separator: ',', 
                        string: str_replace(
                            search: ["\n", "\r", "\t", ";", ], 
                            replace: ',', 
                            subject: $squadra->Referenti,
                        ),
                    ) as $referente) { 
                        if (trim(string: $referente) === '') continue;
                ?>

                    <?= htmlspecialchars(string: trim(string: $referente)) ?><br>
                <?php } ?>
            </p>
        <?php } ?>

        <h4 class="ms-1">
            Membri
        </h4>
        <ul class="list-group list-group-flush">
            <?php foreach ($squadra->MembriFull() as $id_anagrafica => $nome) { ?>
                <li class="list-group-item pt-0 border-0">
                    <a 
                        href="<?= $P ?>/staff/edit_anagrafica?id=<?= $id_anagrafica ?>"
                        class="text-reset link-underline link-underline-opacity-0"
                        title="Modifica i dati"
                    >
                        <?= htmlspecialchars(string: $nome) ?>
                    </a>
                </li>
            <?php } ?>
            <?php if (count(value: $squadra->IdIscritti) === 0) { ?>
                <li class="list-group-item user-select-none text-warning">
                    Nessun membro!
                </li>
            <?php } ?>
        </ul>
    </div>
</div>