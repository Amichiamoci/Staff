<?php
use lfkeitel\phptotp\{Base32,Totp};
class Token
{
    public string $val = "";
    public ?DateTime $expiration = null;
    private string $secret = "";
    public int $edition = 0;
    public int $anagrafica = 0;
    public bool $used = false;

    public function __construct(
        string|null $val,
        DateTime|string|null $expire,
        string|null $secret,
        string|int|null $edition,
        string|int|null $anagrafica,
        string|bool|null $used = false
    ) {
        if (isset($val) && is_string($val))
        {
            $this->val = $val;
        }

        if (isset($expire))
        {
            if ($expire instanceof DateTime) {
                $this->expiration = $expire;
            } else {
                $this->expiration = new DateTime($expire);
            }
        }

        if (isset($secret) && is_string($secret))
        {
            $this->secret = $secret;
        }

        if (isset($edition) && ctype_digit($edition))
        {
            $this->edition = (int)$edition;
        }

        if (isset($anagrafica) && ctype_digit($anagrafica))
        {
            $this->anagrafica = (int)$anagrafica;
        }

        if (isset($used))
        {
            $this->used = $used == 1 || (bool)$used;
        }
    }

    public static function Generate(
        mysqli $connection, 
        int $edizione, 
        int $anagrafica,
        string $expire) : Token|null
    {
        if (!$connection || $edizione === 0 || $anagrafica === 0)
            return null;
        $secret = Totp::GenerateSecret(16);
        $secret_s = Base32::encode($secret);
        $key = (new Totp())->GenerateToken($secret);
        $query = "REPLACE INTO `token` (val, secret, edizione, anagrafica, expire) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        if (!$stmt || !$stmt->bind_param("ssii", $key, $secret_s, $edizione, $anagrafica, $expire))
        {
            return null;
        }
        if (!$stmt->execute() || $stmt->affected_rows === 0)
        {
            return null;
        }
        return new Token($key, $expire, $secret_s, $edizione, $anagrafica);
    }

    public static function Load(mysqli $connection, string $val) : Token|null
    {
        if (!$connection || strlen($val) === 0)
        {
            return null;
        }
        $query = "SELECT * FROM token WHERE val = '" . $connection->real_escape_string($val) . "'";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new Token(
                $val, 
                $row["expire"], 
                $row["secret"], 
                $row["edizione"], 
                $row["anagrafica"],
                $row["used"]);
        }
        return null;
    }
    public static function LoadIfNotExpired(mysqli $connection, string $val) : Token|null
    {
        $loaded = self::Load($connection, $val);
        if (!isset($loaded) || $loaded->used)
            return null;
        if (isset($loaded->expiration) || $loaded->expiration < new DateTime())
        {
            return null;
        }
        return $loaded;
    }

    public function Expire(mysqli $connection) : bool
    {
        if (!$connection)
            return false;
        $query = "UPDATE `token` SET `token`.`used` = 1 WHERE `token`.`val` = ? AND `token`.`used` = 0";
        $stmt = $connection->prepare($query);
        if (!$stmt || !$stmt->bind_param("s", $this->val))
        {
            return false;
        }
        if ($stmt->execute() && $stmt->affected_rows === 1)
        {
            $this->used = true;
            return true;
        }
        return false;
    }
}