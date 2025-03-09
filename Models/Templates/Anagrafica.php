<?php

namespace Amichiamoci\Models\Templates;

class Anagrafica
{
    public int $Id;
    public string $Nome;
    public string $Cognome;
    public int $Eta = 0;

    public function __construct(
        string|int $id,
        string $nome,
        string $cognome,
        string|int|null $eta
    )
    {
        $this->Id = (int)$id;
        $this->Nome = $nome;
        $this->Cognome = $cognome;
        if (isset($eta))
            $this->Eta = (int)$eta;
    }

    public static function All(\mysqli $connection, ?callable $filter = null) : array
    {
        if (!$connection)
            return [];
        $query = "SELECT `id`, `nome`, `cognome`, Eta(`data_nascita`) AS \"eta\" 
        FROM `anagrafiche`
        ORDER BY `cognome`, `nome` ASC";
        $result = $connection->query(query: $query);
        if (!$result)
            return [];
        
        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $anagrafica = new self(
                id: $row["id"],
                nome: $row["nome"],
                cognome: $row["cognome"],
                eta: $row["eta"]
            );
            $arr[] = $anagrafica;
        }
        if (!is_null(value: $filter))
            $arr = array_filter(array: $arr, callback: $filter);
        return $arr;
    }

    public static function Table(): string { return "anagrafiche"; }

    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;
        
        $query = "SELECT `id`, `nome`, `cognome`, Eta(`data_nascita`) AS \"eta\" 
        FROM `anagrafiche`
        WHERE `id` = $id
        LIMIT 1";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
            return null;
        
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"],
                nome: $row["nome"],
                cognome: $row["cognome"],
                eta: $row["eta"]
            );
        }
        return null;
    }

    public static function NomeDaId(\mysqli $connection, int $id) : string
    {
        if (!$connection)
            return "";
        $query = "CALL NomeDaAnagrafica($id)";
        $result = $connection->query(query: $query);
        if (!$result)
        {
            $connection->next_result();
            return "";
        }
        if ($row = $result->fetch_assoc())
        {
            $result->close();
            $connection->next_result();
            return $row["nome_completo"];
        }
        $result->close();
        return "";
    }

    public static function NomiCompleannati(\mysqli $connection): array
    {
        if (!$connection)
            return [];

        $result = $connection->query('SELECT `nome`, `cognome`, `eta` FROM `compleanni_oggi` ORDER BY `eta` DESC');
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        return array_map(
            callback: function (array $row): string {
                return $row['nome'] . ' ' . $row['cognome']. ' (' . $row['eta'] . ')';
            },
            array: $result->fetch_all(MYSQLI_ASSOC),
        );
    }
}