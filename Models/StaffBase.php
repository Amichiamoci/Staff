<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class StaffBase extends NomeIdSemplice
{
    public static function Table(): string { return "staff_list_raw"; }
    
    public static function All(\mysqli $connection) : array
    {
        if (!$connection)
            return [];
        $query  = "SELECT * FROM `staff_list_raw`";
        $result = $connection->query(query: $query);
        $arr = [];
        if (!$result)
        {
            return [];
        }
        while ($row = $result->fetch_assoc())
        {
            $staff = new self(id: $row["staff"], nome: $row["nome_completo"]);
            $arr[] = $staff;
        }
        $result->close();
        return $arr;
    }

    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id <= 0)
            return null;
        
        $table = self::Table();
        $result = $connection->query(query: "SELECT `nome_completo` FROM `$table` WHERE `staff` = $id");

        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new self(id: $id, nome: $row["nome_completo"]);
        }
        return null;
    }
}