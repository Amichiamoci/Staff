<?php
namespace Amichiamoci\Utils;
use GuzzleHttp\Client;

class Security
{
    public static function Hash(string $str) : string
    {
        if (!isset($str))
            return "";
        return password_hash(password: $str, algo: PASSWORD_BCRYPT);
    }
    public static function TestPassword(string $password, string $hash) : bool
    {
        if (!isset($password) || !isset($hash))
            return false;
        return password_verify(password: $password, hash: $hash);
    }
    public static function IsFromApp() : bool
    {
        return isset($_COOKIE['AppVersion']) && !is_array(value: $_COOKIE['AppVersion']);
    }
    public static function RandomPassword(int $length = 10) : string
    {
        $alphabet = str_split(string: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!?/@;*+-$%&=^_");
        $password = "";
        for ($i = 0; $i < $length; $i++)
        {
            $password .= $alphabet[random_int(min: 0, max: count(value: $alphabet) - 1)];
        }
        return $password;
    }
    public static function GetIpAddress(): string {
        
        //$headers = getallheaders();
        //if (array_key_exists(key: 'Cf-Connecting-Ip', array: $headers)) {
              // Cloudflare tunnel forwarding
        //    return $headers['Cf-Connecting-Ip'];
        //}

        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] !== '::1') {
            //ip from share internet
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function LoadEnvironmentOfFromFile(string $var, ?string $default = null): ?string
    {
        if (empty($var)) {
            throw new \Exception(
                message: 'Varible name can\'t be empty!');
        }

        if (array_key_exists(key: $var, array: $_ENV)) {
            return $_ENV[$var];
        }

        if (
            !array_key_exists(key: $var . "_FILE", array: $_ENV) || 
            !file_exists(filename: $_ENV[$var . "_FILE"])) {
            return $default;
        }

        $content =  file_get_contents(filename: $_ENV[$var . "_FILE"]);
        if (!$content) 
            return $default;
        return $content;
    }

    public static function CfTurnStileVerify(string $turnstile_response): bool {
        if (strlen(string: $turnstile_response) === 0) {
            return false;
        }

        // Load secret
        $secret = self::LoadEnvironmentOfFromFile(var: 'CF_TURNSTILE_SECRET');
        if (empty($secret)) {
            return false;
        }

        // Challenge
        $http_client = new Client();
        $cf_response = $http_client->request(
            'POST',
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'form_params' => [
                    'secret' => $secret,
                    'response' => $turnstile_response,
                ]
            ]
        );
        $object = json_decode(json: (string)$cf_response->getBody());
        return (bool)$object->success;
    }
}