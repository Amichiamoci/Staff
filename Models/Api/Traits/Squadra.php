<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Squadra
{
    protected function teams_info(): ApiCall
    {
        return new ApiCall(query: 'CALL `SquadreList`(YEAR(CURRENT_DATE), NULL);',
            row_parser: function (array $row): array {
                $base = [
                    'Name' => $row['nome'],
                    'Id' => (int)$row['id_squadra'],
                    
                    'Church' => $row['parrocchia'],
                    'ChurchId' => (int)$row['id_parrocchia'],
    
                    'Sport' => $row['nome_sport'],
                    'SportId' => (int)$row['id_sport'],
    
                    'MemberCount' => (int)($row['totale_membri'] ?? 0),
                ];

                if (array_key_exists(key: 'referenti', array: $row) && is_string(value: $row['referenti']))
                {
                    $base['Coach'] = $row['referenti'];
                }

                return $base;
            },
            is_procedure: true,
        );
    }
}