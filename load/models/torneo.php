<?php

class Torneo
{
    public int $id = 0;
    public string $nome = "";
    public string $tipo = "";
    public int $id_tipo = 0;
    public string $sport = "";
    public int $id_sport = 0;
    public int $numero_squadre = 0;

    public function __construct(
        string|int|null $id,
        string|null $nome,
        string|null $tipo,
        string|int|null $id_tipo,
        string|null $sport,
        string|int|null $id_sport,
        string|int|null $numero_squadre
    )
    {
        if (isset($id))
            $id = (int)$id;
        if (isset($nome) && is_string($nome))
            $this->$nome = $nome;
        if (isset($tipo) && is_string($tipo))
            $this->$tipo = $tipo;
        if (isset($id_tipo))
            $id_tipo = (int)$id_tipo;
        if (isset($sport) && is_string($sport))
            $this->$sport = $sport;
        if (isset($id_sport))
            $id_sport = (int)$id_sport;
        if (isset($numero_squadre))
            $numero_squadre = (int)$numero_squadre;
    }

    public static function GetAll(mysqli $connection) : array
    {
        if (!$connection)
            return array();
        $query = "SELECT * FROM tornei_attivi";
        $result = $connection->query($query);
        if (!$result)
            return array();
        $arr = array();
        while ($row = $result->fetch_assoc())
        {
            $t = new Torneo(
                $row["id"],
                $row["nome"],

                $row["codice_sport"],
                $row["sport"],
                
                $row["tipo_id"],
                $row["tipo"],

                $row["numero_squadre"]
            );

            $arr[] = $t;
        }
        return $arr;
    }
    public static function Iscrivi(mysqli $connection, int $torneo, int $squadra) : bool
    {
        if (!$connection)
            return false;
        try {
            $query = "REPLACE INTO partecipaz_squad_torneo (torneo, squadra) VALUES ($torneo, $squadra)";
            return (bool)$connection->query($query);
        } catch (Exception $ex) {
            return false;
        }
    }
    public static function Create(mysqli $connection, int $sport, string $nome, int $tipo) : bool
    {
        if (!$connection)
            return false;
        $curr_ediz = Edizione::Current($connection)->id;
        $query = "INSERT INTO tornei (edizione, nome, sport, tipo_torneo) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        if (!$stmt)
            return false;
        if (!$stmt->bind_param("isii", $curr_ediz, $nome, $sport, $tipo))
            return false;
        return $stmt->execute() && $stmt->affected_rows === 1;
    }
    public static function GenerateCalendar(mysqli $connection, int $torneo) : bool
    {
        if (!$connection)
            return false;
        $query = "CALL CreaCalendario($torneo);";
        $result = $connection->query($query);
        $connection->next_result();
        return (bool)$result;   
    }
}