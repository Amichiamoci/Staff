<?php

require_once __DIR__ . "/load/db_manager.php";

$_HEADERS = getallheaders();
//if (!array_key_exists('App-Bearer', $_HEADERS) || $_HEADERS['App-Bearer'] !== $_ENV['APP_SECRET'])
//{
//    http_response_code(401);
//    exit;
//}


if (!is_string($_GET["resource"]))
{
    http_response_code(400);
    exit;
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
    case "distinte":
        $query = 'SELECT * FROM `distinte`';
        $row_parser = function ($row) {
            $semi_parsed =  [
                'id' => (int)$row['id'],
                'teamId' => (int)$row['squadra_id'],
                'subscriptionId' => (int)$row['iscritto_id'],
                'fullName' => $row['chi'],
                'sex' => is_string($row['sesso']) ? $row['sesso'] : '?',
                'problems' => array()
            ];

            // Other problems
            if (is_string($row['tutore_problem']))
            {
                $semi_parsed['problems'][] = $row['tutore_problem'];
            }
            if (is_string($row['certificato_problem']))
            {
                $semi_parsed['problems'][] = $row['certificato_problem'];
            }

            // Document related problems
            if (is_string($row['doc_problem']))
            {
                $semi_parsed['problems'][] = $row['doc_problem'];
            }
            //if (is_string($row['doc_code_problem']))
            //{
            //    $semi_parsed['problems'][] = $row['doc_code_problem'];
            //}
            if (is_string($row['scadenza_problem']))
            {
                $semi_parsed['problems'][] = $row['scadenza_problem'];
            }
            return $semi_parsed;
        };
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

header("Content-Type: application/json");
echo json_encode(array_map($row_parser, $result)); exit;