<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Parrocchia;
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
}