<?php

include "../load/db_manager.php";
// Deletes the cookies
if (isset($_COOKIE["user_id"]))
{
    $id = $_COOKIE["user_id"];
    logUserOut($id);
}
if (isset($_COOKIE["login_forward"]))
{
    our_delete_cookie("login_forward");
}
header("location: ../../index.php");