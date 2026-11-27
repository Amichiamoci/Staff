<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Sport extends NomeIdSemplice
{
    public static function Table(): string { return "sport"; }

    public ?string $Area;

    public function __construct(string|int|null $id, ?string $nome, ?string $area = null)
    {
        parent::__construct($id, $nome);
        $this->Area = $area;
    }

    /** Lists sports with their stored areas. */
    public static function All(\mysqli $connection): array
    {
        $result = $connection->query('SELECT `id`, `nome`, `area`
            FROM `sport`
            ORDER BY `nome`');
        return $result ? array_map(self::FromRow(...), $result->fetch_all(MYSQLI_ASSOC)) : [];
    }

    /** Loads the sport and its stored area, or returns null for an unknown ID. */
    public static function ById(\mysqli $connection, int $id): ?self
    {
        $result = $connection->execute_query('SELECT `id`, `nome`, `area`
            FROM `sport`
            WHERE `id` = ?', [$id]);
        $row = $result ? $result->fetch_assoc() : null;
        return $row ? self::FromRow($row) : null;
    }

    /** Hydrates the fields stored in the sport table. */
    private static function FromRow(array $row): self
    {
        return new self($row['id'], $row['nome'], $row['area']);
    }

    /** Returns the emoji for the sport area, falling back to the name for partial models. */
    public function getIcon(): string
    {
        $name = strtolower($this->Area ?? $this->Nome);
        return match (true) {
            str_contains($name, 'pallavolo') => '🏐',
            str_contains($name, 'calcio') => '⚽',
            str_contains($name, 'basket') => '🏀',
            default => '',
        };
    }

    public function singleResult(): bool
    {
        return in_array(strtolower($this->Area), ['calcio', 'basket'], true);
    }
}
