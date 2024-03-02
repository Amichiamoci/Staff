<?php

class Security
{
    public static function Hash(string $str) : string
    {
        if (!isset($str))
            return "";
        return password_hash($str, PASSWORD_BCRYPT);
    }
    public static function TestPassword(string $password, string $hash) : bool
    {
        if (!isset($password) || !isset($hash))
            return false;
        return password_verify($password, $hash);
    }
    public static function IsFromApp() : bool
    {
        return isset($_COOKIE['AppVersion']) && !is_array($_COOKIE['AppVersion']);
    }
}

