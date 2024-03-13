<?php

class StaffBase
{
    public int $id = 0;
    public string $nome = "";
    public function __construct(string|int|null $id, string|null $nome)
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($nome) && is_string($nome))
            $this->nome = $nome;
    }
    public static function All(mysqli $connection) : array
    {
        if (!$connection)
            return array();
        $query  = "SELECT * FROM `staff_list_raw`";
        $result = $connection->query($query);
        $arr = array();
        if ($result)
        {
            while ($row = $result->fetch_assoc())
            {
                $staff = new StaffBase($row["staff"], $row["nome_completo"]);
                $arr[] = $staff;
            }
            $result->close();
        }
        return $arr;
    }
}

class Commissione
{
    public int $id = 0;
    public string $nome = "";
    public function __construct(string|int|null $id, string|null $nome)
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($nome) && is_string($nome))
            $this->nome = $nome;
    }
    public static function All(mysqli $connection) : array
    {
        if (!$connection)
            return array();
        $query = "SELECT id, nome FROM commissioni ORDER BY nome, id";
        $result = $connection->query($query);
        $arr = array();
        if ($result)
        {
            while ($row = $result->fetch_assoc())
            {
                $arr[] = new Commissione($row["id"], $row["nome"]);
            }
            $result->close();
        }
        return $arr;
    }
}

class Staff extends StaffBase
{
    public string $commissioni = "";
    public string $maglia = "";
    public string $parrocchia = "";
    public int $id_parrocchia = 0;
    public bool $is_referente = false;
    public string $cf = "";
    public function is_subscribed() : bool
    {
        return isset($this->maglia) && $this->maglia !== "Non scelta" && strlen(trim($this->maglia)) > 0;
    }
    public function valid_cf():bool
    {
        return isset($this->cf) && !is_array($this->cf) && strlen($this->cf);
    }
    public function is_in(string $commissione): bool
    {
        if (!$commissione || !$this->commissioni)
            return false;
        $comm = explode(",", $this->commissioni);
        for ($i = 0; $i < count($comm); $i++)
        {
            if (strtolower($comm[$i]) == strtolower($commissione))
                return true;
        }
        return false;
    }
    public static function Get(mysqli $connection, int $id) : Staff|null
    {
        if (!$connection || $id === 0)
        {
            return null;
        }
        $query = "CALL StaffData($id, YEAR(CURRENT_DATE))";
        $result = $connection->query($query);
        $data = null;
        if ($result)
        {
            if ($row = $result->fetch_assoc())
            {
                $data = new Staff($id, null);
                $data->commissioni = $row["commissioni"];
                $data->parrocchia = $row["parrocchia"];
                $data->id_parrocchia = (int)$row["id_parrocchia"];
                $data->maglia = $row["maglia"];
                $data->cf = $row["cf"];
                $data->is_referente = (bool)$row["referente"];
            }
            $result->close();
        }
        $connection->next_result();
        return $data;
    }
    public static function Create(mysqli $connection, int $id_anagrafica, int $user, int $parrocchia) : int
    {
        if (!$connection)
            return 0;
        $query = "INSERT INTO `staffisti` (`dati_anagrafici`, `id_utente`, `parrocchia`) VALUES ($id_anagrafica, $user, $parrocchia)";
        if (!$connection->query($query) || $connection->affected_rows !== 1)
            return 0;
        $result = $connection->query("SELECT LAST_INSERT_ID() AS id ");
        if (!$result)
        {
            return 0;
        }
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["id"]))
                return (int)$row["id"];
        }
        return 0;
    }
    public static function ChangeParrocchia(mysqli $connection, int $staff, int $parrocchia) : bool
    {
        if (!$connection || $staff === 0 || $parrocchia === 0)
            return false;
        $query = "UPDATE `staffisti` SET `parrocchia` = $parrocchia WHERE `id` = $staff";
        return (bool)$connection->query($query) && $connection->affected_rows === 1;
    }
    public static function Partecipa(
        mysqli $connection, 
        int $staff, 
        int $edizione, 
        string $maglia, 
        array $commissioni, 
        bool $is_referente = false) : bool
    {
        if (!$connection)
            return false;
        $maglia_sana = $connection->real_escape_string($maglia);
        $query = "CALL PartecipaStaff($staff, $edizione, '$maglia_sana', '";
        for ($i = 0; $i < count($commissioni); $i++)
        {
            $commissione = (int)$commissioni[$i];
            $query .= "$commissione";
            if ($i < count($commissioni) - 1)
            {
                $query .= ",";
            }
        }
        $query .= "', ";
        if ($is_referente) {
            $query .= "1";
        } else {
            $query .= "0";
        }
        $query .= ");";
        $result = (bool)$connection->query($query);
        $connection->next_result();
        return $result;
    }
}