<?php

if (!isset($_GET["val"]) || empty($_GET["val"]))
{
    header("Location: ./index.php");
    exit;
}

require_once "../load/db_manager.php";
require_once "../load/models/token.php"; 

$token = Token::LoadIfNotExpired($connection, $_GET["val"]);

if (!isset($token))
{
    header("Location: ./index.php");
    exit;
}

$edizione = Edizione::LoadSingle($connection, $token->edition);
if (!isset($edizione) || !$edizione->ok())
{
    // Should not happen
    header("Location: ./edizione_non_trovata.php");
    exit;
}
if (!$edizione->IscrizioniAperte())
{
    header("Location: ./edizione_passata.php?year=" . $edizione->year);
    exit;
}

$anagrafica = Anagrafica::Load($connection, $token->anagrafica);
if (!isset($anagrafica))
{
    // Should not happen
    header("Location: ./index.php");
    exit;
}

$token->Expire($connection);

Cookie::Set("id_anagrafica", $anagrafica->id, 60 * 60 * 12);
if (empty($anagrafica->doc_expires) || $anagrafica->doc_expires <= date("Y-m-d"))
{
    // Document expired or missing -> upload a new one
    header("Location: ./reupload_documento.php");
    exit;
}

header("Location: ./iscrizione-anagrafica.php?year=" . date("Y"));