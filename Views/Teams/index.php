<?php

use Amichiamoci\Models\Squadra;

$squadre_per_sport = array_reduce(
    array: $teams,
    callback: function (array $carry, Squadra $s): array {
      $carry[$s->Sport->Id][] = $s;
      return $carry;
    },
    initial: [],
);
?>
<?php if ($user->IsAdmin || $staff !== null) { ?>
    <div class="input-group mb-2">
        <select id="parrocchia-selector" class="form-control">
            <?php foreach ($parrocchie as $parrocchia) { ?>
                <option value="<?= $parrocchia->Id ?>" 
                    <?= ($parrocchia->Id === $id_parrocchia) ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars(string: $parrocchia->Nome) ?>
                </option>
            <?php } ?>
        </select>
        <select id="anno-selector" class="form-control">
            <?php foreach ($edizioni as $e) { ?>
                <option value="<?= $e->Year ?>" <?= ($e->Year === $anno) ? 'selected' : '' ?>>
                    <?= $e->Year ?>
                </option>
            <?php } ?>
        </select>
    </div>
    <script>
        (() => {
            const select_p = document.getElementById('parrocchia-selector');
            const select_a = document.getElementById('anno-selector');
            const f = () => {
                const p = Number(select_p.value);
                const a = Number(select_a.value);
                select_p.removeEventListener('change', f);
                select_a.removeEventListener('change', f);
                window.location.replace(`?church=${p}&year=${a}`);
            };
            select_p.addEventListener('change', f);
            select_a.addEventListener('change', f);
        })();
    </script>
<?php } ?>

<h1>
    Lista squadre (<?=count(value: $teams)?>)
</h1>
<p>
    Le squadre sono raggruppate per lo sport per il quale sono registrate
</p>
<?php foreach (array_keys(array: $squadre_per_sport) as $sport_id) { ?>
    <h3 class="mt-1">
        <?= htmlspecialchars(string: $squadre_per_sport[$sport_id][0]->Sport->Nome) ?>
        (<?= count(value: $squadre_per_sport[$sport_id]) ?>)
    </h3>
    <div class="row">
        <?php foreach ($squadre_per_sport[$sport_id] as $squadra) { ?>
            <div class="col col-xs-6 col-sm-4">
                <div class="card" id="squadra-<?= $squadra->Id ?>">
                    <div class="card-header user-select-none">
                        <strong>
                            <?= htmlspecialchars(string: $squadra->Nome) ?>
                        </strong>
                        <a 
                            href="<?= $B ?>/teams/edit?id=<?= $squadra->Id ?>"
                            class="link-underline link-underline-opacity-0 link-primary text-end"
                            <?php /* 
                            href="javascript:alert('Non più possibile!')"
                            class="link-underline link-underline-opacity-0 link-secondary text-end"
                            */ ?>
                            title="Modifica <?= htmlspecialchars(string: $squadra->Nome) ?>"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <?php if ($user->IsAdmin) { ?>
                            <form action="<?= $B ?>/teams/delete" method="post" class="d-inline">
                                <input type="hidden" name="id" value="<?= $squadra->Id ?>" required>
                                <input type="hidden" name="year" value="<?= $anno ?>">
                                <input type="hidden" name="church" value="<?= $id_parrocchia ?>">
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
                                        href="<?= $B ?>/staff/edit_anagrafica?id=<?= $id_anagrafica ?>"
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
            </div>
        <?php } ?>
    </div>
<?php } ?>