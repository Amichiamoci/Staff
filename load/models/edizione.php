<?php

class Edizione
{
    public int $id = 0;
    public int $year = 0;
    public string $motto = "";
    public string $imgpath = "";
    public DateTime|null $inizio_iscrizioni = null;
    public DateTime|null $fine_iscrizioni = null;
    public function ok():bool
    {
        return $this->id != 0;
    }
    public function __construct(
        string|int|null $id, 
        string|int|null $year, 
        string|null $motto, 
        string|null $img,
        string|DateTime|null $inizio,
        string|DateTime|null $fine)
    {
        if (isset($id))
            $this->id = (int)$id;
        if (isset($year))
            $this->year = (int)$year;
        
        if (isset($motto) && is_string($motto))
            $this->motto = $motto;
        if (isset($img) && is_string($img))
            $this->imgpath = $img;
        if (isset($inizio))
        {
            if ($inizio instanceof DateTime)
            {
                $this->inizio_iscrizioni = $inizio;
            } elseif (is_string($inizio)) {
                try {
                    $this->inizio_iscrizioni = new DateTime($inizio);
                } catch (Exception $ex) {
                    $this->inizio_iscrizioni = null;
                }
            }
        }
        if (isset($fine))
        {
            if ($fine instanceof DateTime)
            {
                $this->fine_iscrizioni = $fine;
            } elseif (is_string($inizio)) {
                try {
                    $this->fine_iscrizioni = new DateTime($fine);
                } catch (Exception $ex) {
                    $this->fine_iscrizioni = null;
                }
            }
        }
    }
    public static function LoadSingle(mysqli $connection, string|int $id) : Edizione|null
    {
        if (!$connection || !isset($id))
            return null;
        $id = (int)$id;
        $query = "SELECT * FROM `edizioni` WHERE `id` = $id";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"], $row["inizio_iscrizioni"], $row["fine_iscrizioni"]);
        }
        return null;
    }
    public static function LoadAll(mysqli $connection) : array
    {
        if (!$connection) return array();
        $query = "SELECT * FROM `edizioni` ORDER BY `anno` DESC";
        $ret = array();
        $result = $connection->query($query);
        if ($result)
        {
            while($row = $result->fetch_assoc())
            {
                $edizione = new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"], $row["inizio_iscrizioni"], $row["fine_iscrizioni"]);
                $ret[] = $edizione;
            }
        }
        return $ret;
    }
    public static function Current(mysqli $connection) : Edizione|null
    {
        if (!$connection)
            return null;
        $query = "SELECT * FROM `edizioni` WHERE `anno` = YEAR(CURRENT_DATE)";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"], $row["inizio_iscrizioni"], $row["fine_iscrizioni"]);
        }
        return null;
    }
    public static function FromYear(mysqli $connection, int $year) : Edizione|null
    {
        if (!$connection)
        return null;
        $query = "SELECT * FROM `edizioni` WHERE `anno` = $year";
        $result = $connection->query($query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione($row["id"], $row["anno"], $row["motto"], $row["path_immagine"], $row["inizio_iscrizioni"], $row["fine_iscrizioni"]);
        }
        return null;
    }
    public function IscrizioniAperte() : bool
    {
        if (!$this->ok())
            return false;
        if (isset($this->inizio_iscrizioni))
        {
            if ($this->inizio_iscrizioni > new DateTime())
            {
                return false;
            }
            if (!isset($this->fine_iscrizioni))
            {
                return true;
            }
            return new DateTime() <= $this->fine_iscrizioni;
        }
        if (!isset($this->fine_iscrizioni))
        {
            return true;
        }
        return new DateTime() <= $this->fine_iscrizioni;
    }
}