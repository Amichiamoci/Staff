<?php

namespace Amichiamoci\Models\Api;

use Amichiamoci\Models\Templates\NomeIdSemplice;
use Amichiamoci\Utils\Security;

class Token extends NomeIdSemplice
{
    public static function Table(): string { return 'api_token'; }
    public string $Key;
    public function __construct(
        int|string|null $id, 
        string|null $nome,
        string $key,
    ) {
        parent::__construct(id: $id, nome: $nome);
        $this->Key = $key;
    }

    /** Lists API credentials for the administration page. */
    public static function All(\mysqli $connection): array
    {
        if (!$connection)
            return [];

        $result = $connection->query(query: 'SELECT * FROM `api_token`');
        if (!$result || $result->num_rows === 0)
        {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                id: $row['id'],
                nome: $row['name'],
                key: $row['key'],
            );
        }
        return $arr;
    }

    /** Returns the verified token ID and records request activity, or returns null for a rejected credential. */
    public static function Authenticate(\mysqli $connection, #[\SensitiveParameter] string $key, string $ip): ?int
    {
        if ($key === '' || strlen($key) > 128) {
            return null;
        }

        $row = $connection->execute_query(
            'SELECT `id`
             FROM `api_token`
             WHERE `key` = ? AND BINARY `key` = BINARY ?',
            [$key, $key],
        )->fetch_row();
        if ($row === null) {
            return null;
        }

        // The usage table stores IPv4-sized labels.
        $address = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? $ip : 'other';
        $connection->execute_query(
            'INSERT INTO `api_usage` (`token_id`, `ip_address`, `hits`)
             SELECT `id`, ?, 1
             FROM `api_token`
             WHERE `id` = ?
             ON DUPLICATE KEY UPDATE `hits` = `hits` + 1, `last_usage` = CURRENT_TIMESTAMP',
            [$address, $row[0]],
        );
        return $connection->affected_rows > 0 ? (int)$row[0] : null;
    }

    /** Generates a new named API credential and returns it to the caller. */
    public static function New(\mysqli $connection, string $name): ?self
    {
        if (!$connection)
            return null;

        $token = Security::RandomSubset(
            length: 64, 
            alphabet: str_split(string: Security::LETTERS . Security::DIGITS),
        );
        $query = "INSERT INTO `api_token` (`name`, `key`)
            VALUES (?, ?)";

        $result = $connection->execute_query(query: $query, params: [$name, $token]);
        if (!$result || $connection->affected_rows !== 1)
        {
            return null;
        }

        return new self(
            id: $connection->insert_id,
            nome: $name,
            key: $token
        );
    }

    /** Revokes an API credential by ID. */
    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(
            query: 'DELETE
                FROM `api_token`
                WHERE `id` = ?',
            params: [$id],
        );
        return (bool)$result && $connection->affected_rows > 0;
    }
}