<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Iscrizione;
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\Sport;
use Amichiamoci\Models\Squadra;
use Amichiamoci\Models\Message;

class TeamsController extends Controller
{
    public function index(?int $church = null, ?int $year = null): int
    {
        if (empty($year)) {
            $year = (int)date(format: "Y");
        }
        
        $staff = $this->RequireStaff();
        if (!isset($church) && isset($staff)) {
            $church = $staff->Parrocchia->Id;
        }
        if (empty($church)) {
            return $this->BadRequest();
        }

        return $this->Render(
            view: 'Teams/index',
            title: 'Tutte le squadre',
            data: [
                'teams' => Squadra::FromParrocchia(connection: $this->DB, parrocchia: $church, year: $year),
                'id_parrocchia' => $church,
                'anno' => $year,
                'parrocchie' => Parrocchia::All(connection: $this->DB),
                'edizioni' => Edizione::All(connection: $this->DB),
            ]
        );
    }

    public function new(
        ?int $id = null,
        ?string $name = null,
        ?int $church = null,
        ?int $sport = null,
        ?int $edition = null,
        ?string $coach = null,
        array $members = [],
    ): int {
        $staff = $this->RequireStaff();
        if (!isset($church) && isset($staff))
        {
            $church = $staff->Parrocchia->Id;
        }
        if (empty($church))
        {
            return $this->BadRequest();
        }

        if (!isset($edition))
        {
            $edition = Edizione::Current(connection: $this->DB);
        }
        if (!isset($edition))
        {
            return $this->NotFound();
        }
        if ($edition instanceof Edizione)
        {
            $edition = $edition->Id;
        }

        if (self::IsPost())
        {
            if (empty($name) || empty($sport))
            {
                return $this->BadRequest();
            }
            $res = Squadra::Create(
                connection: $this->DB, 
                nome: $name, 
                parrocchia: $church, 
                sport: $sport, 
                membri: implode(separator: ', ', array: $members), 
                edizione: $edition,
                coach: $coach,
                id: $id,
            );
            if ($res)
            {
                if (empty($id))
                {
                    $this->Message(message: Message::Success(content: 'Squadra creata correttamente'));
                } else {   
                    $this->Message(message: Message::Success(content: 'Squadra MODIFICATA correttamente'));
                }
                return $this->index(church: $church);
            }
            
            if (empty($id))
            {
                $this->Message(message: Message::Error(content: 'Non è stato possibile creare la squadra'));
            } else {   
                $this->Message(message: Message::Error(content: 'Non è stato possibile MODIFICARE la squadra'));
            }
        }

        return $this->Render(
            view: 'Teams/create',
            title: 'Crea squadra',
            data: [
                'id' => $id,
                'nome' => $name,
                'sport_squadra' => $sport,

                'parrocchia' => $church,
                'parrocchie' => Parrocchia::All(connection: $this->DB),

                'edizione' => $edition,
                'edizioni' => Edizione::All(connection: $this->DB),

                'sport' => Sport::All(connection: $this->DB),
                'iscritti' => Iscrizione::All(connection: $this->DB),

                'membri' => [],
                'coach' => $coach,
            ],
        );
    }

    public function edit(
        ?int $id = null,
    ): int
    {
        $this->RequireStaff();
        if (empty($id))
        {
            return $this->BadRequest();
        }

        $team = Squadra::ById(connection: $this->DB, id: $id);
        if ($team === null)
        {
            return $this->NotFound();
        }

        $edition = Edizione::Current(connection: $this->DB);
        if (!isset($edition))
        {
            return $this->NotFound();
        }

        return $this->Render(
            view: 'Teams/create',
            title: 'Modifica squadra',
            data: [
                'id' => $team->Id,
                'nome' => $team->Nome,
                'sport_squadra' => $team->Sport->Id,

                'parrocchia' => $team->Parrocchia->Id,
                'parrocchie' => Parrocchia::All(connection: $this->DB),

                'edizione' => $edition->Id,
                'edizioni' => Edizione::All(connection: $this->DB),

                'sport' => Sport::All(connection: $this->DB),
                'iscritti' => Iscrizione::All(connection: $this->DB),

                'membri' => array_keys(array: $team->MembriFull()),
                'coach' => $team->Referenti,
            ],
        );
    }

    public function delete(?int $id = null, ?int $church = null, ?int $year = null): int
    {
        $this->RequireLogin(require_admin: true);
        if (self::IsPost())
        {
            if (empty($id)) {
                return $this->BadRequest();
            }
            if (Squadra::Delete(connection: $this->DB, id: $id)) {
                $this->Message(message: Message::Success(content: 'Squadra correttamente eliminata'));
            } else {
                $this->Message(message: Message::Error(content: 'Impossibile eliminare la squadra'));
            }
        }
        return $this->index(church: $church, year: $year);
    }

    public function list(?int $church = null, ?int $year = null): int
    {
        if (empty($year)) {
            $year = (int)date(format: "Y");
        }
        
        $staff = $this->RequireStaff();
        if (!isset($church) && isset($staff)) {
            $church = $staff->Parrocchia->Id;
        }
        if (empty($church)) {
            return $this->BadRequest();
        }

        return $this->Json(
            object: array_values(array: array_map(
                callback: function (Squadra $s): array {
                    return [
                        'id' => $s->Id,
                        'name' => $s->Nome,
                        'sport' => $s->Sport->Nome,
                    ];
                }, 
                array: Squadra::FromParrocchia(
                    connection: $this->DB, 
                    parrocchia: $church, 
                    year: $year)
            ))
        );
    }

    public function sport(?int $sport, ?int $year): int
    {
        $this->RequireLogin();
        if (empty($sport) || empty($year)) {
            return $this->BadRequest();
        }

        return $this->Json(
            object: array_values(array: array_map(
                callback: function (Squadra $s): array {
                    return [
                        'id' => $s->Id,
                        'name' => $s->Nome,
                        'sport' => $s->Sport->Nome,
                        'church' => $s->Parrocchia->Nome,
                    ];
                }, 
                array: Squadra::All(
                    connection: $this->DB, 
                    year: $year, 
                    sport: $sport
                )
            ))
        );
    }
}