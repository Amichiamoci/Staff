<?php
include "../../load/config.php";
require_once("../../load/basic_functions.php");
if (isset($_GET["id"]))
{
    Cookie::Set("id_anagrafica", $_GET["id"], 3600 * 24);
}
header("Location: iscrivi.php");
exit;