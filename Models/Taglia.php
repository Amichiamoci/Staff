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

    public static function All(): array {
        return array_column(array: self::cases(), column_key: 'value');
    }

    public static function Valid(string $s) : bool {
        return in_array(needle: $s, haystack: self::All());
    }

    public static function List(\mysqli $connection, int $year): array
    {
        if (!$connection)
            return [];

        $result = $connection->query("CALL `ListaMaglie`($year, FALSE);");
        if (!$result) {
            $connection->next_result();
            return [];
        }

        $all = $result->fetch_all(MYSQLI_ASSOC);
        $connection->next_result();

        return $all;
    }

    public static function Grouped(\mysqli $connection, int $year): array
    {
        if (!$connection)
            return [];

        $result = $connection->query("CALL `ListaMaglie`($year, TRUE);");
        if (!$result) {
            $connection->next_result();
            return [];
        }

        $all = $result->fetch_all(MYSQLI_ASSOC);
        $connection->next_result();

        return $all;
    }
}