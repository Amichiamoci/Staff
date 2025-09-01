<?php

use Amichiamoci\Models\TipoTorneo;
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
        <?php if (!isset($hide_edit_icon) || $hide_edit_icon !== 'yes') { ?>
            <a 
                href="<?= $B ?>/sport/tournament?id=<?= $torneo->Id ?>"
                class="link-underline link-underline-opacity-0 link-primary text-end"
                title="Modifica il torneo">
                <i class="bi bi-pencil-square"></i>
            </a>
        <?php } ?>
        <?php if ($user->IsAdmin) { ?>
            <form 
                action="<?= $B ?>/sport/tournament_delete" 
                method="post"
                class="d-inline p-0"
            >
                <input type="hidden" name="id" value="<?= $torneo->Id ?>">
                <button
                    type="submit"
                    class="btn btn-link link-danger link-underline link-underline-opacity-0 p-0"
                    title="Elimina il torneo"
                    data-confirm="Sei sicuro? Eliminerai anche le iscrizioni delle squadre e le partite"
                    data-confirm-btn="Sì, elimina"
                    data-cancel-btn="Annulla"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </form>
        <?php } ?>
    </div>
    <div class="card-body">
        <dl class="row">
            <?php if (isset($show_sport) && $show_sport === 'yes') { ?>
                <dt class="col-sm-4 text-nowrap">
                    Sport
                </dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars(string: $torneo->Sport->Nome) ?>
                </dd>
            <?php } ?>

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
                        <button
                            type="button"
                            data-bs-toggle="modal" 
                            data-bs-target="#modal-calendario-<?= $torneo->Id ?>"
                            class="btn btn-outline-primary"
                        >
                            Genera
                        </button>
                        <div class="modal fade" id="modal-calendario-<?= $torneo->Id ?>" 
                            tabindex="-1" 
                            aria-hidden="true"
                            aria-labelledby="modal-calendario-<?= $torneo->Id ?>-label">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modal-calendario-<?= $torneo->Id ?>-label">
                                            Genera il calendario di <?= htmlspecialchars(string: $torneo->Nome) ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>
                                            Le date delle partite andranno inserite a mano successivamente.<br>
                                            Ora verranno solo generati gli accoppiamenti tra le squadre.
                                        </p>
                                        <form method="POST" action="<?= $B ?>/sport/tournament_generate_calendar">
                                            <input type="hidden" name="id" value="<?= $torneo->Id ?>" required>
                                            <div class="form-floating mb-2">
                                                <select 
                                                    name="field" 
                                                    id="field-<?= $torneo->Id ?>"
                                                    title="La squadra da aggiungere"
                                                    class="form-control"
                                                >
                                                    <option value="0">Specificherò il campo in seguito</option>
                                                </select>
                                                <label for="field-<?= $torneo->Id ?>">Campo</label>
                                                <div class="invalid-feedback">
                                                    Per favore, scegli un luogo tra i proposti
                                                </div>
                                                <div class="form-text user-select-none ms-2">
                                                    Specifica questa opzione solo se vuoi che tutte le partite siano giocate
                                                    nel medesimo luogo.
                                                    Il luogo di una partità potrà comunque essere cambiato in seguito.
                                                </div>
                                            </div>

                                            <?php if ($torneo->Tipo->Id === TipoTorneo::$RoundRobin) { ?>
                                                <div class="form-check">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        value="two-ways-<?= $torneo->Id ?>" 
                                                        id="two-ways-<?= $torneo->Id ?>" name="two_ways">
                                                    <label class="form-check-label" for="admin">
                                                        Andata e ritorno
                                                    </label>
                                                </div>
                                            <?php } ?>
                                            
                                            <button type="submit" class="btn btn-primary">
                                                Genera calendario
                                            </button>
                                        </form>
                                    </div>
                                    <script>
                                        $('#modal-calendario-<?= $torneo->Id ?>').on('show.bs.modal', async function (event) {
                                            const select = document.getElementById('field-<?= $torneo->Id ?>');
                                            if (select.children.length > 1) {
                                                return; // Already loaded
                                            }

                                            const resp = await fetch(`<?= $B ?>/sport/fields`, { method: 'GET'});
                                            if (!resp.ok) {
                                                return;
                                            }

                                            const list = await resp.json();
                                            for (const field of list) {
                                                const o = document.createElement('option');
                                                o.value = field.id;
                                                o.innerText = field.nome;
                                                select.appendChild(o);
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>

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
                                href="<?= $B ?>/teams/view?id=<?= $id ?>"
                                title="Vedi squadra">
                                <?= htmlspecialchars(string: $nome) ?>
                            </a>
                            <?php if ($user->IsAdmin || (isset($staff) && $staff->InCommissione(commissione: 'Tornei'))) { ?>
                                <form action="<?= $B ?>/sport/tournament_remove_team" method="post" class="d-inline p-0">
                                    <input type="hidden" name="tournament" value="<?= $torneo->Id ?>" required>
                                    <input type="hidden" name="team" value="<?= $id ?>" required>
                                    <button 
                                        type="submit"
                                        class="btn btn-link link-underline link-underline-opacity-0 link-danger p-0"
                                        title="Rimuovi squadra dal torneo"
                                        data-confirm="Sei sicuro di voler rimuovere la squadra '<?= htmlspecialchars(string: $nome) ?>' dal torneo?"
                                        data-confirm-btn="Sì, rimuovi"
                                        data-cancel-btn="No, lascia così">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </dd>
        </dl>
        <?php if ($user->IsAdmin || (isset($staff) && $staff->InCommissione(commissione: 'Tornei'))) { ?>
            <div class="row">
                <div class="col">
                    <button
                        type="button"
                        data-bs-toggle="modal" 
                        data-bs-target="#modal-torneo-<?= $torneo->Id ?>"
                        data-sport="<?= $torneo->Sport->Id ?>"
                        data-year="<?= $torneo->Edizione->Year ?>"
                        class="btn btn-outline-primary">
                        Aggiungi squadra
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
                                    <form method="POST" action="<?= $B ?>/sport/tournament_add_team">
                                        <input type="hidden" name="tournament" value="<?= $torneo->Id ?>">
                                        <div class="form-floating mb-2">
                                            <select 
                                                name="team" 
                                                id="add-team-<?= $torneo->Id ?>"
                                                title="La squadra da aggiungere"
                                                class="form-control"
                                                required>
                                                <option value="">Caricamento delle squadre...</option>
                                            </select>
                                            <label for="add-team-<?= $torneo->Id ?>">Squadra</label>
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

                                        const resp = await fetch(`<?= $B ?>/teams/sport?year=${year}&sport=${sport}`, { method: 'GET'});
                                        if (!resp.ok) {
                                            return;
                                        }
                                        const list = await resp.json();

                                        const select = document.getElementById('add-team-<?= $torneo->Id ?>');
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
                </div>
            </div>
        <?php } ?>

        <?php 
        if (
            count(value: $torneo->IdPartite) > 0 && 
            (!isset($hide_edit_icon) || $hide_edit_icon !== 'yes')
        ) { ?>
            <div class="row">
                <div class="col">
                    <a
                        href="<?= $B ?>/sport/tournament?id=<?= $torneo->Id ?>"
                        title="Aggiungi un risultato a una partita"
                        class="btn btn-outline-primary"
                    >
                        Inserisci risultato
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>