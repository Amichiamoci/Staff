<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Parrocchia
{
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