<?php

require_once __DIR__ . "/load/db_manager.php";

$_HEADERS = getallheaders();
if (!array_key_exists('App-Bearer', $_HEADERS) || $_HEADERS['App-Bearer'] !== $_ENV['APP_SECRET'])
{
    http_response_code(401);
    exit;
}


if (!is_string($_GET["resource"]))
{
    http_response_code(400);
    exit;
}
function get_additional_param(string $name): string {
    global $_HEADERS;
    if (!array_key_exists('Data-Param-' . $name, $_HEADERS))
    {
        if (!array_key_exists($name, $_GET) || !is_string($_GET[$name]))
        {
            http_response_code(400);
            exit;
        }
        return $_GET[$name];
    }
    return $_HEADERS['Data-Param-' . $name];
}

$resource = $_GET["resource"];

// Defaults
$result = [];
$row_parser = function($r) { return $r; };
$next_result = false;

switch ($resource)
{
    case "teams-members":
        $query = 'SELECT * FROM `distinte`';
        $row_parser = function ($row) {
            $semi_parsed =  [
                'Id' => (int)$row['id'],
                'TeamId' => (int)$row['squadra_id'],
                'SubscriptionId' => (int)$row['iscrizione'],
                'FullName' => $row['chi'],
                'Sex' => is_string($row['sesso']) ? $row['sesso'] : '?',
                'Problems' => array()
            ];

            // Other problems
            if (is_string($row['tutore_problem']))
            {
                $semi_parsed['Problems'][] = $row['tutore_problem'];
            }
            if (is_string($row['certificato_problem']))
            {
                $semi_parsed['Problems'][] = $row['certificato_problem'];
            }

            // Document related problems
            if (is_string($row['doc_problem']))
            {
                $semi_parsed['Problems'][] = $row['doc_problem'];
            }
            //if (is_string($row['doc_code_problem']))
            //{
            //    $semi_parsed['Problems'][] = $row['doc_code_problem'];
            //}
            if (is_string($row['scadenza_problem']))
            {
                $semi_parsed['Problems'][] = $row['scadenza_problem'];
            }
            return $semi_parsed;
        };
        break;
    case "teams-info":
        $query = 'SELECT * FROM `squadre_attuali`';
        $row_parser = function ($row) {
            return [
                'Name' => $row['nome'],
                'Id' => (int)$row['id'],
                
                'Church' => $row['parrocchia'],
                'ChurchId' => (int)$row['id_parrocchia'],

                'Sport' => $row['sport'],
                'SportId' => (int)$row['id_sport'],

                'MemberCount' => (int)$row['membri']
            ];
        };
        break;
    case "managed-anagraphicals":
        $email = $connection->real_escape_string(get_additional_param('Email'));
        $query = "SELECT * FROM `anagrafiche_con_iscrizioni_correnti` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
        $row_parser = function($r) {
            return [
                'Id' => (int)$r['id'],
                'Name' => $r['nome'],
                'Surname' => $r['cognome'],
                
                'Phone' => is_string($r['telefono']) && strlen($r['telefono']) > 0 ? $r['telefono'] : null,
                'Email' => is_string($r['email']) && strlen($r['email']) > 0 ? $r['email'] : null,
                
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
        };
        break;
    case "church":
        $id = (int)get_additional_param('Id');
        $query = "SELECT * FROM `parrocchie` WHERE `id` = $id";
        $row_parser = function($r) {
            return [
                'Id' => (int)$r['id'],
                'Name' => $r['nome'],

                'Address' => is_string($r['indirizzo']) && strlen($r['indirizzo']) > 0 ? $r['indirizzo'] : null,
                'Website' => is_string($r['website']) && strlen($r['website']) > 0 ? $r['website'] : null,
            ];
        };
        break;
    case "staff-list":
        $query = "SELECT * FROM `staffisti_attuali`";
        $row_parser = function($r){
            return [
                'Name' => $r['chi'],
                'ChurchId' => (int)$r['id_parrocchia'],
                
                'Phone' => is_string($r['telefono']) && strlen($r['telefono']) > 0 ? $r['telefono'] : null,
                'Email' => is_string($r['email']) && strlen($r['email']) > 0 ? $r['email'] : null,
            ];
        };
        break;
    case "today-matches-of":
        $email = $connection->real_escape_string(get_additional_param('Email'));
        $query = "SELECT * FROM `partite_oggi_persona` WHERE TRIM(LOWER(`email`)) = TRIM(LOWER('$email'))";
        $row_parser = function($r) {
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
                'HomeTeamScore' => null,
                'GuestTeam' => [
                    'Name' => $r['squadra_ospite'],
                    'Id' => (int)$r['squadra_ospite_id'],
                    
                    'Sport' => $r['sport'],
                    'SportId' => (int)$r['codice_sport'],

                    'Church' => $r['nome_parrocchia_ospite'],
                    'ChurchId' => (int)$r['id_parrocchia_ospite'],
                ],
                'GuestTeamScore' => null
            ];

            if (is_string($r['nome_campo']) && isset($r['id_campo']))
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
        };
        break;
    case "today-matches-sport":
        $sport = $connection->real_escape_string(get_additional_param('Sport'));
        $query = "SELECT * FROM `partite_oggi_completo` WHERE `area_sport` = UPPER('$sport')";
        $row_parser = function($r) {
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
                'HomeTeamScore' => null,
                'GuestTeam' => [
                    'Name' => $r['squadra_ospite'],
                    'Id' => (int)$r['squadra_ospite_id'],
                    
                    'Sport' => $r['sport'],
                    'SportId' => (int)$r['codice_sport'],

                    'Church' => $r['nome_parrocchia_ospite'],
                    'ChurchId' => (int)$r['id_parrocchia_ospiti'],
                ],
                'GuestTeamScore' => null
            ];

            if (is_string($r['nome_campo']) && isset($r['id_campo']))
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
        };
        break;
    case "today-yesterday-matches":
        $query = "SELECT * FROM `partite_oggi_ieri_completo`";
        $row_parser = function($r) {
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
                'HomeTeamScore' => null,
                'GuestTeam' => [
                    'Name' => $r['squadra_ospite'],
                    'Id' => (int)$r['squadra_ospite_id'],
                    
                    'Sport' => $r['sport'],
                    'SportId' => (int)$r['codice_sport'],

                    'Church' => $r['nome_parrocchia_ospite'],
                    'ChurchId' => (int)$r['id_parrocchia_ospiti'],
                ],
                'GuestTeamScore' => null
            ];

            if (is_string($r['nome_campo']) && isset($r['id_campo']))
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
        };
        break;
    case "tourney":
        $id = (int)get_additional_param('Id');
        $query = "SELECT * FROM `tornei_attivi` WHERE `id` = $id";
        $row_parser = function($r) {
            return [
                'Id' => (int)$r['id'],
                'Name' => $r['nome'],

                'Sport' => $r['sport'],
                'SportId' => (int)$r['codice_sport'],

                'Type' => $r['tipo'],

                'Teams' => array_map(function($s): string {
                    return trim($s);
                }, explode(',', $r['squadre'])),
            ];
        };
        break;
    case "tourney-matches":
        $id = (int)get_additional_param('Id');

        break;

        
    case "new-match-result":
        $match = (int)get_additional_param('Id');
        $query = "CALL `CreaPunteggio`($match);";
        $next_result = true;
        $row_parser = function($r) {
            return [ 'Id' => $r['id'] ];
        };
        break;
    case "set-match-result":
        $id = (int)get_additional_param('Id');
        $home = $connection->real_escape_string( get_additional_param('Home') );
        $guest = $connection->real_escape_string( get_additional_param('Guest') );
        
        $query = "UPDATE `punteggi` SET `home` = '$home', `guest` = '$guest' WHERE `id` = $id;";
        
        $row_parser = function($r) {
            return [ 'Id' => $r['id'] ];
        };
        break;
    case "delete-match-result":
        $id = (int)get_additional_param('Id');
        $query = "DELETE FROM `punteggi` WHERE `id` = $id";
        break;
    default: {
        http_response_code(404);
        exit;
    }
}

$response = $connection->execute_query($query);
if (!$response)
{
    http_response_code(500);
    exit;
}

$result = $response->fetch_all(MYSQLI_ASSOC);
if (!isset($result) || !$result)
{
    $result = [];
}
if ($next_result) {
    $connection->next_result();
}

header("Content-Type: application/json");
echo json_encode(array_map($row_parser, $result)); exit;