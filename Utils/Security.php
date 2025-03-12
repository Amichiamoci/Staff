<?php
namespace Amichiamoci\Utils;
use GuzzleHttp\Client as HttpClient;

class Security
{
    public static function Hash(#[\SensitiveParameter] string $str) : string
    {
        if (!isset($str))
            return "";
        return password_hash(password: $str, algo: PASSWORD_BCRYPT);
    }
    public static function TestPassword(#[\SensitiveParameter] string $password, string $hash) : bool
    {
        if (!isset($password) || !isset($hash))
            return false;
        return password_verify(password: $password, hash: $hash);
    }
    public static function IsFromApp() : bool
    {
        return isset($_COOKIE['AppVersion']) && !is_array(value: $_COOKIE['AppVersion']);
    }
    public static function RandomSubset(int $length, array $alphabet): string
    {
        $str = "";
        for ($i = 0; $i < $length; $i++)
        {
            $str .= $alphabet[random_int(min: 0, max: count(value: $alphabet) - 1)];
        }
        return $str;
    }

    public static function RandomPassword(int $length = 10) : string
    {
        return self::RandomSubset(
            length: $length, 
            alphabet: str_split(string: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!?/@;*+-$%&=^_"),
        );
    }

    public static function GetIpAddress(): string {
        
        $headers = getallheaders();
        if (array_key_exists(key: 'Cf-Connecting-Ip', array: $headers)) {
            // Cloudflare tunnel forwarding
            return $headers['Cf-Connecting-Ip'];
        }

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

    public static function Recaptcha3Validation(#[\SensitiveParameter] ?string $g_recaptcha_response): ?string
    {
        if (empty($g_recaptcha_response)) {
            return 'Variabile $g_recaptcha_response non impostata!';
        }

        $secret_key = self::LoadEnvironmentOfFromFile(var: 'RECAPTCHA_SECRET_KEY');
        if (empty($secret_key)) {
            return 'Google Recaptcha è abilitato, ma la chiave segreta non è impostata';
        }
        
        $client = new HttpClient();
        $response = $client->post(
            uri: 'https://www.google.com/recaptcha/api/siteverify',
            options: [
                'form_params' => [
                    'secret' => $secret_key,
                    'response' => $g_recaptcha_response,
                ]
            ]
        );
        if (!$response) {
            return 'Impossibile contattare il server di Google';
        }
        
        $body = $response->getBody();
        if (empty($body)) {
            return 'Risposta vuota dai server di Google';
        }
        
        $object = json_decode(json: $body);
        if (empty($object)) {
            return 'Risposta non valida dai server di Google';
        }
        
        if ($object->success) {
            return null;
        }
        return 'Token Recaptcha scaduto';
    }
}