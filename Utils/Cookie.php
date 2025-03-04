<?php
namespace Amichiamoci\Utils;

class Cookie
{
    public static function Set(string $name, string $value, int $exp) : bool
    {
        if (!defined(constant_name: "DOMAIN") || !defined(constant_name: "ADMIN_PATH")) 
            return false;
        if (!isset($name) || empty($name)) 
            return false;

        return setcookie(
            name: $name, 
            value: $value, 
            expires_or_options: time() + $exp, 
            path: ADMIN_PATH, 
            domain: DOMAIN, 
            secure: false, 
            httponly: true
        );
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
        return self::Set(name: $name, value: "", exp: -3600);
    }
    public static function DeleteIfItIs(string $name, string $value) : bool
    {
        if (!self::Exists(name: $name))
            return false;
        if (self::Get(name: $name) !== $value)
            return true;
        return self::Delete(name: $name);
    }
    public static function Exists(string $name) : bool
    {
        return isset($name) && !empty($name) && isset($_COOKIE[$name]);
    }
    public static function DeleteIfExists(string $name) : bool
    {
        if (!self::Exists(name: $name))
        {
            return true;
        }
        return self::Delete(name: $name);
    }
}