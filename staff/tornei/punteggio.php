<?php
include "../../check_login.php";

$dati_staff = Staff::Get($connection, User::$Current->staff_id);

header("Content-Type: application/json");

if (!isset($_POST["id"]))
{
    echo "{\"message\": \"Errore: id mancante\"}";
    exit;
}
$id = (int)$_POST["id"];
if (isset($_POST["add_score"]))
{
    $query = "CALL `CreaPunteggio`($id);";
    try {
        $result = $connection->query($query);
        $connection->next_result();
    } catch (Exception $ex)
    {
        $result = false;
    }
    if (!$result || $result->num_rows < 1)
    {
        echo "{\"message\": \"Errore: Impossibile raggiungere il DB\"}";
        exit;
    }
    if ($row = $result->fetch_assoc())
    {
        $id_punteggio = (int)$row["id"];
        echo "{\"message\": \"ok\", \"id\": $id_punteggio }";
    } else {
        echo "{\"message\": \"Errore\"}";
    }
    exit;
}
if (isset($_POST["update_score"]))
{
    $nuovo_punteggio = $connection->real_escape_string($_POST["update_score"]);
    $punteggi = explode("-", $nuovo_punteggio);
    if (count($punteggi) !== 2)
    {
        echo "{\"message\": \"Errore: Dati malformati\"}";
        exit;
    }
    $casa = $punteggi[0];
    $ospiti = $punteggi[1];
    $query = "UPDATE `punteggi` SET `home` = TRIM('$casa'), `guest` = TRIM('$ospiti') WHERE `id` = $id";
}
if (isset($_POST["remove_score"]))
{
    $query = "DELETE FROM `punteggi` WHERE `id` = $id";
}
if (!isset($query))
{
    echo "{\"message\": \"Errore: nessuna azione specificata\"}";
    exit;
}
try {
    $result = $connection->query($query);
} catch (Exception $ex)
{
    $result = false;
}
if (!$result)
{
    echo "{\"message\": \"Errore: Impossibile raggiungere il DB\"}";
    exit;
}
if ($connection->affected_rows !== 1)
{
    echo "{\"message\": \"Errore: righe modificate non uguali ad 1\"}";
    exit;
}
echo "{\"message\": \"ok\"}";