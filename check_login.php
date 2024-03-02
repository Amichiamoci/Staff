<?php
//Do not add the ./ at the beginning of the path, 
//as this file can be included from variuos subfolders
require_once "load/db_manager.php";
$anagrafica = new AnagraficaResult();

if (!(isset($is_extern) && (bool)$is_extern))
{
    if (!isset($_COOKIE["user_id"])) 
    {
        //Sappiamo gia' che non e' loggato
        Cookie::Set("login_forward", $_SERVER['REQUEST_URI'], 3600 * 12);
        header("location: $DOMAIN/admin/manage/login.php");
        exit;
    }
    $user_id = (int)$_COOKIE["user_id"];
    if (isUserLogged($connection, $user_id, $_SERVER['HTTP_USER_AGENT'], getUserIp(), $anagrafica))
    {
        if (isset($_COOKIE["login_forward"]))
        {
            //Not needed anymore
            Cookie::Delete("login_forward");
        }
    } else {
    
        //Login non e' andato a buon fine
        Cookie::Set("login_forward", $_SERVER['REQUEST_URI'], 3600 * 12);
        header("location: $DOMAIN/admin/manage/login.php");
        exit;
    }
}

