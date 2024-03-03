<?php

include "../load/db_manager.php";
// Deletes the cookies
if (isset(User::$Current))
{
    User::$Current->Logout();
}
Cookie::DeleteIfExists("login_forward");
header("location: ../../index.php");