<?php
class Sport
{
    public int $id = 0;
    public string $label = "";

    public function __construct(string|int|null $id, string|null $label)
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($label) && is_string($label))
            $this->label = $label;
    }

    public static function GetAll(mysqli $connection) : array
    {
        $query = "SELECT id, nome FROM sport ORDER BY nome";
        $arr  = array();
        $result = $connection->query($query);
        if ($result)
        {
            while ($row = $result->fetch_assoc())
            {
                $sport = new Sport($row["id"], $row["nome"]);
                $arr[] = $sport;
            }
        }
        return $arr;
    }
    public static function Load(mysqli $connection, int $id) : Sport|null
    {
        if (!$connection || $id <= 0)
            return null;
        $query  = "SELECT nome FROM sport WHERE id = $id";

        $result = $connection->query($query);

        if (!$result)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new Sport($id, $row["nome"]);
        }
        return null;
    }
}

class TipoTorneo
{
    public int $id = 0;
    public string $label = "";

    public function __construct(string|int|null $id, string|null $label)
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($label) && is_string($label))
            $this->label = $label;
    }

    public static function GetAll(mysqli $connection) : array
    {
        $query = "SELECT id, nome FROM tipi_torneo ORDER BY nome";
        $arr  = array();
        $result = $connection->query($query);
        if ($result)
        {
            while ($row = $result->fetch_assoc())
            {
                $sport = new TipoTorneo($row["id"], $row["nome"]);
                $arr[] = $sport;
            }
        }
        return $arr;
    }
    public static function Load(mysqli $connection, int $id) : TipoTorneo|null
    {
        if (!$connection || $id <= 0)
            return null;
        $query  = "SELECT nome FROM tipi_torneo WHERE id = $id";

        $result = $connection->query($query);

        if (!$result)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new TipoTorneo($id, $row["nome"]);
        }
        return null;
    }
}