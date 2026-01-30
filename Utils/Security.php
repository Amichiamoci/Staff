<?php
namespace Amichiamoci\Utils;

use GuzzleHttp\Client as HttpClient;
use Richie314\SimpleMvc\Utils\Security as BaseSecurity;

class Security extends BaseSecurity
{
    public static function ApiEnabled(): bool
    {
        $enable_api = self::LoadEnvironmentOfFromFile(var: 'ENABLE_API');
        return is_string(value: $enable_api) && (bool)$enable_api;
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

    public const LETTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const DIGITS = '0123456789';
    public const SYMBOLS = '!?/@;*+-$%&=^_';

    public static function RandomPassword(int $length = 10) : string
    {
        return self::RandomSubset(
            length: $length, 
            alphabet: str_split(string: self::LETTERS . self::DIGITS . self::SYMBOLS),
        );
    }


    public static function Recaptcha3Validation(
        #[\SensitiveParameter] ?string $g_recaptcha_response,
    ): ?string
    {
        if (empty($g_recaptcha_response))
            return 'Variabile $g_recaptcha_response non impostata!';

        $secret_key = self::LoadEnvironmentOfFromFile(var: 'RECAPTCHA_SECRET_KEY');
        if (empty($secret_key)) 
            return 'Google Recaptcha è abilitato, ma la chiave segreta non è impostata';
        
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
        if (!$response) 
            return 'Impossibile contattare il server di Google';
        
        $body = $response->getBody();
        if (empty($body))
            return 'Risposta vuota dai server di Google';
        
        $object = json_decode(json: $body);
        if (empty($object))
            return 'Risposta non valida dai server di Google';
        
        if ($object->success)
            return null;
        return 'Token Recaptcha scaduto';
    }
}