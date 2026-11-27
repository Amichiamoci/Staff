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

    /** Expands the aggregated score fields into individual score objects for one match. */
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

    /** Finds an individual score by ID. */
    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;

        $result = $connection->query(query: "SELECT *
            FROM `punteggi`
            WHERE `id` = $id");
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

    /** Removes a score without deleting its match. */
    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection)
            return false;

        $result = $connection->query(query: "DELETE r
            FROM `punteggi` r
                INNER JOIN `partite` p ON p.`id` = r.`partita`
            WHERE r.`id` = $id AND p.`a_tavolino` IS NULL");
        return (bool)$result && $connection->affected_rows === 1;
    }

    /** Updates the home and guest values for a score row. */
    public static function Edit(\mysqli $connection, int $id, string $casa, string $ospiti): bool
    {
        if (!$connection)
            return false;

        $result = $connection->execute_query(
            query: 'UPDATE `punteggi` r
                    INNER JOIN `partite` p ON p.`id` = r.`partita`
                SET r.`home` = ?, r.`guest` = ?
                WHERE r.`id` = ? AND p.`a_tavolino` IS NULL',
            params: [
                $casa, 
                $ospiti, 
                $id
            ]
        );

        return (bool)$result && $connection->affected_rows === 1;
    }

    /**
     * Saves the score at a zero-based board position if the row and its previous values still match.
     * Returns the saved row ID, including on a retry, or null when the board conflicts with stored scores.
     */
    public static function SalvaTabellone(
        \mysqli $connection,
        int $match,
        int $position,
        ?int $scoreId,
        ?array $previous,
        array $score,
    ): ?int {
        $connection->begin_transaction();
        $committed = false;
        try {
            $locked = $connection->execute_query(
                'SELECT `a_tavolino`, `torneo`
                FROM `partite`
                WHERE `id` = ?
                FOR UPDATE',
                [$match],
            )->fetch_assoc();
            if ($locked === null || $locked['a_tavolino'] !== null) {
                return null;
            }
            if ($position > 0) {
                $sport = $connection->execute_query(
                    'SELECT `area_sport`
                    FROM `tornei_sport`
                    WHERE `id` = ?',
                    [$locked['torneo']],
                )->fetch_column();
                if ($sport !== 'Pallavolo') {
                    return null;
                }
            }

            $rows = $connection->execute_query(
                'SELECT `id`, `home`, `guest`
                FROM `punteggi`
                WHERE `partita` = ?
                ORDER BY `id`
                FOR UPDATE',
                [$match],
            );
            if ($position > $rows->num_rows) {
                return null;
            }
            $row = $position < $rows->num_rows && $rows->data_seek($position) ? $rows->fetch_assoc() : null;
            $currentId = $row === null ? null : (int)$row['id'];
            if ($scoreId !== null && $scoreId !== $currentId) {
                return null;
            }
            $current = $row === null ? null : [trim($row['home']), trim($row['guest'])];
            if ($current === $score) {
                return $currentId;
            }
            if ($current !== $previous) {
                return null;
            }
            if ($row === null) {
                $connection->execute_query(
                    'INSERT INTO `punteggi` (`partita`, `home`, `guest`) VALUES (?, ?, ?)',
                    [$match, ...$score],
                );
                $currentId = (int)$connection->insert_id;
            } elseif (!self::Edit($connection, $currentId, ...$score)) {
                return null;
            }
            $committed = $connection->commit();
            return $committed ? $currentId : null;
        } finally {
            if (!$committed) {
                $connection->rollback();
            }
        }
    }

    /** Provides the model interface’s currently unsupported unfiltered list as an empty array. */
    public static function All(\mysqli $connection): array
    {
        return [];
    }
}