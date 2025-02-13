<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Iscrizione;
use Amichiamoci\Models\Parrocchia;
use Amichiamoci\Models\Sport;
use Amichiamoci\Models\Squadra;

class TeamsController extends Controller
{
    public function index(?int $church = null, ?int $year = null): int {
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
        ?string $name = null,
        ?int $church = null,
        ?int $sport = null,
        ?int $edition = null,
        array $members = [],
    ): int {
        $staff = $this->RequireStaff();
        if (!isset($church) && isset($staff)) {
            $church = $staff->Parrocchia->Id;
        }
        if (empty($church)) {
            return $this->BadRequest();
        }

        if (!isset($edition)) {
            $edition = Edizione::Current(connection: $this->DB);
        }
        if (!isset($edition)) {
            return $this->NotFound();
        }

        if (self::IsPost())
        {
            if (empty($name) || empty($sport)) {
                return $this->BadRequest();
            }
            $res = Squadra::Create(
                connection: $this->DB, 
                nome: $name, 
                parrocchia: $church, 
                sport: $sport, 
                membri: implode(separator: ' ', array: $members), 
                edizione: $edition
            );
        }

        return $this->Render(
            view: 'Teams/create',
            title: 'Crea squadra',
            data: [
                'parrocchia' => $church,
                'parrocchie' => Parrocchia::All(connection: $this->DB),

                'edizione' => $edition,
                'edizioni' => Edizione::All(connection: $this->DB),

                'sport' => Sport::All(connection: $this->DB),
                'iscritti' => Iscrizione::All(connection: $this->DB),
            ],
        );
    }
}