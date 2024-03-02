<?php

class Edizione
{
    public int $id = 0;
    public int $year = 0;
    public string $motto = "";
    public string $imgpath = "";
    public function ok():bool
    {
        return $this->id != 0;
    }
    public function __construct(
        string|int|null $id, string|int|null $year, string|null $motto, string|null $img)
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($year))
            $this->year = (int)$year;
        
        if (isset($motto) && is_string($motto))
            $this->motto = $motto;
        if (isset($img) && is_string($img))
            $this->imgpath = $img;
    }
    public static function LoadSingle(mysqli $connection, string|int $id) : Edizione|null
    {
        if (!$connection || !isset($id))
            return null;
        $id = (int)$id;
        $query = "SELECT id, anno, motto, path_immagine FROM edizioni WHERE id = $id";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"]);
        }
        return null;
    }
    public static function LoadAll(mysqli $connection) : array
    {
        if (!$connection) return array();
        $query = "SELECT id, anno, motto, path_immagine FROM edizioni ORDER BY anno DESC";
        $ret = array();
        $result = $connection->query($query);
        if ($result)
        {
            while($row = $result->fetch_assoc())
            {
                $edizione = new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"]);
                $ret[] = $edizione;
            }
        }
        return $ret;
    }
    public static function Current(mysqli $connection) : Edizione|null
    {

        if (!$connection)
            return null;
        $query = "SELECT * FROM edizioni WHERE anno = YEAR(CURRENT_DATE)";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"]);
        }
        return null;
    }
}