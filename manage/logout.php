<?php

include "../load/db_manager.php";
// Deletes the cookies
if (isset($_COOKIE["user_id"]))
{
    $id = $_COOKIE["user_id"];
    logUserOut($id);
}
Cookie::DeleteIfExists("login_forward");
header("location: ../../index.php");