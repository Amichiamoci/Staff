<?php

namespace Amichiamoci\Controllers;

use Richie314\SimpleMvc\Controllers\Attributes\RequireLogin;
use Richie314\SimpleMvc\Http\StatusCode;

use Amichiamoci\Models\{
    Campo,
    Edizione,
    Message,
    Partita,
    Punteggio,
    Settings,
    Sport,
    TipoTorneo,
    Torneo,
};
use Amichiamoci\Controllers\Attributes\RequireStaff;

class SportController
extends Controller
{
    private function CanManageTournaments(): bool
    {
        return $this->getUser()->Admin
            || ($this->Staff() !== null && Settings::Read($this->DB)['tournaments_enabled']);
    }

    private function TournamentManagementData(): array
    {
        $canManage = $this->CanManageTournaments();
        return [
            'can_manage_tournaments' => $canManage,
            'tournament_csrf_token' => $canManage ? $this->AccountToken() : null,
        ];
    }

    private function CheckTournamentManagement(?string $csrfToken): ?StatusCode
    {
        if (!$this->CanManageTournaments()) {
            return $this->NotAuthorized('La gestione dei tornei è riservata agli amministratori.');
        }
        if ($this->IsPost() && !$this->ValidAccountToken($csrfToken)) {
            return $this->BadRequest();
        }
        return null;
    }

    #[RequireLogin]
    public function fields(): StatusCode
    {
        return $this->Json(
            object: array_values(array: array_map(
                callback: function (Campo $c): array {
                    return [
                        'id' => $c->Id,
                        'nome' => $c->Nome,
                    ];
                },
                array: Campo::All(connection: $this->DB),
            ))
        );
    }

    #[RequireLogin]
    public function campi(): StatusCode
    {
        return $this->Render(
            view: 'Sport/campi',
            title: 'Campi',
            data: [
                'campi' => Campo::All($this->DB),
                'can_manage' => $this->CanManageTournaments(),
            ],
        );
    }

    #[RequireLogin]
    public function campo_edit(
        int|string|null $id = null,
        ?string $nome = null,
        ?string $indirizzo = null,
        ?string $latitudine = null,
        ?string $longitudine = null,
        ?string $csrf_token = null,
    ): StatusCode {
        if (($denied = $this->CheckTournamentManagement($csrf_token)) !== null) {
            return $denied;
        }

        if ($id !== null) {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                return $this->BadRequest();
            }
        }

        $campo = $id === null ? null : Campo::ById($this->DB, $id);
        if ($id !== null && $campo === null) {
            return $this->NotFound();
        }

        if ($this->IsPost()) {
            if (trim($nome ?? '') === '' || mb_strlen(trim($nome)) > 128
                || trim($indirizzo ?? '') === '' || mb_strlen(trim($indirizzo)) > 256
                || !isset($latitudine, $longitudine)
            ) {
                return $this->BadRequest();
            }

            $latitudine = trim($latitudine);
            $longitudine = trim($longitudine);
            $lat = $latitudine === '' ? null : filter_var($latitudine, FILTER_VALIDATE_FLOAT);
            $lon = $longitudine === '' ? null : filter_var($longitudine, FILTER_VALIDATE_FLOAT);
            if (($lat === null) !== ($lon === null) || $lat === false || $lon === false
                || ($lat !== null && (abs($lat) > 90 || abs($lon) > 180))
            ) {
                return $this->BadRequest();
            }

            Campo::Save($this->DB, $id, trim($nome), trim($indirizzo), $lat, $lon);
            $this->Message(Message::Success(content: 'Campo salvato.'));
            return $this->RedirectWithStatus($this->PathPrefix . '/sport/campi', StatusCode::SeeOther);
        }

        return $this->Render(
            view: 'Sport/campo_edit',
            title: $campo === null ? 'Nuovo campo' : 'Modifica campo',
            data: ['campo' => $campo, 'csrf_token' => $this->AccountToken()],
        );
    }

    #[RequireLogin]
    public function index(?int $year = null): StatusCode
    {
        if (empty($year))
            $year = (int)date(format: 'Y');

        $tournaments = Torneo::FromYear(connection: $this->DB, year: $year);
        return $this->Render(
            view: 'Sport/tournaments',
            title: 'Lista tornei',
            data: $this->TournamentManagementData() + [
                'tornei' => $tournaments,

                'anno' => $year,
                'edizioni' => Edizione::All(connection: $this->DB),
            ],
        );
    }

    #[RequireLogin]
    public function tournament(?int $id = null): StatusCode
    {
        if (empty($id))
            return $this->BadRequest();
        
        $torneo = Torneo::ById(connection: $this->DB, id: $id);
        if ($torneo === null)
            return $this->NotFound();

        $partite = Partita::Torneo(connection: $this->DB, torneo: $torneo);
        return $this->Render(
            view: 'Sport/tournament',
            title: $torneo->Nome,
            data: $this->TournamentManagementData() + [
                'torneo' => $torneo,
                'partite' => $partite,
                'edizioni' => Edizione::All(connection: $this->DB),
                'campi' => Campo::All(connection: $this->DB),
                'match_csrf_token' => $this->getUser()->Admin || $this->Staff() !== null ? $this->AccountToken() : null,
            ]
        );
    }

    #[RequireLogin]
    public function tournament_add_team(
        ?int $tournament = null,
        ?int $team = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if (($denied = $this->CheckTournamentManagement($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($tournament) || empty($team))
            return $this->BadRequest();

        if ($this->IsPost())
        {
            $res = Torneo::SubscribeTeam(connection: $this->DB, torneo: $tournament, squadra: $team);
            if ($res) {
                $this->Message(message: Message::Success(content: 'Squadra iscritta correttamente al torneo'));
            } else {
                $this->Message(message: Message::Error(content: 'Non è stato possibile iscrivere la squadra al torneo'));
            }
        }

        return $this->tournament(id: $tournament);
    }

    #[RequireLogin]
    public function tournament_remove_team(
        ?int $tournament = null,
        ?int $team = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if (($denied = $this->CheckTournamentManagement($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($tournament) || empty($team))
            return $this->BadRequest();

        if ($this->IsPost())
        {
            $res = Torneo::UnSubscribeTeam(connection: $this->DB, torneo: $tournament, squadra: $team);
            if ($res) {
                $this->Message(message: Message::Success(content: 'Squadra rimossa dal torneo'));
            } else {
                $this->Message(message: Message::Error(content: 'È avvenuto un errore'));
            }
        }

        return $this->tournament(id: $tournament);
    }

    #[RequireLogin]
    public function tournament_generate_calendar(
        ?int $id = null,
        bool $two_ways = false, 
        ?int $field = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if (($denied = $this->CheckTournamentManagement($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($id)) {
            return $this->BadRequest();
        }

        if (empty($field))
            $field = null;

        if ($this->IsPost())
        {
            $res = Torneo::GenerateCalendar(
                connection: $this->DB, 
                torneo: $id, 
                two_ways: $two_ways, 
                default_field: $field
            );

            if ($res) {
                $this->Message(message: Message::Success(content: 'Calendario generato'));
            } else {
                $this->Message(message: Message::Error(content: 'Non `e stato possibile generare il calendario'));
            }
        }
        
        return $this->tournament(id: $id);
    }

    #[RequireLogin]
    public function tournament_create(
        ?int $edition = null,
        ?int $sport = null,
        ?int $type = null,
        ?string $name = null,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if (($denied = $this->CheckTournamentManagement($csrf_token)) !== null) {
            return $denied;
        }

        if ($this->IsPost())
        {
            if (empty($edition) || 
                empty($sport) || 
                empty($type) || 
                empty($name)
            )
                return $this->BadRequest();

            $id = Torneo::Create(
                connection: $this->DB, 
                sport: $sport, 
                nome: $name, 
                tipo: $type, 
                edizione: $edition
            );
            
            if ($id !== null)
            {
                $this->Message(message: Message::Success(
                    content: 'Torneo creato correttamente'));
                return $this->tournament(id: $id);
            }
            $this->Message(message: Message::Error(
                content: 'Non è stato possibile creare il torneo'));
        }

        return $this->Render(
            view: 'Sport/tournament_create',
            title: 'Nuovo torneo',
            data: [
                'edizioni' => Edizione::All(connection: $this->DB),
                'sport' => Sport::All(connection: $this->DB),
                'tipi_torneo' => TipoTorneo::All(connection: $this->DB),
                'tournament_csrf_token' => $this->AccountToken(),
            ]
        );
    }

    #[RequireLogin(requireAdmin: true)]
    public function tournament_delete(
        ?int $id,
    ): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        $tournament = Torneo::ById(connection: $this->DB, id: $id);
        if ($tournament === null)
            return $this->NotFound();

        if (Torneo::Delete(connection: $this->DB, id: $id)) {
            $this->Message(message: Message::Success(
                content: 'Torneo "'. $tournament->Nome . '" correttamente cancellato'));
        } else {
            $this->Message(message: Message::Error(
                content: 'Non è stato possibile eliminare il torneo "' . $tournament->Nome . '"'));
        }

        return $this->index();
    }

    #[RequireStaff]
    public function matches(): StatusCode
    {
        $matches = Partita::Settimana(connection: $this->DB);
        $fields = Campo::All(connection: $this->DB);

        return $this->Render(
            view: 'Sport/matches',
            title: 'Partite degli ultimi 7 giorni',
            data: [
                'partite' => $matches,
                'campi' => $fields,
                'match_csrf_token' => $this->AccountToken(),
            ]
        );
    }
    

    private function CheckMatchWrite(?string $csrfToken): ?StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }
        if (!$this->ValidAccountToken($csrfToken)) {
            return $this->Json(['message' => 'Richiesta non valida.'], statusCode: StatusCode::BadRequest);
        }
        return null;
    }

    #[RequireStaff]
    public function match_results(
        int|string|null $match_id = null,
        ?string $winner_team_id = null,
        ?string $csrf_token = null,
        ?string $return_calendar = null,
    ): StatusCode {
        $match_id = filter_var($match_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($match_id === false) {
            return $this->BadRequest();
        }
        $partita = Partita::ById($this->DB, $match_id);
        if ($partita === null) {
            return $this->NotFound();
        }

        if ($this->IsPost()) {
            if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
                return $denied;
            }
            if (!in_array($winner_team_id, ['0', (string)$partita->Casa->Id, (string)$partita->Ospiti->Id], true)) {
                return $this->BadRequest();
            }
            Partita::ImpostaTavolino($this->DB, $match_id, $winner_team_id === '0' ? null : (int)$winner_team_id);
            $calendar = $return_calendar === 'matches' ? '/sport/matches' : '/sport/tournament?id=' . $partita->Torneo;
            return $this->RedirectWithStatus($this->PathPrefix . $calendar . '#match-' . $match_id, StatusCode::SeeOther);
        }

        return $this->Render(
            view: 'Sport/match_results',
            title: 'Segnapunti',
            data: [
                'partita' => $partita,
                'match_csrf_token' => $this->AccountToken(),
            ],
            cutomLayout: dirname(__DIR__) . '/Views/Shared/LayoutSegnapunti.php',
        );
    }

    #[RequireStaff]
    public function match_board_score(
        int|string|null $match_id = null,
        string $score_change = '',
        ?string $csrf_token = null,
    ): StatusCode {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        $match_id = filter_var($match_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        $change = json_decode($score_change, true, 4);
        $position = $change['position'] ?? null;
        $scoreId = $change['score_id'] ?? null;
        $previous = $change['previous'] ?? null;
        $score = $change['score'] ?? null;
        $validPair = static fn(array $pair, string $pattern): bool => array_is_list($pair) && count($pair) === 2
            && is_string($pair[0]) && is_string($pair[1])
            && preg_match($pattern, $pair[0]) && preg_match($pattern, $pair[1]);

        if ($match_id === false || !is_int($position) || $position < 0 || !is_array($score) || !$validPair($score, '/^[0-9]{1,8}$/D')
            || ($scoreId !== null && (!is_int($scoreId) || $scoreId < 1))
            || (($scoreId === null) !== ($previous === null))
            || ($previous !== null && (!is_array($previous) || !$validPair($previous, '/^[0-9]{0,8}$/D')))) {
            return $this->Json(['message' => 'Richiesta non valida.'], statusCode: StatusCode::BadRequest);
        }
        $saved = Punteggio::SalvaTabellone($this->DB, $match_id, $position, $scoreId, $previous, $score);
        if ($saved === null) {
            return $this->Json(['conflict' => true], statusCode: StatusCode::BadRequest);
        }
        return $this->Json(['result' => 'success', 'id' => $saved]);
    }

    #[RequireStaff]
    public function match_field(?int $match, string|int|null $field = null, ?string $csrf_token = null): StatusCode
    {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($match))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Partita non specificata',
            ], statusCode: StatusCode::BadGateway);

        if ($field === '') {
            $field = null;
        } elseif ($field !== null) {
            $field = filter_var($field, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
            if ($field === false || Campo::ById($this->DB, $field) === null) {
                return $this->Json(['message' => 'Campo non valido.'], statusCode: StatusCode::BadRequest);
            }
        }

        if (!Partita::ImpostaCampo(connection: $this->DB, partita: $match, campo: $field))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile aggiornare il campo della partita specificata',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }

    #[RequireStaff]
    public function match_time(?int $match, ?string $time = null, ?string $csrf_token = null): StatusCode
    {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($match))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Partita non specificata',
            ], statusCode: StatusCode::BadRequest);

        if ($time === '') {
            $time = null;
        } elseif ($time !== null && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $time)) {
            return $this->Json(['message' => 'Orario non valido.'], statusCode: StatusCode::BadRequest);
        }

        if (!Partita::ImpostaOrario(connection: $this->DB, partita: $match, orario: $time))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile aggiornare l\'orario della partita specificata',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }

    #[RequireStaff]
    public function match_date(?int $match, ?string $date = null, ?string $csrf_token = null): StatusCode
    {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($match)) 
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Partita non specificata',
            ], statusCode: StatusCode::BadRequest);

        if ($date === '') {
            $date = null;
        } elseif ($date !== null && (!preg_match('/^([1-9][0-9]{3})-([0-9]{2})-([0-9]{2})$/D', $date, $parts)
            || !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]))) {
            return $this->Json(['message' => 'Data non valida.'], statusCode: StatusCode::BadRequest);
        }

        if (!Partita::ImpostaData(connection: $this->DB, partita: $match, data: $date))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile aggiornare la data della partita specificata',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }

    #[RequireLogin(requireAdmin: true)]
    public function match_delete(?int $match): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (empty($match))
            return $this->BadRequest();

        $partita = Partita::ById(connection: $this->DB, id: $match);
        if ($partita === null)
            return $this->NotFound();

        if (Partita::Delete(connection: $this->DB, id: $match))
        {
            $this->Message(message: Message::Success(content: 'Partita eliminata correttamente'));
        } else {
            $this->Message(message: Message::Error(content: 'Non è stato possibile eliminare la partita'));
        }

        return $this->tournament(id: $partita->Torneo);
    }

    #[RequireStaff]
    public function match_add_score(?int $match, ?string $csrf_token = null): StatusCode
    {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($match))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Richiesta non valida',
            ], statusCode: StatusCode::BadRequest);

        $id = Partita::PunteggioVuoto(connection: $this->DB, match: $match);
        if (empty($id))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile aggiungere il nuovo punteggio al database',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
            'id' => $id,
        ]);
    }

    #[RequireStaff]
    public function match_remove_score(?int $score, ?string $csrf_token = null): StatusCode
    {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        if (empty($score))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Richiesta non valida',
            ], statusCode: StatusCode::BadRequest);

        $id = Punteggio::Delete(connection: $this->DB, id: $score);
        if (empty($id))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile rimuovere il risultato',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }

    #[RequireStaff]
    public function match_edit_score(
        ?int $score, 
        ?string $home, 
        ?string $guest,
        ?string $csrf_token = null,
    ): StatusCode
    {
        if (($denied = $this->CheckMatchWrite($csrf_token)) !== null) {
            return $denied;
        }

        $home = trim($home ?? '');
        $guest = trim($guest ?? '');
        if (empty($score) || !preg_match('/^[0-9]{1,8}$/D', $home) || !preg_match('/^[0-9]{1,8}$/D', $guest))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Richiesta non valida',
            ], statusCode: StatusCode::BadRequest);

        if (!Punteggio::Edit(connection: $this->DB, id: $score, casa: $home, ospiti: $guest))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile modificare il risultato',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }
}