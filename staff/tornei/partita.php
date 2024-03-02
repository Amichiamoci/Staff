<?php
include "../../check_login.php";

$dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

header("Content-Type: application/json");
if (!$dati_staff->is_in("Tornei") && !$anagrafica->is_admin) {
    echo "{\"message\": \"Errore: Operazione non consentita\"}";
    exit;
}

if (!isset($_POST["id"]))
{
    echo "{\"message\": \"Errore: id mancante\"}";
    exit;
}
$id = (int)$_POST["id"];
if (isset($_POST["data"]))
{
    $data = sql_sanitize($_POST["data"]);
    $query = "UPDATE partite SET data = '$data' WHERE id = $id";
}
if (isset($_POST["ora"]))
{
    $ora = sql_sanitize($_POST["ora"]);
    $query = "UPDATE partite SET orario = '$ora' WHERE id = $id";
}
if (isset($_POST["campo"]))
{
    $campo = (int)$_POST["campo"];
    if ($campo === 0)
    {
        $query = "UPDATE partite SET campo = NULL WHERE id = $id";
    } else {
        $query = "UPDATE partite SET campo = $campo WHERE id = $id";
    }
}
if (!isset($query))
{
    echo "{\"message\": \"Errore: nessuna azione specificata\"}";
    exit;
}
try {
    $result = mysqli_query($connection, $query);
} catch (Exception $ex)
{
    $result = false;
}
if (!$result)
{
    echo "{\"message\": \"Errore: Impossibile raggiungere il DB\"}";
    exit;
}
if (mysqli_affected_rows($connection) !== 1)
{
    echo "{\"message\": \"Errore: righe modificate non uguali ad 1\"}";
    exit;
}
echo "{\"message\": \"ok\"}";