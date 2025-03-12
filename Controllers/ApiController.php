<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Api\Call as ApiCall;
use Amichiamoci\Models\Api\Token as ApiToken;
use Amichiamoci\Models\Message;
use Amichiamoci\Utils\Security;

class ApiController extends Controller
{
    public function admin(?string $token_name = null): int
    {
        $this->RequireLogin(require_admin: true);

        if (self::IsPost() && isset($token_name))
        {
            $new_token = ApiToken::New(connection: $this->DB, name: $token_name);
            if (isset($new_token))
            {
                $this->Message(
                    message: Message::Success(
                        content: "Token correttamente generato: " . $new_token->Key)
                );
            } else {
                $this->Message(
                    message: Message::Error(content: "Impossibile generare il token!")
                );
            }
        }

        return $this->Render(
            view: 'Api/all',
            title: 'Applicazioni attive',
            data: [
                'tokens' => ApiToken::All(connection: $this->DB),
            ],
        );
    }

    public function get(?string $resource = null): int {
        if (empty($resource)) {
            return $this->BadRequest();
        }

        $bearer = $this->get_param(name: 'Bearer');
        if (!ApiToken::Test(
            connection: $this->DB, 
            key: $bearer, 
            ip: Security::GetIpAddress(),
        )) {
            return $this->Json(
                object: ['message' => 'Invalid bearer token'],
                status_code: 401,
            );
        }

        if (!array_key_exists(key: $resource, array: $this->avaible_methods)) {
            return $this->NotFound();
        }

        // Get the parameters for the query
        $f = $this->avaible_methods[$resource];
        $call_object = $this->$f();

        $result = $call_object->Execute($this->DB);
        if (!isset($result)) {
            return $this->InternalError();
        }

        return $this->Json(object: $result);
    }

    private function get_param(string $name): string
    {
        global $_HEADERS;
        if (!array_key_exists(key: "Data-Param-$name", array: $_HEADERS))
        {
            $this->Json(object: [
                'message' => "Parameter '$name' missing",
            ], status_code: 400);
            exit;
        }
        return $_HEADERS["Data-Param-$name"];
    }

    private array $avaible_methods = [
        'teams-members' => 'teams_members',
        'teams-info' => 'teams_info',

        'church' => 'church',
        'staff-list' => 'staff_list',

        'managed-anagraphicals' => 'managed_anagraphicals',

        'today-matches-of' => 'today_matches_of',
        'today-matches-sport' => 'today_matches_sport',
        'today-yesterday-matches' => 'today_and_yesterday_matchest',

        'tournament' => 'tournament',
        'tournament-matches' => 'tournament_matches',
        'tournament-leaderboard' => 'tournament_leaderboard',
        'tournament-sport' => 'tournament_sport',

        'new-match-result' => 'add_result',
        'delete-match-result' => 'delete_result',

        'leaderboard' => 'leaderboard',
    ];
    private function teams_members(): ApiCall
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

    private function teams_info(): ApiCall
    {
        return new ApiCall(
            query: 'SELECT * FROM `squadre_attuali`',
            row_parser: function (array $row): array {
                return [
                    'Name' => $row['nome'],
                    'Id' => (int)$row['id'],
                    
                    'Church' => $row['parrocchia'],
                    'ChurchId' => (int)$row['id_parrocchia'],
    
                    'Sport' => $row['sport'],
                    'SportId' => (int)$row['id_sport'],
    
                    'MemberCount' => (int)$row['membri']
                ];
            },
        );
    }

