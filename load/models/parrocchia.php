<?php

class Parrocchia
{
    public int $id = 0;
    public string $nome = "";
    public function __construct(
        string|int|null $id,
        string|null $nome
    )
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($nome) && is_string($nome))
            $this->nome = $nome;
    }
    public static function GetAll(mysqli $connection) : array
    {  
        if (!$connection)
            return array();
        $query  = "SELECT id, nome FROM parrocchie";

        $result = $connection->query($query);

        if (!$result)
        {
            return array();
        }
        $parr = array();
        while ($row = $result->fetch_assoc())
        {
            $curr = new Parrocchia($row["id"], $row["nome"]);
            $parr[] = $curr;
        }
        return $parr;
    }

    public static function Load(mysqli $connection, int $id) : Parrocchia|null
    {
        if (!$connection || $id <= 0)
            return null;
        $query  = "SELECT nome FROM parrocchie WHERE id = $id";

        $result = $connection->query($query);

        if (!$result)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new Parrocchia($id, $row["nome"]);
        }
        return null;
    }
    public static function LoadByUerId(mysqli $connection, int $user) : Parrocchia|null
    {
        if (!$connection || $user <= 0)
            return null;
        $query  = "SELECT *
        FROM parrocchie
            INNER JOIN staffisti ON staffisti.parrocchia = parrocchie.id
        WHERE staffisti.id_utente = $user
        LIMIT 1";

        $result = $connection->query($query);

        if (!$result)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new Parrocchia($row["id"], $row["nome"]);
        }
        return null;
    }
}