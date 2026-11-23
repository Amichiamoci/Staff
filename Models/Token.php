<?php

namespace Amichiamoci\Models;

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

    /** Checks whether the reset token has a recorded use time. */
    public function IsUsed(): bool { return isset($this->UsageDate); }

    /** Checks whether the reset token has passed its expiration time. */
    public function IsExpired(): bool { return (new \DateTime()) >= $this->ExpirationDate; }

    /** Marks a reset token as used in the database. */
    public function Use(\mysqli $connection): bool
    {
        if (!$connection)
            return false;

        $query = "UPDATE `token` SET `usage_date` = CURRENT_TIMESTAMP WHERE `value` = ? AND `usage_date` IS NULL AND `expiration_date` > CURRENT_TIMESTAMP";

        $result = $connection->execute_query(
            query: $query, 
            params: [$this->Value],
        );

        if (!$result || $connection->affected_rows !== 1)
            return false;

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

    /** Loads a reset token and its account metadata by its stored value. */
    public static function Load(\mysqli $connection, string $value): ?self
    {
        if (!$connection)
            return null;

        $result = $connection->execute_query(
            query: 'SELECT * FROM `token` WHERE `value` = ? LIMIT 1', 
            params: [ $value ],
        );

        if (!$result || $result->num_rows !== 1)
            return null;
        
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

    /** Derives the database lookup value from a reset secret. */
    private static function GenerateValueFromSecret(string $secret): string
    {
        return hash(algo: 'sha256', data: $secret);
    }

    /** Verifies the provided reset secret against the stored token value. */
    public function Matches(string $secret): bool
    {
        return hash_equals($this->Value, self::GenerateValueFromSecret($secret));
    }

    /** Creates a reset token and returns the data needed to deliver it. */
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
        $secret = bin2hex(random_bytes(32));
        $value = self::GenerateValueFromSecret(secret: $secret);

        // Upload to DB
        $query = 
            "INSERT INTO `token` (`value`, `secret`, `expiration_date`, `user_id`, `email`, `requesting_ip`, `requesting_browser`) " .
            "VALUES (?, '', CURRENT_TIMESTAMP + INTERVAL ? MINUTE, ?, ?, ?, ?)";
        $result = $connection->execute_query(
            query: $query, 
            params: [
                $value,
                $duration_mins,
                $user_id,
                $email,
                strlen($requesting_ip ?? '') <= 15 ? $requesting_ip : null,
                $requesting_browser === null ? null : mb_scrub(substr($requesting_browser, 0, 128), 'UTF-8'),
            ]
        );

        if (!$result || $connection->affected_rows !== 1) 
            return null;
        
        $token = self::Load(connection: $connection, value: $value);
        if ($token !== null) {
            $token->Secret = $secret;
        }
        return $token;
    }

}