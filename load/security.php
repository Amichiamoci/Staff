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
    public static function RandomPassword(int $length = 10) : string
    {
        $alphabet = str_split("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!?/@;*+-$%&=^_");
        $password = "";
        for ($i = 0; $i < $length; $i++)
        {
            $password .= $alphabet[random_int(0, count($alphabet) - 1)];
        }
        return $password;
    }
}

