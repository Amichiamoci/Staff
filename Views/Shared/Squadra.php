<?php

use Amichiamoci\Models\{Squadra, User};

if (!isset($anno) || !is_int(value: $anno)) {
    $anno = (int) date(format: 'Y');
}

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($squadra) && ($squadra instanceof Squadra), 'var $squadra not valid');
assert(isset($P) && is_string($P), 'var $P not valid');

$sport_icon = $squadra->Sport->getIcon();
?>

<article
    class="card"
    id="squadra-<?= $squadra->Id ?>"
>
    <header class="card-header d-flex align-items-start gap-2 user-select-none">
        <?php if ($sport_icon !== '') { ?>
            <span aria-hidden="true"><?= htmlspecialchars($sport_icon) ?></span>
        <?php } ?>
        <div class="flex-grow-1 text-break">
            <a  href="<?= $P ?>/teams/details?id=<?= $squadra->Id ?>"
                class="fw-semibold text-reset link-underline link-underline-opacity-0"
                title="Vedi la distinta di <?= htmlspecialchars($squadra->Nome) ?>"
            >
                <?= htmlspecialchars($squadra->Nome) ?>
                <i  class="bi bi-clipboard2-pulse"
                    aria-hidden="true"
                ></i>
            </a>
            <div class="small text-body-secondary">
                <?= htmlspecialchars($squadra->Sport->Nome) ?>
            </div>
        </div>
        <?php if ($user->Admin) { ?>
            <div class="d-flex align-items-center flex-shrink-0 gap-1">
                <a  href="<?= $P ?>/teams/edit?id=<?= $squadra->Id ?>"
                    class="btn btn-sm btn-link link-primary"
                    title="Modifica <?= htmlspecialchars($squadra->Nome) ?>"
                    aria-label="Modifica <?= htmlspecialchars($squadra->Nome) ?>"
                >
                    <i  class="bi bi-pencil-square"
                        aria-hidden="true"
                    ></i>
                </a>
                <form
                    action="<?= $P ?>/teams/delete"
                    method="post"
                >
                    <input
                        type="hidden"
                        name="id"
                        value="<?= $squadra->Id ?>"
                    >
                    <input
                        type="hidden"
                        name="year"
                        value="<?= $anno ?>"
                    >
                    <input
                        type="hidden"
                        name="church"
                        value="<?= $squadra->Parrocchia->Id ?>"
                    >
                    <button
                        type="submit"
                        class="btn btn-sm btn-link link-danger"
                        title="Elimina <?= htmlspecialchars($squadra->Nome) ?>"
                        aria-label="Elimina <?= htmlspecialchars($squadra->Nome) ?>"
                        data-confirm="Sicuro di voler cancellare la squadra?"
                        data-confirm-btn="Sì, cancella"
                        data-cancel-btn="Annulla"
                    >
                        <i  class="bi bi-trash"
                            aria-hidden="true"
                        ></i>
                    </button>
                </form>
            </div>
        <?php } ?>
    </header>
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
                    <a  href="<?= $P ?>/staff/edit_anagrafica?id=<?= $id_anagrafica ?>"
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
</article>
