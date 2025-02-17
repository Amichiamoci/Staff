<?php

use Amichiamoci\Models\Torneo;

if (!isset($torneo) || !($torneo instanceof Torneo)) {
    throw new \Exception(message: 'var $torneo not valid');
}
?>

<div class="card mb-1" id="torneo-<?= $torneo->Id ?>">
    <div class="card-header user-select-none">
        <strong>
            <?= htmlspecialchars(string: $torneo->Nome) ?>
        </strong>
        <a 
            href="/sport/tournament?id=<?= $torneo->Id ?>"
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

            
            <dt class="col-sm-4">
                Aggiungi squadre
            </dt>
            <dd class="col-sm-8">
                <button
                    type="button"
                    data-bs-toggle="modal" 
                    data-bs-target="#modal-torneo-<?= $torneo->Id ?>"
                    data-sport="<?= $torneo->Sport->Id ?>"
                    data-year="<?= $torneo->Edizione->Year ?>"
                    class="btn btn-outline-primary">
                    Aggiungi
                </button>
                <div class="modal fade" id="modal-torneo-<?= $torneo->Id ?>" 
                    tabindex="-1" 
                    aria-hidden="true"
                    aria-labelledby="modal-torneo-<?= $torneo->Id ?>-label">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modal-torneo-<?= $torneo->Id ?>-label">
                                    Aggiungi una squadra <?= htmlspecialchars(string: $torneo->Nome) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="/sport/tournament_add_team">
                                    <input type="hidden" name="tournament" value="<?= $torneo->Id ?>">
                                    <div class="form-floating mb-2">
                                        <select 
                                            name="team" 
                                            id="<?= $torneo->Id ?>-team"
                                            title="La squadra da aggiungere"
                                            class="form-control"
                                            required>
                                            <option value="">Caricamento delle squadre...</option>
                                        </select>
                                        <label for="<?= $torneo->Id ?>-team">Squadra</label>
                                        <div class="invalid-feedback">
                                            Per favore, scegli una squadra tra le proposte
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        Aggiungi al torneo
                                    </button>
                                </form>
                            </div>
                            <script>
                                $('#modal-torneo-<?= $torneo->Id ?>').on('show.bs.modal', async function (event) {
                                    const button = $(event.relatedTarget);
                                    const year = button.data('year'), sport = button.data('sport');
                                    const modal = $(this);

                                    const resp = await fetch(`/teams/sport?year=${year}&sport=${sport}`, { method: 'GET'});
                                    if (!resp.ok) {
                                        return;
                                    }
                                    const list = await resp.json();

                                    const select = document.getElementById('<?= $torneo->Id ?>-team');
                                    select.innerHTML = '<option value="">Scegli una squadra</option>';
                                    for (const team of list) {
                                        const o = document.createElement('option');
                                        o.value = team.id;
                                        o.innerText = `${team.name} (${team.church})`;
                                        select.appendChild(o);
                                    }
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </dd>
        </dl>
    </div>
</div>