    private function church(): ApiCall
    {
        $id = (int)$this->get_param(name: 'Id');

        return new ApiCall(
            query: "SELECT * FROM `parrocchie` WHERE `id` = $id",
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

    private function staff_list(): ApiCall
    {
        return new ApiCall(
            query: 'SELECT * FROM `staffisti_attuali`',
            row_parser: function (array $r): array {
                return [
                    'Name' => $r['chi'],
                    'ChurchId' => (int)$r['id_parrocchia'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                ];
            }
        );
    }

    private function managed_anagraphicals(): ApiCall
    {
        $email = $this->DB->escape_string($this->get_param(name: 'Email'));
        $query = 
            "SELECT * " .
            "FROM `anagrafiche_con_iscrizioni_correnti` " .
            "WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email')) OR LOWER(TRIM(`email_tutore`)) = LOWER(TRIM('$email'))";
        return new ApiCall(
            query: $query,
            row_parser: function(array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
                    'Surname' => $r['cognome'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                    
                    'FiscalCode' => $r['cf'],
                    'BirthDate' => $r['data_nascita_italiana'],
                    
                    'Document' => [
                        'TypeId' => (int)$r['tipo_documento'],
                        'TypeName' => $r['nome_tipo_documento'],
                        'Code' => $r['codice_documento'],
                        'Message' => $r['scadenza_problem'],
                    ],
    
                    'MedicalCertificate' => $r['stato_certificato'],
                    'SubscriptionStatus' => $r['codice_iscrizione'],
                    'ShirtSize' => $r['maglia'],
    
                    'Church' => $r['parrocchia'],
                    'ChurchId' => (int)$r['id_parrocchia'],
                ];
            }
        );
    }

    private function today_matches_of(): ApiCall
    {
        $email = $this->DB->escape_string($this->get_param(name: 'Email'));
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
    
                    'TourneyName' => $r['torneo'],
                    'TourneyId' => (int)$r['codice_torneo'],
    
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

    private function today_matches_sport(): ApiCall
    {
        $area = $this->DB->escape_string($this->get_param(name: 'Sport'));
        $query = "SELECT * FROM `partite_oggi` WHERE UPPER(`area_sport`) = UPPER('$area')";
        return new ApiCall(
            query: $query,
            row_parser: function(array $r): array {
                $arr = [
                    'Id' => (int)$r['id'],
    
                    'TourneyName' => $r['nome_torneo'],
                    'TourneyId' => (int)$r['torneo'],
    
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

    private function today_and_yesterday_matchest(): ApiCall
    {
        return new ApiCall(
            query: "SELECT * FROM `partite_oggi_ieri`",
            row_parser: function($r): array {
                $arr = [
                    'Id' => (int)$r['id'],
    
                    'TourneyName' => $r['nome_torneo'],
                    'TourneyId' => (int)$r['torneo'],
    
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

    private function tournament(): ApiCall
    {
        $id = (int)$this->get_param(name: 'Id');
        return new ApiCall(
            query: "SELECT * FROM `tornei_attivi` WHERE `id` = $id",
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

    private function tournament_matches(): ApiCall
    {
        $id = (int)$this->get_param(name: 'Id');
        return new ApiCall(
            query: "SELECT * FROM `partite_completo` WHERE `torneo` = $id",
            row_parser: function($r): array {
                $arr = [
                    'Id' => (int)$r['id'],
    
                    'TourneyName' => $r['nome_torneo'],
                    'TourneyId' => (int)$r['torneo'],
    
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
                        'Guest' => is_string($r['punteggi_ospiti']) ?
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

    private function tournament_leaderboard(): ApiCall
    {
        $id = (int)$this->get_param(name: 'Id');
        return new ApiCall(
            query: "SELECT * FROM `classifica_torneo` WHERE `id_torneo` = $id ORDER BY CAST(`punteggio` AS UNSIGNED) DESC",
            row_parser: function(array $r): array {
                return [
                    'Name' => $r['nome_squadra'],
                    'Id' => (int)$r['id_squadra'],
                    
                    'Church' => $r['nome_parrocchia'],
                    'ChurchId' => (int)$r['id_parrocchia'],
    
                    'Sport' => $r['nome_sport'],
                    'SportId' => (int)$r['id_sport'],
    
                    'ToruneyName' => $r['nome_torneo'],
                    'TourneyId' => (int)$r['id_torneo'],
    
                    'Points' => isset($r['punteggio']) ? (int)$r['punteggio'] : null,
                    'MatchesToPlay' => isset($r['partite_da_giocare']) ? (int)$r['partite_da_giocare'] : null,
                    'PlannedMatches' => isset($r['partite_previste']) ? (int)$r['partite_previste'] : null,
                ];
            }
        );
    }

    private function tournament_sport(): ApiCall
    {
        $area = $this->DB->escape_string($this->get_param(name: 'Sport'));
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

    private function add_result(): ApiCall
    {
        $id = (int)$this->get_param(name: 'Id');
        $home = $this->DB->escape_string($this->get_param(name: 'Home'));
        $guest = $this->DB->escape_string($this->get_param(name: 'Guest'));
        $query = "CALL `CreaPunteggioCompleto`($id, TRIM('$home'), TRIM('$guest'));";
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

    private function delete_result(): ApiCall
    {
        $id = (int)$this->get_param(name: 'Id');
        return new ApiCall(
            query: "DELETE FROM `punteggi` WHERE `id` = $id",
        );
    }

    private function leaderboard(): ApiCall
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