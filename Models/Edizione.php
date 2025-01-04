<?php
namespace Amichiamoci\Models;

class Edizione
{
    public int $Id = 0;
    public ?string $Motto = null;
    public ?string $ImgPath = null;
    public ?\DateTime $inizio_iscrizioni = null;
    public ?\DateTime $fine_iscrizioni = null;
    public function Ok(): bool
    {
        return $this->Id != 0;
    }

    public function __construct(
        string|int|null $id, 
        string|null $motto, 
        string|null $img,
        string|\DateTime|null $inizio,
        string|\DateTime|null $fine)
    {
        if (isset($id))
            $this->Id = (int)$id;
        
        if (isset($motto) && is_string(value: $motto))
            $this->Motto = $motto;
        if (isset($img) && is_string(value: $img))
            $this->ImgPath = $img;
        
            if (isset($inizio))
        {
            if ($inizio instanceof \DateTime)
            {
                $this->inizio_iscrizioni = $inizio;
            } elseif (is_string(value: $inizio)) {
                try {
                    $this->inizio_iscrizioni = new \DateTime(datetime: $inizio);
                } catch (\Exception $ex) {
                    $this->inizio_iscrizioni = null;
                }
            }
        }

        if (isset($fine))
        {
            if ($fine instanceof \DateTime)
            {
                $this->fine_iscrizioni = $fine;
            } elseif (is_string(value: $inizio)) {
                try {
                    $this->fine_iscrizioni = new \DateTime(datetime: $fine);
                } catch (\Exception $ex) {
                    $this->fine_iscrizioni = null;
                }
            }
        }
    }

    public static function ById(\mysqli $connection, string|int $id) : Edizione|null
    {
        if (!$connection || !isset($id))
            return null;
        $id = (int)$id;
        $query = "SELECT * FROM `edizioni` WHERE `id` = $id";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione(
                id: $row["id"], 
                motto: $row["motto"], 
                img: $row["path_immagine"], 
                inizio: $row["inizio_iscrizioni"], 
                fine: $row["fine_iscrizioni"]
            );
        }
        return null;
    }
    public static function All(\mysqli $connection) : array
    {
        if (!$connection) return array();
        $query = "SELECT * FROM `edizioni` ORDER BY `id` DESC";
        $ret = array();
        $result = $connection->query(query: $query);
        if ($result)
        {
            while($row = $result->fetch_assoc())
            {
                $edizione = new Edizione(
                    id: $row["id"], 
                    motto: $row["motto"], 
                    img: $row["path_immagine"], 
                    inizio: $row["inizio_iscrizioni"], 
                    fine: $row["fine_iscrizioni"]
                );
                $ret[] = $edizione;
            }
        }
        return $ret;
    }
    public static function Current(\mysqli $connection) : Edizione|null
    {
        if (!$connection)
            return null;
        $query = "SELECT * FROM `edizioni` WHERE `id` = YEAR(CURRENT_DATE)";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0) return null;
        if ($row = $result->fetch_assoc())
        {
            return new Edizione(
                id: $row["id"], 
                motto: $row["motto"], 
                img: $row["path_immagine"], 
                inizio: $row["inizio_iscrizioni"], 
                fine: $row["fine_iscrizioni"]
            );
        }
        return null;
    }
    
    public function CanSubscribe() : bool
    {
        if (!$this->ok())
            return false;

        if (isset($this->inizio_iscrizioni))
        {
            if ($this->inizio_iscrizioni > new \DateTime())
            {
                return false;
            }
            if (!isset($this->fine_iscrizioni))
            {
                return true;
            }
            return new \DateTime() <= $this->fine_iscrizioni;
        }

        if (!isset($this->fine_iscrizioni))
        {
            return true;
        }
        return new \DateTime() <= $this->fine_iscrizioni;
    }
}