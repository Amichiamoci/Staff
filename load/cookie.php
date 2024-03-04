<?php

class Cookie
{
    public static function Set(string $name, string $value, int $exp) : bool
    {
        if (!defined("DOMAIN") || !defined("ADMIN_PATH")) return false;
        if (!isset($name) || empty($name)) return false;

        return setcookie($name, $value, time() + $exp, ADMIN_PATH, DOMAIN, false, true);
    }
    public static function Get(string $name)
    {
        if (!isset($name) || empty($name))
        {
            return null;
        }
        return $_COOKIE[$name];
    }
    public static function Delete(string $name) : bool
    {
        return Cookie::Set($name, "", -3600);
    }
    public static function DeleteIfItIs(string $name, string $value) : bool
    {
        if (!Cookie::Exists($name))
            return false;
        if (Cookie::Get($name) !== $value)
            return true;
        return Cookie::Delete($name);
    }
    public static function Exists(string $name) : bool
    {
        return isset($name) && !empty($name) && isset($_COOKIE[$name]);
    }
    public static function DeleteIfExists(string $name) : bool
    {
        if (!Cookie::Exists($name))
        {
            return true;
        }
        return Cookie::Delete($name);
    }
}