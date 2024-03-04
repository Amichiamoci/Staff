<?php
require_once __DIR__ . "/config.php";
include_once __DIR__ . "/cookie.php";
include_once __DIR__ . "/email.php";
include_once __DIR__ . "/file_functions.php";
include_once __DIR__ . "/link.php";
require_once __DIR__ . "/security.php";

function getUserIP() : string
{
    $ipaddress = "127.0.0.1";
    if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    if ($ipaddress == "::1" || $ipaddress == "localhost")
    {
        return "127.0.0.1";
    }
    return $ipaddress;
}