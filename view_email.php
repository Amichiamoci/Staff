<?php
include_once "./load/config.php";
include "./load/basic_functions.php";
if (isset($_GET["id"]))
{
    $id = (int)$_GET["id"];
    $query = "CALL OpenedEmail($id)";
    try {
        mysqli_query($connection, $query); 
    } catch (Exception $ex) { }; 
}

header("Content-Type: image/svg+xml");
?>
<svg xmlns="http://www.w3.org/2000/svg" width="2px" height="2px" viewBox="0 0 2 2">
    <rect x="0" y="0" width="2" height="2" fill="white" fill-opacity="0" />
</svg>