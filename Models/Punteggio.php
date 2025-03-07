<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\DbEntity;

class Punteggio implements DbEntity
{
    public int $Id;
    public int $Partita;
    public string $Casa;
    public string $Ospiti;

    public function __toString(): string
    {
        return "$this->Casa - $this->Ospiti";
    }

    public function __construct(
        string|int $id,
        string|int $partita,
        string $casa,
        string $ospiti,
    ) {
        $this->Id = (int)$id;
        $this->Partita = (int)$partita;
        $this->Casa = $casa;
        $this->Ospiti = $ospiti;
    }

    public static function Decompress(
        string|array|null $id,
        string|array|null $casa,
        string|array|null $ospiti,

        string|int $partita,
    ): array {
        if (!isset($id) || !isset($casa) || !isset($ospiti))
        {
            return [];
        }

        if (is_string(value: $id))
        {
            $id = explode(separator: '|', string: $id);
            if (!$id)
            {
                return [];
            }
        }
        if (is_string(value: $casa))
        {
            $casa = explode(separator: '|', string: $casa);
            if (!$casa)
            {
                return [];
            }
        }
        if (is_string(value: $ospiti))
        {
            $ospiti = explode(separator: '|', string: $ospiti);
            if (!$ospiti)
            {
                return [];
            }
        }

        if (count(value: $id) !== count(value: $casa) || count(value: $id) !== count(value: $ospiti))
        {
            throw new \LengthException(message: 'Numero di punteggi non allineato');
        }
        
        $arr = [];
        for ($i = 0; $i < count(value: $id); $i++)
        {
            $arr[] = new self(
                id: trim(string: $id[$i]),
                partita: $partita,
                casa: trim(string: $casa[$i]),
                ospiti: trim(string: $ospiti[$i]),
            );
        }
        return $arr;
    }

    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;

        $result = $connection->query(query: "SELECT * FROM `punteggi` WHERE `id` = $id");
        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            return null;
        }

        return new self(
            id: $row['id'],
            partita: $row['partita'],
            casa: $row['casa'],
            ospiti: $row['ospiti'],
        );
    }

    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection)
            return false;

        $result = $connection->query(query: "DELETE FROM `punteggi` WHERE `id` = $id");
        return (bool)$result && $connection->affected_rows === 1;
    }

    public static function Edit(\mysqli $connection, int $id, string $casa, string $ospiti): bool
    {
        if (!$connection)
            return false;

        $result = $connection->execute_query(
            query: 'UPDATE `punteggi` SET `home` = ?, `guest`= ? WHERE `id` = ?', 
            params: [
                $casa, 
                $ospiti, 
                $id
            ]
        );

        return (bool)$result && $connection->affected_rows === 1;
    }

    public static function All(\mysqli $connection): array
    {
        return [];
    }
}