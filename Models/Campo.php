<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Campo extends NomeIdSemplice
{
    public static function Table(): string { return "campi"; }

    public ?float $Latitudine = null;
    public ?float $Longitudine = null;
    public ?string $Indirizzo = null;

    /** Lists fields with their addresses and optional coordinates, ordered by name. */
    public static function All(\mysqli $connection): array
    {
        $rows = $connection->query(
            'SELECT id, nome, indirizzo, ST_Y(posizione) AS latitudine, ST_X(posizione) AS longitudine
             FROM campi ORDER BY nome'
        );
        return array_map(static fn(array $row): Campo => new Campo(...$row), $rows->fetch_all(MYSQLI_ASSOC));
    }

    /** Loads a field with its address and optional coordinates, or null if the ID is unknown. */
    public static function ById(\mysqli $connection, int $id): ?self
    {
        $row = $connection->execute_query(
            'SELECT id, nome, indirizzo, ST_Y(posizione) AS latitudine, ST_X(posizione) AS longitudine
             FROM campi WHERE id = ?',
            [$id]
        )->fetch_assoc();
        return $row === null ? null : new Campo(...$row);
    }

    /** Creates or updates a field, storing a null position when the coordinates are omitted. */
    public static function Save(
        \mysqli $connection,
        ?int $id,
        string $nome,
        string $indirizzo,
        ?float $latitudine,
        ?float $longitudine,
    ): void {
        $values = [$nome, $indirizzo, $longitudine, $latitudine];
        if ($id === null) {
            $connection->execute_query(
                'INSERT INTO campi (nome, indirizzo, posizione) VALUES (?, ?, POINT(?, ?))',
                $values
            );
        } else {
            $connection->execute_query(
                'UPDATE campi SET nome = ?, indirizzo = ?, posizione = POINT(?, ?) WHERE id = ?',
                [...$values, $id]
            );
        }
    }

    public function __construct(
        int|string $id, 
        string $nome, 
        string|float|null $latitudine = null,
        string|float|null $longitudine = null,
        string|null $indirizzo = null,
    ) {
        parent::__construct(id: $id, nome: $nome);
        if (isset($latitudine))
        {
            $this->Latitudine = (float)$latitudine;
        }
        if (isset($longitudine))
        {
            $this->Longitudine = (float)$longitudine;
        }
        $this->Indirizzo = $indirizzo;
    }
}