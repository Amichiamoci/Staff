<?php

namespace Amichiamoci\Models\Templates;

abstract class NomeIdSemplice implements DbEntity
{
    public int $Id = 0;
    public string $Nome = "";
    abstract protected static function Table(): string;
    public function __construct(
        string|int|null $id,
        string|null $nome
    ) {
        if (isset($id))
            $this->Id = (int)$id;
        if (isset($nome) && is_string(value: $nome))
            $this->Nome = $nome;
    }
    public static function All(\mysqli $connection) : array
    {  
        if (!$connection)
            return [];

        $class_name = get_called_class();
        $table = $class_name::Table();
        $result = $connection->query(query: "SELECT `id`, `nome` FROM `$table` ORDER BY `nome`");

        if (!$result)
        {
            return [];
        }
        
        $list = [];
        while ($row = $result->fetch_assoc())
        {
            $curr = new $class_name(
                id: $row["id"], 
                nome: $row["nome"]
            );
            $list[] = $curr;
        }
        return $list;
    }

    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;
        
        $class_name = get_called_class();
        $table = $class_name::Table();
        if (empty($table)) { return null; }
        $result = $connection->query(query: "SELECT `nome` FROM `$table` WHERE `id` = $id");

        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new $class_name(id: $id, nome: $row["nome"]);
        }
        return null;
    }
}