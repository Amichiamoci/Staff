<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Torneo
{
    protected function tournament(int $Id): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `tornei_attivi` WHERE `id` = $Id",
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
    
                    'Sport' => $r['sport'],
                    'SportId' => (int)$r['codice_sport'],
    
                    'Type' => $r['tipo'],
    
                    'Teams' => array_map(callback: function($s): string {
                        return trim(string: $s);
                    }, array: explode(separator: ',', string: $r['squadre'])),
                ];
            }
        );
    }

    protected function tournament_matches(int $Id): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `partite_completo` WHERE `torneo` = $Id",
            row_parser: function($r): array {
                $arr = [
                    'Id' => (int)$r['id'],
    
                    'TournamentName' => $r['nome_torneo'],
                    'TournamentId' => (int)$r['torneo'],
    
                    'SportName' => $r['sport'],
                    'SportId' => (int)$r['codice_sport'],
    
                    'Date' => $r['data'],
                    'Time' => $r['orario'],
    
                    'HomeTeam' => [
                        'Name' => $r['squadra_casa'],
                        'Id' => (int)$r['squadra_casa_id'],
                        
                        'Sport' => $r['sport'],
                        'SportId' => (int)$r['codice_sport'],
    
                        'Church' => $r['nome_parrocchia_casa'],
                        'ChurchId' => (int)$r['id_parrocchia_casa'],
                    ],
                    'GuestTeam' => [
                        'Name' => $r['squadra_ospite'],
                        'Id' => (int)$r['squadra_ospite_id'],
                        
                        'Sport' => $r['sport'],
                        'SportId' => (int)$r['codice_sport'],
    
                        'Church' => $r['nome_parrocchia_ospite'],
                        'ChurchId' => (int)$r['id_parrocchia_ospiti'],
                    ],
    
                    'HomeScore' => null,
                    'GuestScore' => null,
                    'Scores' => [
                        'Id' => is_string(value: $r['id_punteggi']) ? 
                            array_map(
                                callback: function (string $s): int { return (int)$s; },
                                array: explode(separator: '|', string: $r['id_punteggi'])
                            ) : [],
                        'Home' => is_string(value: $r['punteggi_casa']) ? 
                            explode(separator: '|', string: $r['punteggi_casa']) : [],
                        'Guest' => is_string(value: $r['punteggi_ospiti']) ?
                            explode(separator: '|', string: $r['punteggi_ospiti']) : [],
                    ],
                ];
    
                if (is_string(value: $r['nome_campo']) && isset($r['id_campo']))
                {
                    $arr['Field'] = [
                        'Name' => $r['nome_campo'],
                        'Id' => (int)$r['id_campo'],
                        'Address' => $r['indirizzo_campo'],
                        'Latitude' => isset($r['latitudine_campo']) ? (float)$r['latitudine_campo'] : null,
                        'Longitude' => isset($r['longitudine_campo']) ? (float)$r['longitudine_campo'] : null,
                    ];
                }
    
                return $arr;
            }
        );
    }

    protected function tournament_leaderboard(int $Id): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `classifica_torneo` WHERE `id_torneo` = $Id ORDER BY CAST(`punteggio` AS UNSIGNED) DESC",
            row_parser: function(array $r): array {
                return [
                    'Name' => $r['nome_squadra'],
                    'Id' => (int)$r['id_squadra'],
                    
                    'Church' => $r['nome_parrocchia'],
                    'ChurchId' => (int)$r['id_parrocchia'],
    
                    'Sport' => $r['nome_sport'],
                    'SportId' => (int)$r['id_sport'],
    
                    'TournamentName' => $r['nome_torneo'],
                    'TournamentId' => (int)$r['id_torneo'],
    
                    'Points' => isset($r['punteggio']) ? (int)$r['punteggio'] : null,
                    'MatchesToPlay' => isset($r['partite_da_giocare']) ? (int)$r['partite_da_giocare'] : null,
                    'PlannedMatches' => isset($r['partite_previste']) ? (int)$r['partite_previste'] : null,
                ];
            }
        );
    }

    protected function tournament_sport(string $Sport): ApiCall
    {
        $area = $this->DB->escape_string($Sport);
        return new ApiCall(
            query: "SELECT * FROM `tornei_attivi` WHERE UPPER(`area`) = UPPER('$area')",
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
    
                    'Sport' => $r['sport'],
                    'SportId' => (int)$r['codice_sport'],
    
                    'Type' => $r['tipo'],
    
                    'Teams' => array_map(callback: function ($s): string {
                        return trim(string: $s);
                    }, array: explode(separator: ',', string: $r['squadre'])),
                ];
            }
        );
    }
}