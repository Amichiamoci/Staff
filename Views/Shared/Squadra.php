<?php

use Amichiamoci\Models\Squadra;
use Amichiamoci\Models\User;

if (!isset($anno) || !is_int(value: $anno)) {
    $anno = (int) date(format: 'Y');
}

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($squadra) && ($squadra instanceof Squadra), 'var $squadra not valid');
assert(isset($P) && is_string($P), 'var $P not valid');

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
        <?php 
        $lista_espansa_referenti = [];
        if (is_string(value: $squadra->Referenti)) {
            $lista_espansa_referenti = explode(
                separator: ',', 
                string: str_replace(
                    search: ["\n", "\r", "\t", ";", ], 
                    replace: ',', 
                    subject: $squadra->Referenti,
                ),
            );
            $lista_espansa_referenti = array_filter($lista_espansa_referenti, 
                                                    callback: function ($referente) {
                return trim(string: $referente) !== '';
            });
        }
        ?>
        
        <h4 class="ms-1">
            Referenti
            <?php if (count(value: $lista_espansa_referenti) > 0) { ?>
                <small>(extra-staff)</small>
            <?php } ?>
        </h4>
        
        <p class="ms-2">
            <?php if (count(value: $lista_espansa_referenti) === 0) { ?>
                Nessun referente extra-staff indicato
            <?php } else { ?>
                <?php foreach ($lista_espansa_referenti as $referente) { ?>
                    <?= htmlspecialchars(string: trim(string: $referente)) ?><br>
                <?php } ?>
            <?php } ?>
        </p>

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