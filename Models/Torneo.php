<?php
namespace Amichiamoci\Models;

class Torneo
{
    public int $Id;
    public string $Nome;
    public TipoTorneo $Tipo;
    public Sport $Sport;
    public int $NumeroSquadre = 0;

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
        $this->Id = (int)$id;
        $this->Nome = $nome;
        $this->Tipo = new TipoTorneo(id: $id_tipo, nome: $tipo);
        $this->Sport = new Sport(id: $id_sport, nome: $sport);
        if (isset($numero_squadre))
            $this->NumeroSquadre = (int)$numero_squadre;
    }

    public static function All(\mysqli $connection) : array
    {
        if (!$connection)
            return array();
        $query = "SELECT * FROM tornei_attivi";
        $result = $connection->query(query: $query);
        if (!$result)
            return array();
        $arr = array();
        while ($row = $result->fetch_assoc())
        {
            $t = new Torneo(
                id: $row["id"],
                nome: $row["nome"],

                tipo: $row["tipo"],
                id_tipo: $row["id_tipo"],

                sport: $row["sport"],
                id_sport: $row["codice_sport"],
                
                numero_squadre: $row["numero_squadre"]
            );

            $arr[] = $t;
        }
        return $arr;
    }
    public static function SubscribeTeam(\mysqli $connection, int $torneo, int $squadra) : bool
    {
        if (!$connection)
            return false;
        try {
            $query = "REPLACE INTO partecipaz_squad_torneo (torneo, squadra) VALUES ($torneo, $squadra)";
            return (bool)$connection->query(query: $query);
        } catch (\Exception $ex) {
            return false;
        }
    }
    public static function Create(\mysqli $connection, int $sport, string $nome, int $tipo) : bool
    {
        if (!$connection)
            return false;
        $curr_ediz = Edizione::Current(connection: $connection)->Id;
        $query = "INSERT INTO tornei (edizione, nome, sport, tipo_torneo) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare(query: $query);
        if (!$stmt)
            return false;
        if (!$stmt->bind_param("isii", $curr_ediz, $nome, $sport, $tipo))
            return false;
        return $stmt->execute() && $stmt->affected_rows === 1;
    }
    public static function GenerateCalendar(\mysqli $connection, int $torneo) : bool
    {
        if (!$connection)
            return false;
        $query = "CALL CreaCalendario($torneo);";
        $result = $connection->query(query: $query);
        $connection->next_result();
        return (bool)$result;   
    }
    public static function ByIdWithNoCalendar(\mysqli $connection, int $id) : ?Torneo
    {
        if (!$connection)
            return null;
        $query = "SELECT * FROM `tornei_attivi` WHERE `id` = $id AND `partite` = 0";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
            return null;
        if ($row = $result->fetch_assoc())
        {
            return new Torneo(
                id: $id,
                nome: $row["nome"], 
                tipo: $row["tipo"], 
                id_tipo: $row["id_tipo"], 
                sport: $row["sport"], 
                id_sport: $row["codice_sport"], 
                numero_squadre: $row["numero_squadre"]
            );
        }
        return null;
    }
    public static function ByIdWithExistingCalendar(\mysqli $connection, int $id) : ?Torneo
    {
        if (!$connection)
            return null;
        $query = "SELECT * FROM `tornei_attivi` WHERE `id` = $id AND `partite` <> 0";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0)
            return null;
        if ($row = $result->fetch_assoc())
        {
            return new Torneo(
                id: $id, 
                nome: $row["nome"], 
                tipo: $row["tipo"], 
                id_tipo: $row["id_tipo"], 
                sport: $row["sport"], 
                id_sport: $row["codice_sport"], 
                numero_squadre: $row["numero_squadre"]
            );
        }
        return null;
    }
}