<?php

namespace Amichiamoci\Models\Api\Traits;
use Amichiamoci\Models\Api\Call as ApiCall;

trait Partita
{
    protected function today_matches_of(string $Email): ApiCall
    {
        $email = $this->DB->escape_string($Email);
        $query = "SELECT * FROM `partite_oggi_persona` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
        return new ApiCall(
            query: $query,
            row_parser: function(array $r): array {
                $arr = [
                    'WhoPlays' => $r['nome'] . ' ' . $r['cognome'],
                    'Email' => $r['email'],
                    'PlayerId' => (int)$r['id'],
                    'NeedsMedicalCertificate' => (bool)($r['necessita_certificato'] == 1),
    
                    'Id' => (int)$r['id_partita'],
    
                    'TournamentName' => $r['torneo'],
                    'TournamentId' => (int)$r['codice_torneo'],
    
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
                        'ChurchId' => (int)$r['id_parrocchia_ospite'],
                    ],
                    'HomeScore' => null,
                    'GuestScore' => null,
                    'Scores' => [
                        'Id' => [],
                        'Home' => [],
                        'Guest' => [],
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

    protected function today_matches_sport(string $Sport): ApiCall
    {
        $area = $this->DB->escape_string($Sport);
        $query = "SELECT * FROM `partite_oggi` WHERE UPPER(`area_sport`) = UPPER('$area')";
        return new ApiCall(
            query: $query,
            row_parser: function(array $r): array {
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

    protected function today_and_yesterday_matchest(): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `partite_oggi_ieri`",
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

    protected function add_result(int $Id, string $Home, string $Guest): ApiCall
    {
        $home = $this->DB->escape_string($Home);
        $guest = $this->DB->escape_string($Guest);
        $query = "CALL `CreaPunteggioCompleto`($Id, TRIM('$home'), TRIM('$guest'));";
        return new ApiCall(
            query: $query,
            row_parser: function (array $r) use($home, $guest): array {
                return [
                    'Id' => (int)$r['id'],
                    'Home' => $home,
                    'Guest' => $guest,
                ];
            },
            is_procedure: true,
        );
    }

    protected function delete_result(int $Id): ApiCall
    {
        return new ApiCall(
            query: "DELETE FROM `punteggi` WHERE `id` = $Id",
        );
    }
}