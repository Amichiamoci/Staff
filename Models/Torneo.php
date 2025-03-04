<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Torneo extends NomeIdSemplice
{
    public static function Table(): string { return 'tornei_espanso'; }
    public TipoTorneo $Tipo;
    public Sport $Sport;
    public Edizione $Edizione;

    /**
     * Associative array of the type id => name
     * @var array
     */
    public array $ListaSquadre = [];

    public array $IdPartite = [];

    public ?self $Successore = null;

    public function __construct(
        string|int|null $id,
        string|null $nome,

        string|null $tipo,
        string|int|null $id_tipo,

        string|null $sport,
        string|int|null $id_sport,

        string|int|null $anno_edizione,
        string|int|null $id_edizione,

        array $squadre,
        array $id_partite,
    ) {
        $this->Id = (int)$id;
        $this->Nome = $nome;
        $this->Tipo = new TipoTorneo(id: $id_tipo, nome: $tipo);
        $this->Sport = new Sport(id: $id_sport, nome: $sport);
        $this->Edizione = new Edizione(id: $id_edizione, year: $anno_edizione, motto: null, img: null);
        $this->ListaSquadre = $squadre;
        $this->IdPartite = $id_partite;
    }

    private static function ParseIdsAndNames(string|array|null $ids, string|array|null $names): array {
        if (!isset($ids) || !isset($names)) {
            return [];
        }
        $arr = [];

        if (is_string(value: $ids)) {
            $ids = explode(separator: ',', string: $ids);
        }
        if (is_string(value: $names)) {
            $names = explode(separator: ',', string: $names);
        }

        $length = min(count(value: $ids),  count(value: $names));
        for ($i = 0; $i < $length; $i++)
        {
            $arr[(int)$ids[$i]] = trim(string: $names[$i]);
        }
        return $arr;
    }

    private static function ParseDbRow(array $row): self
    {
        return new self(
            id: $row["id"],
            nome: $row["nome"],

            tipo: $row["nome_tipo"],
            id_tipo: $row["id_tipo"],

            sport: $row["nome_sport"],
            id_sport: $row["id_sport"],
            
            anno_edizione: $row["anno"],
            id_edizione: $row["id_edizione"],

            squadre: self::ParseIdsAndNames(ids: $row["id_squadre"], names: $row["nomi_squadre"]),
            id_partite: array_keys(self::ParseIdsAndNames(ids: $row["id_partite"], names: $row["id_partite"]))
        );
    }

    public static function All(\mysqli $connection, ?callable $filter = null) : array
    {
        if (!$connection)
            return [];
        $result = $connection->query(query: "SELECT * FROM `tornei_espanso`");
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $t = self::ParseDbRow(row: $row);
            if (!isset($filter) || $filter($t))
            {
                $arr[] = $t;
            }
        }
        return $arr;
    }

    public static function FromYear(\mysqli $connection, int $year) : array
    {
        return self::All(connection: $connection, filter: function (self $t) use($year): bool {
            return $t->Edizione->Year === $year;
        });
    }

    public static function Active(\mysqli $connection) : array
    {
        if (!$connection)
            return [];
        $result = $connection->query(query: "SELECT * FROM `tornei_attivi`");
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = self::ParseDbRow(row: $row);
        }
        return $arr;
    }

    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;
        $result = $connection->query("SELECT * FROM `tornei_espanso` WHERE `id` = $id");
        if (!$result || $result->num_rows !== 1)
            return null;
        return self::ParseDbRow(row: $result->fetch_assoc());
    }

    public static function SubscribeTeam(\mysqli $connection, int $torneo, int $squadra) : bool
    {
        if (!$connection)
            return false;
        try {
            $query = "REPLACE INTO `partecipaz_squad_torneo` (`torneo`, `squadra`) VALUES ($torneo, $squadra)";
            return (bool)$connection->query(query: $query);
        } catch (\Exception) {
            return false;
        }
    }
    /**
     * Does not check if the calendar has already been generated
     * @param \mysqli $connection
     * @param int $torneo
     * @param int $squadra
     * @return bool
     */
    public static function UnSubscribeTeam(\mysqli $connection, int $torneo, int $squadra) : bool
    {
        if (!$connection)
            return false;
        try {
            $query = "DELETE FROM `partecipaz_squad_torneo` WHERE `torneo` = $torneo AND `squadra` = $squadra";
            return (bool)$connection->query(query: $query);
        } catch (\Exception) {
            return false;
        }
    }
    public static function Create(
        \mysqli $connection, 
        int $sport, 
        string $nome, 
        int $tipo, 
        int $edizione
    ): ?int
    {
        if (!$connection)
            return null;
        
        $query = "INSERT INTO `tornei` (`edizione`, `nome`, `sport`, `tipo_torneo`) VALUES (?, ?, ?, ?)";
        $result = $connection->execute_query(
            query: $query, 
            params: [
                $edizione, 
                $nome, 
                $sport, 
                $tipo,
            ],
        );
        if (!$result || $connection->affected_rows !== 1) {
            return null;
        }
        return $connection->insert_id;
    }

    public static function Delete(\mysqli $connection, int $id): bool {
        if (!$connection) 
            return false;
        $result = $connection->query(query: "DELETE FROM `tornei` WHERE `id` = $id");
        return (bool)$result && $connection->affected_rows >= 1;
    }

    public static function GenerateCalendar(
        \mysqli $connection, 
        int $torneo, 
        bool $two_ways = false, 
        ?int $default_field = null,
    ): bool
    {
        if (!$connection)
            return false;
        $query = "CALL `CreaCalendario`(?, ?, ?);";
        $result = $connection->execute_query(query: $query, params: [
            $torneo, (int)$two_ways, $default_field,
        ]);
        $connection->next_result();
        return (bool)$result;   
    }

}