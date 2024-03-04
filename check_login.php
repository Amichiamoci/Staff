<?php
//Do not add the ./ at the beginning of the path, 
//as this file can be included from variuos subfolders
require_once __DIR__ . "/load/db_manager.php";
$is_extern = isset($is_extern) && (bool) $is_extern;

if (!$is_extern)
{
    $user = User::LoadFromSession();
    if (!isset($user) || $user->TimeLogged() > 60 * 30)
    {
        Cookie::Set("login_forward", $_SERVER['REQUEST_URI'], 3600 * 12);
        header("location: " . ADMIN_PATH . "/manage/login.php");
        exit;
    }
    if (!$user->UploadDBLog($connection))
    {
        header("location: " . ADMIN_PATH . "/manage/login.php");
        exit;
    }
    User::$Current = $user;
    Cookie::DeleteIfExists("login_forward");
    User::$Current->UpdateLogTs();
    User::$Current->PutLogTsInSession();
    if (!User::$Current->HasAdditionalData())
    {
        if (User::$Current->LoadAdditionalData($connection))
        {
            User::$Current->PutAdditionalInSession();
        }
    }
}

