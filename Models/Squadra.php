<?php

namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\NomeIdSemplice;

class Squadra extends NomeIdSemplice
{    
    public string $Membri = "";
    public array $IdIscritti = [];

    public Parrocchia $Parrocchia;
    public Sport $Sport;

    public ?string $Referenti = null;

    public static function Table(): string { return 'squadre'; }

    public function __construct(
        string|int $id,
        string $nome,

        string $parrocchia,
        string|int $id_parrocchia,
        string $sport,
        string|int $id_sport,

        ?string $membri = null,
        string|array|null $iscrizione_membri = [],

        ?string $referenti = null,
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

        $this->Referenti = $referenti;
    }
    
    public static function All(
        \mysqli $connection, 
        string|int|null $year = null, 
        string|int|null $sport = null
    ) : array {
        if (!$connection)
            return [];
        
        $result = $connection->execute_query(
            query: "CALL `SquadreList`(?, ?);",
            params: [ $year, $sport, ],
        );
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
                id_sport: $row["id_sport"],

                referenti: array_key_exists(key: 'referenti', array: $row) ? $row['referenti'] : null,
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

    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id === 0)
        {
            return null;
        }

        $result = $connection->query(query: "CALL `GetSquadra`($id);");
        if (!$result || $result->num_rows === 0)
        {
            $connection->next_result();
            return null;
        }

        $squadra = null;
        if ($row = $result->fetch_assoc())
        {
            $squadra = new self(
                id: $row["id"],
                nome: $row["nome"],

                membri: $row["membri"],
                iscrizione_membri: $row["id_iscr_membri"],

                parrocchia: $row["parrocchia"],
                id_parrocchia: $row["id_parrocchia"],

                sport: $row["sport"],
                id_sport: $row["id_sport"],

                referenti: array_key_exists(key: 'referenti', array: $row) ? $row['referenti'] : null,
            );
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
        ?string $coach = null,
        ?int $id = null,
    ): bool 
    {
        if (!$connection || $edizione === 0)
            return false;

        if (empty($id))
        {
            // Creating the team
            $result = $connection->execute_query(
                query: "CALL `CreaSquadra`(?, ?, ?, ?, ?, ?)", 
                params: [
                    $nome,
                    $parrocchia,
                    $sport,
                    $membri,
                    $edizione,
                    $coach,
                ],
            );
        } else {
            // Editing the team
            $result = $connection->execute_query(
                query: "CALL `ModificaSquadra`(?, ?, ?, ?, ?, ?)", 
                params: [
                    $id,
                    $nome,
                    $parrocchia,
                    $sport,
                    $membri,
                    $coach,
                ],
            );
        }
        if (!$result || $result->num_rows === 0)
        {
            $connection->next_result();
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

    /**
     * returns a dictionary of the type
     * MemberId => MemberName
     * @return 
     */
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