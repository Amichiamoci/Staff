<?php

use Amichiamoci\Models\Torneo;

$tornei_per_sport = array_reduce(
    array: $tornei,
    callback: function (array $carry, Torneo $t): array {
      $carry[$t->Sport->Id][] = $t;
      return $carry;
    },
    initial: [],
);
?>

<?php if ($user->IsAdmin) { ?>
    <div class="input-group mb-2">
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
            const select_a = document.getElementById('anno-selector');
            const f = () => {
                const a = Number(select_a.value);
                select_a.removeEventListener('change', f);
                window.location.replace(`?year=${a}`);
            };
            select_a.addEventListener('change', f);
        })();
    </script>
<?php } ?>

<h1>
    Tornei attivi (<?= count(value: $tornei) ?>)
</h1>

<?php foreach (array_keys($tornei_per_sport) as $sport_id) { ?>
    <h3 class="mt-1">
        <?= htmlspecialchars(string: $tornei_per_sport[$sport_id][0]->Sport->Nome) ?>
        (<?= count(value: $tornei_per_sport[$sport_id]) ?>)
    </h3>
    <div class="row m-0">
        <?php foreach ($tornei_per_sport[$sport_id] as $torneo) { ?>
            <div class="col col-xs-6 col-sm-4">
                <div class="card mb-1" id="torneo-<?= $torneo->Id ?>">
                    <div class="card-header user-select-none">
                        <strong>
                            <?= htmlspecialchars(string: $torneo->Nome) ?>
                        </strong>
                        <a 
                            href="/sport/tourney?id=<?= $torneo->Id ?>"
                            class="link-underline link-underline-opacity-0 link-primary text-end"
                            title="Modifica il torneo">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4 text-nowrap">
                                Tipologia
                            </dt>
                            <dd class="col-sm-8">
                                <?= htmlspecialchars(string: $torneo->Tipo->Nome) ?>
                            </dd>

                            <dt class="col-sm-4 text-nowrap">
                                Calendario
                            </dt>
                            <dd class="col-sm-8">
                                <?php if (count(value: $torneo->IdPartite) > 0) { ?>
                                    <?= count(value: $torneo->IdPartite) ?> partite previste
                                <?php } else { ?>
                                    <?php if ($user->IsAdmin || (isset($staff) && $staff->InCommissione(commissione: 'Tornei'))) { ?>
                                        <form action="/spost/plan" method="post">
                                            <input type="hidden" name="id" value="<?= $torneo->Id ?>">
                                            <button 
                                                type="submit"
                                                class="btn btn-outline-primary"
                                                title="Genera il calendario del torneo"
                                                data-confirm="Assicurati che risultino ADESSO iscritte tutte le squadre che vi devono prendere parte"
                                                data-confirm-btn="Genera"
                                                data-cancel-btn="Annulla"
                                            >
                                                Genera
                                            </button>
                                        </form>
                                    <?php } else { ?>
                                        <span class="text-warning">
                                            Non ancora creato
                                        </span>
                                    <?php } ?>
                                <?php } ?>
                            </dd>

                            <dt class="col-sm-4 text-nowrap">
                                Squadre (<?= count(value: $torneo->ListaSquadre) ?>)
                            </dt>
                            <dd class="col-sm-8">
                                <ul class="list-group-flush p-0 m-0">
                                    <?php foreach ($torneo->ListaSquadre as $id => $nome) { ?>
                                        <li class="list-group-item">
                                            <a 
                                                class="link-underline link-underline-opacity-0 text-reset"
                                                href="/teams/view?id=<?= $id ?>"
                                                title="Vedi squadra">
                                                <?= htmlspecialchars(string: $nome) ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>