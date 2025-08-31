<?php
namespace Amichiamoci\Models;

class Edizione
{
    public int $Id = 0;
    public int $Year = 0;
    public ?string $Motto = null;
    public ?string $ImgPath = null;
    public function Ok(): bool
    {
        return $this->Id !== 0;
    }

    public function __construct(
        string|int|null $id, 
        string|int|null $year,
        string|null $motto, 
        string|null $img,
    ) {
        if (isset($id))
            $this->Id = (int)$id;
        if (isset($year))
            $this->Year = (int)$year;
        
        if (isset($motto) && is_string(value: $motto))
            $this->Motto = $motto;
        if (isset($img) && is_string(value: $img))
            $this->ImgPath = $img;
    }

    public static function ById(\mysqli $connection, string|int $id) : ?self
    {
        if (!$connection || !isset($id))
            return null;
        $id = (int)$id;
        $query = "SELECT * FROM `edizioni` WHERE `id` = $id";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"], 
                year: $row['anno'],
                motto: $row["motto"], 
                img: $row["path_immagine"], 
            );
        }
        return null;
    }
    public static function FromYear(\mysqli $connection, string|int $year) : ?self
    {
        if (!$connection || !isset($year))
            return null;
        $year = (int)$year;
        $query = "SELECT * FROM `edizioni` WHERE `anno` = $year";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"], 
                year: $row['anno'],
                motto: $row["motto"], 
                img: $row["path_immagine"], 
            );
        }
        return null;
    }
    public static function All(\mysqli $connection) : array
    {
        if (!$connection) return array();
        $query = "SELECT * FROM `edizioni` ORDER BY `anno` DESC";
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
                    img: $row["path_immagine"], 
                );
                $ret[] = $edizione;
            }
        }
        return $ret;
    }
    public static function Current(\mysqli $connection) : ?self
    {
        if (!$connection)
            return null;
        $query = "SELECT * FROM `edizioni` WHERE `anno` = YEAR(CURRENT_DATE)";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $row["id"], 
                year: $row['anno'],
                motto: $row["motto"], 
                img: $row["path_immagine"], 
            );
        }
        return null;
    }
    public static function New(\mysqli $connection, int $year, string $motto): ?self
    {
        if (!$connection) {
            return null;
        }
        $query = "INSERT INTO `edizioni` (`anno`, `motto`) VALUES (?, ?)";
        $result = $connection->execute_query(query: $query, params: [$year, $motto]);
        if (!$result) {
            return null;
        }

        return new self(
            id: $connection->insert_id,
            year: $year,
            motto: $motto,
            img: null
        );
    }
    public function Update(\mysqli $connection): ?self
    {
        if (!$connection) {
            return null;
        }
        $query = "UPDATE `edizioni` SET `anno` = ?, `motto` = ?, `path_immagine` = ? WHERE `id` = ?";
        $result = $connection->execute_query(query: $query, params: [$this->Year, $this->Motto, $this->ImgPath, $this->Id]);
        if (!$result) {
            return null;
        }

        return $this;
    }

    public static function EtaPartecipanti(\mysqli $connection, int $id): array
    {
        if (!$connection)
            return [];
        $query = "SELECT * FROM `eta_iscritti_edizione` WHERE id = ?";
        
        $result = $connection->execute_query(query: $query, params: [$id]);
        if (!$result)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[(int)$row['eta']] = (int)$arr['partecipanti'];
        }
        return $arr;
    }
}