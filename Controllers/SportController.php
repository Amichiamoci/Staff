<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Campo;
use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Message;
use Amichiamoci\Models\Partita;
use Amichiamoci\Models\Sport;
use Amichiamoci\Models\TipoTorneo;
use Amichiamoci\Models\Torneo;

class SportController extends Controller
{
    public function fields(): int {
        $this->RequireLogin();

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

    public function index(?int $year = null): int {
        $this->RequireLogin();
        
        if (empty($year)) {
            $year = (int)date(format: 'Y');
        }

        $tournaments = Torneo::FromYear(connection: $this->DB, year: $year);
        return $this->Render(
            view: 'Sport/tournaments',
            title: 'Lista tornei',
            data: [
                'tornei' => $tournaments,

                'anno' => $year,
                'edizioni' => Edizione::All(connection: $this->DB),
            ],
        );
    }

    public function tournament(?int $id = null): int {
        $this->RequireLogin();
        if (!isset($id)) {
            return $this->BadRequest();
        }
        
        $torneo = Torneo::ById(connection: $this->DB, id: $id);
        if (!isset($torneo)) {
            return $this->NotFound();
        }

        $partite = Partita::Torneo(connection: $this->DB, torneo: $torneo);
        return $this->Render(
            view: 'Sport/tournament',
            title: $torneo->Nome,
            data: [
                'torneo' => $torneo,
                'partite' => $partite,
                'edizioni' => Edizione::All(connection: $this->DB),
                'campi' => Campo::All(connection: $this->DB),
            ]
        );
    }

    public function tournament_add_team(?int $tournament, ?int $team): int {
        $this->RequireStaff();
        if (empty($tournament) || empty($team)) {
            return $this->BadRequest();
        }
        if (self::IsPost())
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

    public function tournament_remove_team(?int $tournament, ?int $team): int {
        $this->RequireStaff();
        if (empty($tournament) || empty($team)) {
            return $this->BadRequest();
        }
        if (self::IsPost())
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

    public function tournament_generate_calendar(?int $id, bool $two_ways = false, ?int $field = null): int
    {
        $this->RequireStaff();
        if (empty($field)) $field = null;
        if (self::IsPost())
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

    public function tournament_create(
        ?int $edition = null,
        ?int $sport = null,
        ?int $type = null,
        ?string $name = null,
    ): int {
        $this->RequireStaff();
        if (self::IsPost())
        {
            if (empty($edition) || empty($sport) || empty($type) || empty($name)) {
                return $this->BadRequest();
            }
            $id = Torneo::Create(
                connection: $this->DB, 
                sport: $sport, 
                nome: $name, 
                tipo: $type, 
                edizione: $edition
            );
            if (isset($id)) {
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
            ]
        );
    }

    public function tournament_delete(
        ?int $id,
    ): int {
        $this->RequireLogin(require_admin: true);

        $tournament = Torneo::ById(connection: $this->DB, id: $id);
        if (!isset($tournament)) {
            return $this->NotFound();
        }

        if (Torneo::Delete(connection: $this->DB, id: $id)) {
            $this->Message(message: Message::Success(
                content: 'Torneo "'. $tournament->Nome . '" correttamente cancellato'));
        } else {
            $this->Message(message: Message::Error(
                content: 'Non è stato possibile eliminare il torneo "' . $tournament->Nome . '"'));
        }

        return $this->index();
    }

    /*
    public function matches(?string $date): int {

    }
    */

    /**
     * Generate (and link) the necessary instances of Torneo to fill up an entire sport
     * @param mixed $id the id of the sport
     */
    public function plan(?int $id = null, ?int $year = null): int
    {
        $this->RequireStaff();
        if (empty($id)) {
            return $this->BadRequest();
        }
        if (empty($year)) {
            $year = (int)date(format: 'Y');
        }

        $sport = Sport::ById(connection: $this->DB, id: $id);
        if (!isset($sport)) {
            return $this->NotFound();
        }
        $edition = Edizione::FromYear(connection: $this->DB, year: $year);
        if (!isset($edition)) {
            return $this->NotFound();
        }

        if (self::IsPost())
        {
            // TODO: check for active tournaments for this sport inside the edition

            // TODO: generate the tournaments
        }

        return $this->Render(
            view: 'Sport/plan',
            title: 'Pianifica tornei',
            data: [

            ],
        );
    }
}