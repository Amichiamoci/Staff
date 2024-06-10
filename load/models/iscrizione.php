<?php

class Iscrizione
{
    
    public int $id = 0;
    public string $nome = "";
    public string $parrocchia = "";
    public int $id_parrocchia = 0;
    public int $anno_edizione = 0;
    public int $id_edizione = 0;
    public string $taglia = "";
    public int $id_tutore = 0;

    public function __construct(
        string|int|null $id,
        string|null $nome,
        string|null $parrocchia,
        string|int|null $id_parrocchia,
        string|int|null $anno_edizione,
        string|int|null $id_edizione,
        string|null $taglia,
        string|int|null $id_tutore
    )
    {
        if (isset($id))
        {
            $this->id = (int)$id;
        }
        if (isset($nome) && is_string($nome))
        {
            $this->nome = $nome;
        }
        
        if (isset($parrocchia) && is_string($parrocchia))
        {
            $this->parrocchia = $parrocchia;
        }
        if (isset($id_parrocchia))
        {
            $this->id_parrocchia = (int)$id_parrocchia;
        }

        if (isset($anno_edizione))
        {
            $this->anno_edizione = (int)$anno_edizione;
        }
        if (isset($id_edizione))
        {
            $this->id_edizione = (int)$id_edizione;
        }

        if (isset($taglia) && is_string($taglia))
        {
            $this->taglia = $taglia;
        }
        
        if (isset($id_tutore))
        {
            $this->id_tutore = (int)$id_tutore;
        }
    }
    public static function GetAll(mysqli $connection, $filter = null):array
    {
        if (!$connection)
            return array();
        $query = "CALL IscrizioniList(YEAR(CURRENT_DATE), NULL);";
        $result = $connection->query($query);
        $iscrizioni = array();
        if (!$result)
        {
            $connection->next_result();
            return $iscrizioni;
        }
        while ($row = $result->fetch_assoc())
        {
            $iscritto = new Iscrizione(
                $row["id_iscrizione"],
                $row["nome"] . " " . $row["cognome"],
                $row["parrocchia"],
                $row["id_parrocchia"],
                $row["anno"],
                $row["id_edizione"],
                null, null
            );
            
            $iscrizioni[] = $iscritto;
        }
        $result->close();
        $connection->next_result();
        if (!is_null($filter))
            $iscrizioni = array_filter($iscrizioni, $filter);
        return $iscrizioni;
    }
    public static function Load(mysqli $connection, int $id) : Iscrizione|null
    {
        $query = "CALL SingolaIscrizione($id);";
        $result = $connection->query($query);
        if (!$result)
        {
            $connection->next_result();
            return null;
        }
        $iscritto = null;
        if ($row = $result->fetch_assoc())
        {
            $iscritto = new Iscrizione(
                $row["id_iscrizione"],
                $row["nome"] . " " . $row["cognome"],
                $row["parrocchia"],
                $row["id_parrocchia"],
                $row["anno"],
                $row["id_edizione"],
                $row["maglia"],
                $row["id_tutore"]
            );
        }
        $result->close();
        $connection->next_result();
        return $iscritto;
    }

    public static function Exists(mysqli $connection, int $id_anagrafica, int $edizione) : bool
    {
        $query = "SELECT * FROM iscritti WHERE dati_anagrafici = $id_anagrafica AND edizione = $edizione";
        $result = mysqli_query($connection, $query);
        if (!$result)
            return false;
        return $result->num_rows > 0;
    }

    public static function Create(
        mysqli $connection,
        int $id_anagrafica, 
        int $tutore, 
        string|null $certificato, 
        int $parrocchia, 
        string $taglia, 
        int $edizione)
    {
        if (!$connection)
            return false;
        $query = "INSERT INTO iscritti (dati_anagrafici, edizione, tutore, certificato_medico, parrocchia, taglia_maglietta) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);
        if (!$stmt)
            return false;
        $tutore_query = $tutore;
        if ($tutore == 0)
            $tutore_query = null;
        if (!$stmt->bind_param("iiisis", $id_anagrafica, $edizione, $tutore_query, $certificato, $parrocchia, $taglia))
        {
            return false;
        }
        return $stmt->execute();
    }

    public static function UpdateCertificato(mysqli $connection, int $id, string $certificato) : bool
    {
        if (!$connection)
            return false;
        $query = "UPDATE iscritti SET certificato_medico = ? WHERE id = ?";
        $stmt = $connection->prepare($query);
        if (!$stmt) 
            return false;
        if (!$stmt->bind_param("si", $certificato, $id))
            return false;
        if (!$stmt->execute())
            return false;
        return $stmt->affected_rows === 1;
    }

    public function Update(mysqli $connection)
    {
        if (!$connection)
            return false;
        $query = "UPDATE iscritti SET taglia_maglietta = ?, tutore = ?, parrocchia = ? WHERE id = ?";
        $stmt = $connection->prepare($query);
        if (!$stmt)
            return false;
        $tutore = $this->id_tutore === 0 ? null : $this->id_tutore;
        if (!$stmt->bind_param("siii", $this->taglia, $tutore, $this->id_parrocchia, $this->id))
            return false;
        if (!$stmt->execute())
            return false;
        return $stmt->affected_rows === 1;
    }

    public static function EmailNonSubscribed(mysqli $connection, int $year): ?array
    {
        if (!$connection) 
            return null;
        $query = "SELECT nome, sesso, email FROM non_iscritti WHERE anno = ? AND email IS NOT NULL";
        $result = $connection->execute_query($query, array($year));
        if (!$result)
            return array();
        return $result->fetch_array();
    }
}

class Maglie
{
    public static function All(): array
    {
        return array('XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL');
    }
    public static function IsValid(string|null $taglia) : bool
    {
        if (!isset($taglia))
            return false;
        $all = self::All();
        return in_array($taglia, $all);
    }
}