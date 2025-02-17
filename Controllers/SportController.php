<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Edizione;
use Amichiamoci\Models\Sport;
use Amichiamoci\Models\Torneo;

class SportController extends Controller
{
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

        $partite = [];
        return $this->Render(
            view: 'Sport/tournament',
            title: $torneo->Nome,
            data: [
                'torneo' => $torneo,
                'partite' => $partite,
                'edizioni' => Edizione::All(connection: $this->DB),
            ]
        );
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
        $this->RequireLogin();
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