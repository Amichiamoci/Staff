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
    if (!is_string($_GET[$name]))
    {
        http_response_code(400);
        exit;
    }
    return $_GET[$name];
}

$resource = $_GET["resource"];
$result = [];
$row_parser = function($r){ return $r; };
switch ($resource)
{
    case "events":
        $query = 'SELECT * FROM `eventi_a_breve`';
        break;
    case "matches":
        $query = 'SELECT * FROM `partite_settimana`';
        break;
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
        $email_ref = get_additional_param('email');
        $query = 'SELECT * FROM `anagrafiche_con_iscrizioni_correnti` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(\'' . 
            $connection->real_escape_string($email_ref) . '\'))';
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
            ];
        };
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

header("Content-Type: application/json");
echo json_encode(array_map($row_parser, $result)); exit;