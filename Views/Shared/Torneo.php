<?php

use Amichiamoci\Models\{TipoTorneo, Torneo, User};

assert(isset($user) && ($user instanceof User), 'var $user not valid');
assert(isset($P) && is_string($P), 'var $P not valid');
assert(isset($torneo) && ($torneo instanceof Torneo), 'var $torneo not valid');
assert(isset($can_manage_tournaments) && is_bool($can_manage_tournaments));
if ($can_manage_tournaments) {
    assert(isset($tournament_csrf_token) && is_string($tournament_csrf_token));
}

$sport_icon = $torneo->Sport->getIcon();
$show_details = ($hide_edit_icon ?? null) !== 'yes';
$details_label = $can_manage_tournaments ? 'Modifica il torneo' : 'Vedi il torneo';

?>

<article
    class="card mb-1"
    id="torneo-<?= $torneo->Id ?>"
>
    <header class="card-header d-flex align-items-start gap-2 user-select-none">
        <?php if ($sport_icon !== '') { ?>
            <span aria-hidden="true"><?= htmlspecialchars($sport_icon) ?></span>
        <?php } ?>
        <div class="flex-grow-1 text-break">
            <strong><?= htmlspecialchars($torneo->Nome) ?></strong>
            <div class="small text-body-secondary">
                <?= htmlspecialchars($torneo->Sport->Nome) ?>
            </div>
        </div>
        <?php if ($show_details || $user->Admin) { ?>
            <div class="d-flex align-items-center flex-shrink-0 gap-1">
                <?php if ($show_details) { ?>
                    <a  href="<?= $P ?>/sport/tournament?id=<?= $torneo->Id ?>"
                        class="btn btn-sm btn-link link-primary"
                        title="<?= $details_label ?>"
                        aria-label="<?= $details_label ?>"
                    >
                        <i  class="bi <?= $can_manage_tournaments ? 'bi-pencil-square' : 'bi-eye' ?>"
                            aria-hidden="true"
                        ></i>
                    </a>
                <?php } ?>
                <?php if ($user->Admin) { ?>
                    <form
                        action="<?= $P ?>/sport/tournament_delete"
                        method="post"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= $torneo->Id ?>"
                        >
                        <button
                            type="submit"
                            class="btn btn-sm btn-link link-danger"
                            title="Elimina il torneo"
                            aria-label="Elimina il torneo"
                            data-confirm="Sei sicuro? Eliminerai anche le iscrizioni delle squadre e le partite"
                            data-confirm-btn="Sì, elimina"
                            data-cancel-btn="Annulla"
                        >
                            <i  class="bi bi-trash"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </form>
                <?php } ?>
            </div>
        <?php } ?>
    </header>
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
                    <?php if ($can_manage_tournaments) { ?>
                        <button
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#modal-calendario-<?= $torneo->Id ?>"
                            class="btn btn-outline-primary"
                            title="Genera"
                        >
                            Genera
                        </button>
                        <div
                            class="modal fade"
                            id="modal-calendario-<?= $torneo->Id ?>"
                            tabindex="-1"
                            aria-hidden="true"
                            aria-labelledby="modal-calendario-<?= $torneo->Id ?>-label"
                        >
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"
                                            id="modal-calendario-<?= $torneo->Id ?>-label"
                                        >
                                            Genera il calendario di <?= htmlspecialchars(string: $torneo->Nome) ?>
                                        </h5>
                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                            aria-label="Chiudi"
                                            title="Chiudi"
                                        ></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>
                                            Le date delle partite andranno inserite a mano successivamente.<br>
                                            Ora verranno solo generati gli accoppiamenti tra le squadre.
                                        </p>
                                        <form
                                            method="POST"
                                            action="<?= $P ?>/sport/tournament_generate_calendar"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars($tournament_csrf_token) ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $torneo->Id ?>"
                                                required
                                            >
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
                                                        id="two-ways-<?= $torneo->Id ?>"
                                                        name="two_ways"
                                                    >
                                                    <label
                                                        class="form-check-label"
                                                        for="admin"
                                                    >
                                                        Andata e ritorno
                                                    </label>
                                                </div>
                                            <?php } ?>
                                            
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                title="Genera calendario"
                                            >
                                                Genera calendario
                                            </button>
                                        </form>
                                    </div>
                                    <script>
                                        $('#modal-calendario-<?= $torneo->Id ?>').on('show.bs.modal', async function (event) {
                                            const $select = $('#field-<?= $torneo->Id ?>');
                                            if ($select.children().length > 1) {
                                                return; // Already loaded
                                            }

                                            try {
                                                const list = await $.getJSON('<?= $P ?>/sport/fields');
                                                for (const field of list) {
                                                    $select.append($('<option>').val(field.id).text(field.nome));
                                                }
                                            } catch {
                                                // Opening the modal again retries the request.
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
                            <a  class="link-underline link-underline-opacity-0 text-reset"
                                href="<?= $P ?>/teams/details?id=<?= $id ?>"
                                title="Vedi squadra"
                            >
                                <?= htmlspecialchars(string: $nome) ?>
                            </a>
                            <?php if ($can_manage_tournaments) { ?>
                                <form
                                    action="<?= $P ?>/sport/tournament_remove_team"
                                    method="post"
                                    class="d-inline p-0"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($tournament_csrf_token) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="tournament"
                                        value="<?= $torneo->Id ?>"
                                        required
                                    >
                                    <input
                                        type="hidden"
                                        name="team"
                                        value="<?= $id ?>"
                                        required
                                    >
                                    <button
                                        type="submit"
                                        class="btn btn-link link-underline link-underline-opacity-0 link-danger p-0"
                                        title="Rimuovi squadra dal torneo"
                                        data-confirm="Sei sicuro di voler rimuovere la squadra '<?= htmlspecialchars(string: $nome) ?>' dal torneo?"
                                        data-confirm-btn="Sì, rimuovi"
                                        data-cancel-btn="No, lascia così"
                                    >
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </dd>
        </dl>
    </div>
    <?php if ($can_manage_tournaments || ($show_details && $torneo->IdPartite !== [])) { ?>
        <footer class="card-footer d-flex flex-wrap justify-content-end gap-2">
            <?php if ($can_manage_tournaments) { ?>
                <button
                    type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-torneo-<?= $torneo->Id ?>"
                    data-sport="<?= $torneo->Sport->Id ?>"
                    data-year="<?= $torneo->Edizione->Year ?>"
                    class="btn btn-sm btn-primary"
                    title="Aggiungi una squadra al torneo"
                >
                    <i  class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>
                    Aggiungi squadra
                </button>
            <?php } ?>
            <?php if ($show_details && $torneo->IdPartite !== []) { ?>
                <a  href="<?= $P ?>/sport/tournament?id=<?= $torneo->Id ?>"
                    title="<?= $user->Admin ? 'Aggiungi un risultato a una partita' : 'Vedi le partite del torneo' ?>"
                    class="btn btn-sm <?= $user->Admin ? 'btn-primary' : 'btn-outline-primary' ?>"
                >
                    <i  class="bi <?= $user->Admin ? 'bi-pencil-square' : 'bi-calendar-week' ?>"
                        aria-hidden="true"
                    ></i>
                    <?= $user->Admin ? 'Inserisci risultato' : 'Vedi partite' ?>
                </a>
            <?php } ?>
        </footer>
    <?php } ?>
    <?php if ($can_manage_tournaments) { ?>
        <div
            class="modal fade"
            id="modal-torneo-<?= $torneo->Id ?>"
            tabindex="-1"
            aria-hidden="true"
            aria-labelledby="modal-torneo-<?= $torneo->Id ?>-label"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"
                            id="modal-torneo-<?= $torneo->Id ?>-label"
                        >
                            Aggiungi una squadra <?= htmlspecialchars(string: $torneo->Nome) ?>
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Chiudi"
                            title="Chiudi"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <form
                            method="POST"
                            action="<?= $P ?>/sport/tournament_add_team"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($tournament_csrf_token) ?>"
                            >
                            <input
                                type="hidden"
                                name="tournament"
                                value="<?= $torneo->Id ?>"
                            >
                            <div class="form-floating mb-2">
                                <select
                                    name="team"
                                    id="add-team-<?= $torneo->Id ?>"
                                    title="La squadra da aggiungere"
                                    class="form-control"
                                    required
                                >
                                    <option value="">Caricamento delle squadre...</option>
                                </select>
                                <label for="add-team-<?= $torneo->Id ?>">Squadra</label>
                                <div class="invalid-feedback">
                                    Per favore, scegli una squadra tra le proposte
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                title="Aggiungi al torneo"
                            >
                                Aggiungi al torneo
                            </button>
                        </form>
                    </div>
                    <script>
                        $('#modal-torneo-<?= $torneo->Id ?>').on('show.bs.modal', async function (event) {
                            const button = $(event.relatedTarget);
                            try {
                                const list = await $.getJSON('<?= $P ?>/teams/sport', {
                                    year: button.data('year'),
                                    sport: button.data('sport'),
                                });
                                const $select = $('#add-team-<?= $torneo->Id ?>')
                                    .empty().append($('<option>').val('').text('Scegli una squadra'));
                                for (const team of list) {
                                    $select.append($('<option>').val(team.id).text(`${team.name} (${team.church})`));
                                }
                            } catch {
                                // Opening the modal again retries the request.
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    <?php } ?>
</article>
