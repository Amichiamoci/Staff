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

    private function CheckTournamentManagement(mixed $csrfToken): ?StatusCode
    {
        if (!$this->CanManageTournaments()) {
            return $this->NotAuthorized('La gestione dei tornei è riservata agli amministratori.');
        }
        if ($this->IsPost() && (!is_string($csrfToken) || !$this->ValidAccountToken($csrfToken))) {
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
            ]
        );
    }

    #[RequireLogin]
    public function tournament_add_team(
        ?int $tournament = null,
        ?int $team = null,
        mixed $csrf_token = null,
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
        mixed $csrf_token = null,
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
        mixed $csrf_token = null,
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
        mixed $csrf_token = null,
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
            ]
        );
    }
    

    #[RequireLogin(requireAdmin: true)]
    public function match_field(?int $match, string|int|null $field = null): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (empty($match))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Partita non specificata',
            ], statusCode: StatusCode::BadGateway);

        if (is_string(value: $field))
            $field = ($field === '') ? null : (int)$field;

        if (!Partita::ImpostaCampo(connection: $this->DB, partita: $match, campo: $field))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile aggiornare il campo della partita specificata',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }

    #[RequireLogin(requireAdmin: true)]
    public function match_time(?int $match, ?string $time = null): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (empty($match))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Partita non specificata',
            ], statusCode: StatusCode::BadRequest);

        if ($time === '')
            $time = null;

        if (!Partita::ImpostaOrario(connection: $this->DB, partita: $match, orario: $time))
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Impossibile aggiornare l\'orario della partita specificata',
            ], statusCode: StatusCode::ServerError);

        return $this->Json(object: [
            'result' => 'success',
        ]);
    }

    #[RequireLogin(requireAdmin: true)]
    public function match_date(?int $match, ?string $date = null): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (empty($match)) 
            return $this->Json(object: [
                'result' => 'fail',
                'message' => 'Partita non specificata',
            ], statusCode: StatusCode::BadRequest);

        if ($date === '')
            $date = null;

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

    #[RequireLogin(requireAdmin: true)]
    public function match_add_score(?int $match): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
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

    #[RequireLogin(requireAdmin: true)]
    public function match_remove_score(?int $score): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
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

    #[RequireLogin(requireAdmin: true)]
    public function match_edit_score(
        ?int $score, 
        ?string $home, 
        ?string $guest,
    ): StatusCode
    {
        if (!$this->IsPost()) {
            $this->PostRequired();
        }

        if (empty($score) || !isset($home) || !isset($guest))
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