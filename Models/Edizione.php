<?php
namespace Amichiamoci\Models;

class Edizione
{
    public int $Id = 0;
    public int $Year = 0;
    public ?string $Motto = null;

    /** Checks whether the object has a persisted edition ID. */
    public function Ok(): bool
    {
        return $this->Id !== 0;
    }

    public function __construct(
        string|int|null $id, 
        string|int|null $year,
        string|null $motto, 
    ) {
        if (isset($id))
            $this->Id = (int)$id;
        if (isset($year))
            $this->Year = (int)$year;
        
        if (isset($motto) && is_string(value: $motto))
            $this->Motto = $motto;
    }

    /** Finds an edition by its database ID. */
    public static function ById(\mysqli $connection, string|int $id) : ?self
    {
        if (!$connection || !isset($id))
            return null;
        $id = (int)$id;
        $query = "SELECT *
            FROM `edizioni`
            WHERE `id` = $id";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"], 
                year: $row['anno'],
                motto: $row["motto"], 
            );
        }
        return null;
    }

    /** Finds the edition for a calendar year. */
    public static function FromYear(\mysqli $connection, string|int $year) : ?self
    {
        if (!$connection || !isset($year))
            return null;
        $year = (int)$year;
        $query = "SELECT *
            FROM `edizioni`
            WHERE `anno` = $year";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"], 
                year: $row['anno'],
                motto: $row["motto"], 
            );
        }
        return null;
    }

    /** Lists editions in descending year order. */
    public static function All(\mysqli $connection) : array
    {
        if (!$connection) return array();
        $query = "SELECT *
            FROM `edizioni`
            ORDER BY `anno` DESC";
        $ret = array();
        $result = $connection->query(query: $query);
        if ($result)
        {
            while($row = $result->fetch_assoc())
            {
                $edizione = new self(
                    id: $row["id"], 
                    year: $row['anno'],
                    motto: $row["motto"], 
                );
                $ret[] = $edizione;
            }
        }
        return $ret;
    }

    /** Looks up the edition for the database server’s current year. */
    public static function Current(\mysqli $connection) : ?self
    {
        if (!$connection)
            return null;
        $query = "SELECT *
            FROM `edizioni`
            WHERE `anno` = YEAR(CURRENT_DATE)";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"], 
                year: $row['anno'],
                motto: $row["motto"], 
            );
        }
        return null;
    }

    /** Inserts an edition and returns the new object, or null when the insert fails. */
    public static function New(\mysqli $connection, int $year, string $motto): ?self
    {
        if (!$connection) {
            return null;
        }
        $query = "INSERT INTO `edizioni` (`anno`, `motto`)
            VALUES (?, ?)";
        $result = $connection->execute_query(query: $query, params: [$year, $motto]);
        if (!$result) {
            return null;
        }

        return new self(
            id: $connection->insert_id,
            year: $year,
            motto: $motto,
        );
    }

    /** Persists this edition’s year and motto. */
    public function Update(\mysqli $connection): ?self
    {
        if (!$connection) {
            return null;
        }
        $query = "UPDATE `edizioni`
            SET `anno` = ?, `motto` = ?
            WHERE `id` = ?";
        $result = $connection->execute_query(query: $query, params: [$this->Year, $this->Motto, $this->Id]);
        if (!$result) {
            return null;
        }

        return $this;
    }

    /** Returns participant counts indexed by age for the specified edition ID. */
    public static function EtaPartecipanti(\mysqli $connection, int $id): array
    {
        if (!$connection)
            return [];
        $query = "SELECT *
            FROM `eta_iscritti_edizione`
            WHERE `id` = ?";
        
        $result = $connection->execute_query(query: $query, params: [$id]);
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[(int)$row['eta']] = (int)$row['partecipanti'];
        }
        return $arr;
    }
}