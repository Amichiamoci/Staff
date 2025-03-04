<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\NomeIdSemplice;

class Squadra extends NomeIdSemplice
{    
    public string $Membri = "";
    public array $IdIscritti = [];

    public Parrocchia $Parrocchia;
    public Sport $Sport;

    public static function Table(): string { return 'squadre'; }

    public function __construct(
        string|int $id,
        string $nome,

        ?string $membri,
        string|array|null $iscrizione_membri,

        string $parrocchia,
        string|int $id_parrocchia,
        string $sport,
        string|int $id_sport
    ) {
        parent::__construct(id: $id, nome: $nome);
        
        if (isset($membri) && is_string(value: $membri))
            $this->Membri = $membri;
        
        if (isset($iscrizione_membri)) {
            if (is_array(value: $iscrizione_membri)) {
                $this->IdIscritti = $iscrizione_membri;
            } else {
                $this->IdIscritti = explode(separator: ',', string: $iscrizione_membri);
            }
            $this->IdIscritti = array_map(callback: function (string|int $o) : int {
                return (int)$o;
            }, array: $this->IdIscritti);
        }
        
        $this->Parrocchia = new Parrocchia(
            id: $id_parrocchia,
            nome: $parrocchia
        );

        $this->Sport = new Sport(
            id: $id_sport,
            nome: $sport
        );
    }
    
    public static function All(
        \mysqli $connection, 
        string|int|null $year = null, 
        string|int|null $sport = null
    ) : array {
        if (!$connection)
            return [];
        $y = (!isset($year) || (int)$year === 0) ? "NULL" : (int)$year;
        $s = (!isset($sport) || (int)$sport === 0) ? "NULL" : (int)$sport;
        
        $result = $connection->query(query: "CALL SquadreList($y, $s);");
        if (!$result)
        {
            $connection->next_result();
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                id: $row["id_squadra"], 
                nome: $row["nome"],
                
                membri: $row["lista_membri"],
                iscrizione_membri: $row["id_membri"],
                
                parrocchia: $row["parrocchia"],
                id_parrocchia: $row["id_parrocchia"],
                
                sport: $row["nome_sport"],
                id_sport: $row["id_sport"]
            );
        }
        $result->close();
        $connection->next_result();
        return $arr;
    }

    public static function FromParrocchia(
        \mysqli $connection, 
        int $parrocchia, 
        ?int $year = null, 
        ?int $sport = null,
    ): array {
        $arr = self::All(connection: $connection, year: $year, sport: $sport);
        return array_filter(array: $arr, callback: function (self $s) use ($parrocchia): bool {
            return $s->Parrocchia->Id === $parrocchia;
        });
    }

    public static function NomeFromId(\mysqli $connection, int $id) : ?string
    {
        if (!$connection || $id === 0)
            return null;
        
        $result = $connection->query(query: "SELECT `nome` FROM `squadre` WHERE `id` = $id");
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        
        $ret = null;
        if ($row = $result->fetch_assoc())
        {
            if (isset($row["nome"]))
            {
                $ret = $row["nome"];
            }
        }
        $result->close();
        return $ret;
    }

    public static function Delete(\mysqli $connection, int $id) : bool
    {
        if (!$connection)
            return false;

        $result = $connection->query(
            query: "DELETE FROM `squadre` WHERE `squadre`.`id` = $id");
        $connection->next_result();
        return (bool)$result && $connection->affected_rows === 1;
    }

    public static function Load(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id === 0)
        {
            return null;
        }

        $result = $connection->query(query: "CALL GetSquadra($id);");
        if (!$result || $result->num_rows === 0)
        {
            $connection->next_result();
            return null;
        }

        $squadra = null;
        if ($row = $result->fetch_assoc())
        {
            $squadra = new Squadra(
                id: $row["id"],
                nome: $row["nome"],

                membri: $row["membri"],
                iscrizione_membri: $row["id_iscr_membri"],

                parrocchia: $row["parrocchia"],
                id_parrocchia: $row["id_parrocchia"],
                sport: $row["sport"],
                id_sport: $row["id_sport"]);
        }
        $connection->next_result();
        return $squadra;
    }

    public static function Create(
        \mysqli $connection, 
        string $nome, 
        int $parrocchia, 
        int $sport, 
        string $membri, 
        int $edizione,
    ) : bool {
        if (!$connection || $edizione === 0)
            return false;
        $nome = $connection->real_escape_string($nome);
        $membri = $connection->real_escape_string($membri);

        $result = $connection->query(query: "CALL CreaSquadra('$nome', $parrocchia, $sport, '$membri', $edizione)");
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
        \mysqli $connection, 
        int $id,
        string $nome, 
        int $parrocchia,
        int $sport,
        string $membri) : bool
    {
        if (!$connection || $id === 0)
            return false;
        $nome = $connection->real_escape_string($nome);
        $membri = $connection->real_escape_string($membri);
        
        $result = $connection->query(query: "CALL ModificaSquadra($id, '$nome', $parrocchia, $sport, '$membri')");
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

    public function MembriFull(): array {
        $a = [];
        $names = explode(separator: ',', string: $this->Membri);
        for ($i = 0; $i < count(value: $this->IdIscritti); $i++)
        {
            $a[$this->IdIscritti[$i]] = trim(string: $names[$i]);
        }
        return $a;
    }
}