<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Squadra
{
    protected function teams_members(): ApiCall
    {
        return new ApiCall(
            query: 'SELECT * FROM `distinte`',
            row_parser: function (array $row): array {
                $semi_parsed =  [
                    'Id' => (int)$row['id'],
                    'TeamId' => (int)$row['squadra_id'],
                    'SubscriptionId' => (int)$row['iscrizione'],
                    'FullName' => $row['chi'],
                    'Sex' => is_string(value: $row['sesso']) ? $row['sesso'] : '?',
                    'Problems' => []
                ];
    
                // Other problems
                if (is_string(value: $row['tutore_problem']))
                {
                    $semi_parsed['Problems'][] = 'Tutore ' . $row['tutore_problem'];
                }
                if (is_string(value: $row['certificato_problem']))
                {
                    $semi_parsed['Problems'][] = $row['certificato_problem'];
                }
    
                // Document related problems
                if (is_string(value: $row['doc_problem']))
                {
                    $semi_parsed['Problems'][] = $row['doc_problem'];
                }
                //if (is_string($row['doc_code_problem']))
                //{
                //    $semi_parsed['Problems'][] = $row['doc_code_problem'];
                //}
                //if (is_string($row['scadenza_problem']))
                //{
                //    $semi_parsed['Problems'][] = $row['scadenza_problem'];
                //}
                return $semi_parsed;
            },
        );
    }

    protected function teams_info(): ApiCall
    {
        return new ApiCall(query: 'CALL `SquadreList(YEAR(CURRENT_DATE), NULL);',
            row_parser: function (array $row): array {
                $base = [
                    'Name' => $row['nome'],
                    'Id' => (int)$row['id_squadra'],
                    
                    'Church' => $row['parrocchia'],
                    'ChurchId' => (int)$row['id_parrocchia'],
    
                    'Sport' => $row['sport'],
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