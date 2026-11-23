<?php
namespace Amichiamoci\Utils;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;

use Richie314\SimpleMvc\Utils\Security as BaseSecurity;

class Security
extends BaseSecurity
{
    public static function ApiEnabled(): bool
    {
        $enable_api = parent::LoadEnvironmentOrFromFile(var: 'ENABLE_API');
        return filter_var($enable_api, FILTER_VALIDATE_BOOL);
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
        string $action,
    ): ?string
    {
        if (empty($g_recaptcha_response))
            return 'Variabile $g_recaptcha_response non impostata!';

        $secret_key = self::LoadEnvironmentOrFromFile(var: 'RECAPTCHA_SECRET_KEY');
        if (empty($secret_key)) 
            return 'Google Recaptcha è abilitato, ma la chiave segreta non è impostata';
        
        try {
            $response = (new HttpClient())->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'timeout' => 5,
                    'form_params' => [
                        'secret' => $secret_key,
                        'response' => $g_recaptcha_response,
                    ],
                ],
            );
        } catch (GuzzleException) {
            return 'Impossibile contattare il server di Google';
        }

        $result = json_decode((string)$response->getBody(), true);
        if (($result['success'] ?? false) === true
            && ($result['action'] ?? null) === $action
            && is_numeric($result['score'] ?? null)
            && $result['score'] >= 0.5
        ) {
            return null;
        }
        return 'Verifica reCAPTCHA non riuscita';
    }
}