<?php

namespace Amichiamoci\Controllers;

use Amichiamoci\Models\Api\Call as ApiCall;
use Amichiamoci\Models\Api\Token as ApiToken;
use Amichiamoci\Models\Message;
use Amichiamoci\Utils\Security;
use Reflection;
use ReflectionClass;

class ApiController extends Controller
{
    public function delete_token(?int $id = null): int
    {
        $this->RequireLogin(require_admin: true);
        if (empty($id))
        {
            return $this->admin();
        }

        if (ApiToken::Delete(connection: $this->DB, id: $id))
        {
            $this->Message(message: Message::Success(content: 'Token correttamente eliminato'));
        } else {
            $this->Message(message: Message::Error(content: 'Non è stato possibile disabilitare il token'));
        }

        return $this->admin();
    }
    public function admin(?string $token_name = null): int
    {
        $this->RequireLogin(require_admin: true);
        $generated_key = null;

        if (self::IsPost() && isset($token_name))
        {
            $new_token = ApiToken::New(connection: $this->DB, name: $token_name);
            if (isset($new_token))
            {
                $this->Message(
                    message: Message::Success(
                        content: "Token correttamente generato.")
                );
                $generated_key = $new_token->Key;
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
                'new_key' => $generated_key,
            ],
        );
    }

    public function index(?string $resource = null): int {
        if (!isset($resource))
        {
            return $this->Json(
                object: [
                    'message' => 'Invalid resource',
                    'resource' => $resource,
                ],
                status_code: 400,
            );
        }

        $bearer = $this->get_bearer();
        if (!isset($bearer))
        {
            return $this->Json(
                object: [
                    'message' => "Header 'App-Bearer' missing",
                ],
                status_code: 400,
            );
        }

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

        if (!array_key_exists(key: $resource, array: $this->avaible_methods))
        {
            return $this->NotFound();
        }

        // Get the parameters for the query
        $parameters = self::get_parameters();

        $dummy_controller = new ReflectionClass(objectOrClass: $this);
        $method = $dummy_controller->getMethod(name: $this->avaible_methods[$resource]);
        $method->setAccessible(accessible: true);

        try { 
            // $call_object = call_user_func_array(callback: [$dummy_controller, $f], args: $parameters);
            $call_object = $method->invokeArgs(object: $this, args: $parameters);
            $result = $call_object->Execute($this->DB);

            if (!isset($result))
            {
                return $this->Json(
                    object: ['message' => 'Could not obtain result from action'],
                    status_code: 500,
                );
            }
        } catch (\Throwable $ex) {
            return $this->Json(
                object: [
                    'message' => $ex->getMessage(),
                    'stack' => $ex->getTrace(),
                    'line' => $ex->getLine(),
                    'file' => $ex->getFile(),
                ],
                status_code: 500,
            );
        }

        return $this->Json(object: $result);
    }

    private function get_bearer(): ?string
    {
        $headers = getallheaders();
        if (!array_key_exists(key: "App-Bearer", array: $headers))
        {
            return null;
        }
        return $headers["App-Bearer"];
    }
    private static function get_parameters(): array
    {
        $arr = [];
        foreach (getallheaders() as $key => $value)
        {
            if (!str_starts_with(haystack: $key, needle: 'Data-Param-'))
            {
                continue;
            }

            $arr[substr(string: $key, offset: strlen(string: 'Data-Param-'))] = $value;
        }
        return $arr;
    }

    private array $avaible_methods = [
        'teams-members' => 'teams_members',
        'teams-info' => 'teams_info',

        'church' => 'church',
        'churches' => 'churches',
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

    private function church(int $Id): ApiCall
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

    private function churches(): ApiCall
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

    private function managed_anagraphicals(string $Email): ApiCall
    {
        $email = $this->DB->escape_string($Email);
        $query = 
            "SELECT * " .
            "FROM `anagrafiche_con_iscrizioni_correnti` " .
            "WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email')) OR LOWER(TRIM(`email_tutore`)) = LOWER(TRIM('$email'))";
        return new ApiCall(
            query: $query,
            row_parser: function (array $r): array {
                return [
                    'Id' => (int)$r['id'],
                    'Name' => $r['nome'],
                    'Surname' => $r['cognome'],
                    
                    'Phone' => is_string(value: $r['telefono']) && strlen(string: $r['telefono']) > 0 ? $r['telefono'] : null,
                    'Email' => is_string(value: $r['email']) && strlen(string: $r['email']) > 0 ? $r['email'] : null,
                    
                    'TaxCode' => $r['cf'],
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

    private function today_matches_of(string $Email): ApiCall
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

    private function today_matches_sport(string $Sport): ApiCall
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

    private function today_and_yesterday_matchest(): ApiCall
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

    private function tournament(int $Id): ApiCall
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

    private function tournament_matches(int $Id): ApiCall
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

    private function tournament_leaderboard(int $Id): ApiCall
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

    private function tournament_sport(string $Sport): ApiCall
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

    private function add_result(int $Id, string $Home, string $Guest): ApiCall
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

    private function delete_result(int $Id): ApiCall
    {
        return new ApiCall(
            query: "DELETE FROM `punteggi` WHERE `id` = $Id",
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