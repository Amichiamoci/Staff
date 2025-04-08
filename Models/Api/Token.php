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

    public static function Test(\mysqli $connection, string $key, string $ip): bool
    {
        if (!$connection)
            return false;

        $result = $connection->execute_query(
            query: 'CALL `ApiCallValidate`(?, ?);', 
            params: [$key, $ip],
        );
        if (!$result || $result->num_rows === 0)
        {
            $connection->next_result();
            return false;
        }

        $row = $result->fetch_assoc();
        if (!$row || !array_key_exists(key: 'id', array: $row) || !isset($row['id']))
        {
            $connection->next_result();
            return false;
        }

        $connection->next_result();
        return true;
    }

    public static function New(\mysqli $connection, string $name): ?self
    {
        if (!$connection)
            return null;

        $token = Security::RandomSubset(
            length: 64, 
            alphabet: str_split(string: Security::LETTERS . Security::DIGITS),
        );
        $query = "INSERT INTO `api_token` (`name`, `key`) VALUES (?, ?)";

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

    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(
            query: 'DELETE FROM `api_token` WHERE `id` = ?', 
            params: [$id],
        );
        return (bool)$result && $connection->affected_rows > 0;
    }
}