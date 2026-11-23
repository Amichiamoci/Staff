<?php

namespace Amichiamoci\Models\Api\Traits;

use Amichiamoci\Models\Api\Call as ApiCall;

trait Parrocchia
{

    /** Prepares a lookup of one parish by its database ID. */
    protected function church(int $Id): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `parrocchie` WHERE `id` = $Id",
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
    
                    'Address' => is_string(value: $r['indirizzo']) && strlen(string: $r['indirizzo']) > 0 ? $r['indirizzo'] : null,
                    'Website' => is_string(value: $r['website']) && strlen(string: $r['website']) > 0 ? $r['website'] : null,
                ];
            }
        );
    }

    /** Prepares the list of all parishes exposed by the API. */
    protected function churches(): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `parrocchie`",
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
    
                    'Address' => is_string(value: $r['indirizzo']) && strlen(string: $r['indirizzo']) > 0 ? $r['indirizzo'] : null,
                    'Website' => is_string(value: $r['website']) && strlen(string: $r['website']) > 0 ? $r['website'] : null,
                ];
            }
        );
    }
    

    /** Prepares the parish standings with their API field names. */
    protected function leaderboard(): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `classifica_parrocchie`",
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
                    'Address' => is_string(value: $r['indirizzo']) && strlen(string: $r['indirizzo']) > 0 ? $r['indirizzo'] : null,
                    'Website' => is_string(value: $r['website']) && strlen(string: $r['website']) > 0 ? $r['website'] : null,
               
                    'Score' => (int)$r['punteggio'],
                    'Position' => (int)$r['posizione'],
                ];
            }
        );
    }
}