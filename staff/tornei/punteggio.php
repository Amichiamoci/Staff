<?php
include "../../check_login.php";

$dati_staff = getCurrentYearStaffData($connection, $anagrafica->staff_id);

header("Content-Type: application/json");

if (!isset($_POST["id"]))
{
    echo "{\"message\": \"Errore: id mancante\"}";
    exit;
}
$id = (int)$_POST["id"];
if (isset($_POST["add_score"]))
{
    $query = "CALL CreaPunteggio($id);";
    try {
        $result = mysqli_query($connection, $query);
        mysqli_next_result($connection);
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
    $nuovo_punteggio = sql_sanitize($_POST["update_score"]);
    $punteggi = explode("-", $nuovo_punteggio);
    if (count($punteggi) !== 2)
    {
        echo "{\"message\": \"Errore: Dati malformati\"}";
        exit;
    }
    $casa = $punteggi[0];
    $ospiti = $punteggi[1];
    $query = "UPDATE punteggi SET home = TRIM('$casa'), guest = TRIM('$ospiti') WHERE id = $id";
}
if (isset($_POST["remove_score"]))
{
    $query = "DELETE FROM punteggi WHERE id = $id";
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