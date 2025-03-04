<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\NomeIdSemplice;

class TipoDocumento extends NomeIdSemplice
{
    public static function Table(): string { return 'tipi_documento'; }
    public static function All(\mysqli $connection) : array
    {
        if (!$connection) 
            return [];
        
        $result = $connection->query(
            query: "SELECT `id`, `label` FROM `tipi_documento` ORDER BY `id` ASC");
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $doc = new self(id: $row["id"], nome: $row["label"]);
            $arr[] = $doc;
        }
        return $arr;
    }
    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection) 
            return null;
        
        $result = $connection->query(
            query: "SELECT `label` FROM `tipi_documento` WHERE `id` = $id");
        if (!$result || $result->num_rows === 0)
            return null;

        if ($row = $result->fetch_assoc())
        {
            return new self(id: $id, nome: $row["label"]);
        }
        return null;
    }
}