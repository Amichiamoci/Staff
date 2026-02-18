<?php
namespace Amichiamoci\Models;
enum Taglia: string
{
    case XXS = 'XXS';
    case XS = 'XS';
    case S = 'S';
    case M = 'M';
    case L = 'L';
    case XL = 'XL';
    case XXL = 'XXL';
    case XXXL = '3XL';

    /*
    public function __toString(): string {
        return $this->value;
    }
    */

    public static function All(): array
    {
        return array_column(array: self::cases(), column_key: 'value');
    }

    public static function Valid(string $s): bool
    {
        return in_array(needle: $s, haystack: self::All());
    }

    public static function List(
        \mysqli $connection, 
        int $year, 
        bool $group = false,
    ): array
    {
        if (!$connection)
            return [];

        $result = $connection->execute_query(
            query: "CALL `ListaMaglie`(?, ?)",
            params: [$year, $group ? 1 : 0],
        );
        if (!$result)
        {
            $connection->next_result();
            return [];
        }

        $all = $result->fetch_all(mode: MYSQLI_ASSOC);
        $connection->next_result();

        return $all;
    }

}