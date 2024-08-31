<?php

class Squadra
{
    public int $id = 0;
    public string $nome = "";
    
    public string $membri = "";
    public string $id_iscr_membri = "";

    public string $parrocchia = "";
    public int $id_parrocchia = 0;
    
    public string $sport = "";
    public int $id_sport = 0;

    public function __construct(
        string|int|null $id,
        string|null $nome,
        string|null $membri,
        string|null $iscrizione_membri,
        string|null $parrocchia,
        string|int|null $id_parrocchia,
        string|null $sport,
        string|int|null $id_sport
    )
    {
        if (isset($id))
            $this->id = (int)$id;
        
        if (isset($nome) && is_string($nome))
            $this->nome = $nome;
        
        if (isset($membri) && is_string($membri))
            $this->membri = $membri;
        
        if (isset($iscrizione_membri) && is_string($iscrizione_membri))
            $this->id_iscr_membri = $iscrizione_membri;
        
        if (isset($parrocchia) && is_string($parrocchia))
            $this->parrocchia = $parrocchia;
        
        if (isset($id_parrocchia))
            $this->id_parrocchia = (int)$id_parrocchia;

        if (isset($sport) && is_string($sport))
            $this->sport = $sport;
        
        if (isset($id_sport))
            $this->id_sport = (int)$id_sport;
    }
    
    public static function List(mysqli $connection, string|int|null $year = null, string|int|null $sport = null) : array
    {
        if (!$connection)
            return array();
        $y = (!isset($year) || (int)$year === 0) ? "NULL" : (int)$year;
        $s = (!isset($sport) || (int)$sport === 0) ? "NULL" : (int)$sport;
        $query = "CALL SquadreList($y, $s);";
        $result = $connection->query($query);
        if (!$result)
        {
            $connection->next_result();
            return array();
        }
        $arr = array();
        
        while ($row = $result->fetch_assoc())
        {
            $s = new Squadra(
                $row["id_squadra"], 
                $row["nome"],
                $row["lista_membri"],
                null,
                $row["parrocchia"],
                $row["id_parrocchia"],
                $row["nome_sport"],
                $row["id_sport"]);
            $arr[] = $s;
        }
        $result->close();
        $connection->next_result();
        return $arr;
    }
    public static function NomeFromId(mysqli $connection, int $id) : string
    {
        if (!$connection || $id === 0)
            return "";
        $query = "SELECT nome FROM squadre WHERE id = $id";
        $result = $connection->query($query);
        $ret = "";
        if ($result)
        {
            if ($row = $result->fetch_assoc())
            {
                if (isset($row["nome"]))
                {
                    $ret = $row["nome"];
                }
            }
            $result->close();
        }
        return $ret;
    }
    public static function Delete(mysqli $connection, int $id) : bool
    {
        if (!$connection)
            return false;
        $query = "CALL CancellaSquadra($id);";
        $result = $connection->query($query);
        $connection->next_result();
        return (bool)$result && $connection->affected_rows === 1;
    }
    public static function Load(mysqli $connection, int $id) : Squadra|null
    {
        if (!$connection || $id === 0)
        {
            return null;
        }
        $query = "CALL GetSquadra($id);";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0)
        {
            $connection->next_result();
            return null;
        }
        $squadra = null;
        if ($row = $result->fetch_assoc())
        {
            $squadra = new Squadra(
                $row["id"],
                $row["nome"],
                $row["membri"],
                $row["id_iscr_membri"],
                $row["parrocchia"],
                $row["id_parrocchia"],
                $row["sport"],
                $row["id_sport"]);
        }
        $connection->next_result();
        return $squadra;
    }
    public static function Create(
        mysqli $connection, 
        string $nome, 
        int $parrocchia, 
        int $sport, 
        string $membri, 
        int $edizione) : bool
    {
        if (!$connection || $edizione === 0)
            return false;
        $nome_sano = $connection->real_escape_string($nome);
        $membri_sano = $connection->real_escape_string($membri);
        $query = "CALL CreaSquadra('$nome_sano', $parrocchia, $sport, '$membri_sano', $edizione)";
        $result = $connection->query($query);
        if (!$result)
        {
            return false;
        }
        $ret = false;
        if ($row = $result->fetch_assoc())
        {
            $ret = isset($row["id"]) && $row["id"] != "0";
        }
        $result->close();
        $connection->next_result();
        return $ret;
    }
    public static function Edit(
        mysqli $connection, 
        int $id,
        string $nome, 
        int $parrocchia,
        int $sport,
        string $membri) : bool
    {
        if (!$connection || $id === 0)
            return false;
        $nome_sano = $connection->real_escape_string($nome);
        $membri_sano = $connection->real_escape_string($membri);
        $query = "CALL ModificaSquadra($id, '$nome_sano', $parrocchia, $sport, '$membri_sano')";
        $result = $connection->query($query);
        if (!$result)
        {
            return false;
        }
        $ret = false;
        if ($row = $result->fetch_assoc())
        {
            $ret = isset($row["Result"]) && $row["Result"] != "0";
        }
        $result->close();
        $connection->next_result();
        return $ret;
    }
}