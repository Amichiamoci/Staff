<?php

namespace Amichiamoci\Models;

use Amichiamoci\Utils\Security;
use lfkeitel\phptotp\{Base32,Totp};
class Token
{
    public string $Value;
    public string $Secret;
    public \DateTime $GenerationDate;
    public \DateTime $ExpirationDate;
    public ?\DateTime $UsageDate = null;
    public int $UserId;
    public string $Email;
    public ?string $RequestingIp = null;
    public ?string $RequestingBrowser = null;

    public function IsUsed(): bool { return isset($this->UsageDate); }
    public function IsExpired(): bool { return (new \DateTime()) > $this->ExpirationDate; }

    public function Use(\mysqli $connection): bool
    {
        if (!$connection)
            return false;
        $query = "UPDATE `token` SET `usage_date` = CURRENT_TIMESTAMP WHERE `value` = ?";

        $result = $connection->execute_query(query: $query, params: [$this->Value]);
        if (!$result || $connection->affected_rows !== 1) {
            return false;
        }

        $this->UsageDate = new \DateTime();
        return true;
    }

    public function __construct(
        string $value,
        string $secret,
        string|\DateTime $generation_date,
        string|\DateTime $expiration_date,
        string|\DateTime|null $usage_date,
        string|int $user_id,
        string $email,
        ?string $requesting_ip = null,
        ?string $requesting_browser = null,
    ) {
        $this->Value = $value;
        $this->Secret = $secret;

        if ($generation_date instanceof \DateTime) {
            $this->GenerationDate = $generation_date;
        } else {
            $this->GenerationDate = new \DateTime(datetime: $generation_date);
        }
        if ($expiration_date instanceof \DateTime) {
            $this->ExpirationDate = $expiration_date;
        } else {
            $this->ExpirationDate = new \DateTime(datetime: $expiration_date);
        }
        if (isset($usage_date))
        {
            if ($usage_date instanceof \DateTime) {
                $this->UsageDate = $usage_date;
            } else {
                $this->UsageDate = new \DateTime(datetime: $usage_date);
            }
        }

        $this->UserId = (int)$user_id;
        $this->Email = $email;
        $this->RequestingIp = $requesting_ip;
        $this->RequestingBrowser = $requesting_browser;
    }

    public static function Load(\mysqli $connection, string $value): ?self
    {
        if (!$connection)
            return null;
        $query = "SELECT * FROM `token` WHERE `value` = ? LIMIT 1";
        $result = $connection->execute_query(query: $query, params: [$value]);
        if (!$result || $result->num_rows !== 1) {
            return null;
        }
        $row = $result->fetch_assoc();
        return new self(
            value: $row['value'],
            secret: $row['secret'],
            generation_date: $row['generation_date'],
            expiration_date: $row['expiration_date'],
            usage_date: $row['usage_date'],
            user_id: $row['user_id'],
            email: $row['email'],
            requesting_ip: $row['requesting_ip'],
            requesting_browser: $row['requesting_browser'],
        );
    }

    private static function GenerateValueFromSecret(string $secret): string
    {
        return hash(algo: 'sha256', data: $secret);
    }
    public function Matches(string $secret): bool
    {
        return 
            $secret === $this->Secret &&
            $this->Value === self::GenerateValueFromSecret(secret: $this->Secret);
    }

    public static function Generate(
        \mysqli $connection, 
        int $duration_mins,
        string|int $user_id,
        string $email,
        ?string $requesting_ip = null,
        ?string $requesting_browser = null,
    ): ?self
    {
        if (!$connection)
            return null;

        // Generate secret and its hash (value)
        $secret = Security::RandomSubset(
            length: 8, 
            alphabet: str_split(string: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'));
        $value = self::GenerateValueFromSecret(secret: $secret);

        // Upload to DB
        $query = 
            "REPLACE INTO `token` (`value`, `secret`, `expiration_date`, `user_id`, `email`, `requesting_ip`, `requesting_browser`) " .
            "VALUES (?, ?, CURRENT_TIMESTAMP + INTERVAL $duration_mins MINUTE, ?, ?, ?, ?)";
        $result = $connection->execute_query(
            query: $query, 
            params: [$value, $secret, $user_id, $email, $requesting_ip, $requesting_browser]
        );

        if (!$result || $connection->affected_rows !== 1) {
            return null;
        }
        return self::Load(connection: $connection, value: $value);
    }

}