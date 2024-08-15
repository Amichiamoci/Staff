<?php

require_once __DIR__ . "/load/db-manager.php";

$_HEADERS = getallheaders();
if (!array_key_exists('App-Bearer', $_HEADERS))
{
    http_response_code(401);
    exit;
}

// TODO: check validity of the token
//if ($_HEADERS['App-Bearer'] !== $_ENV['APP_SECRET'])
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
switch ($resource)
{
    case "events":
        $query = 'SELECT * FROM `eventi_a_breve`';
        break;
    case "matches":
        $query = 'SELECT * FROM `partite_settimana`';
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

$result = $response->fetch_array();
if (!isset($result) || !$result)
{
    $result = [];
}

header("Content-Type: application/json");
echo json_encode($result); exit;