<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Iscrizione extends NomeIdSemplice
{
    public Parrocchia $Parrocchia;
    public int $Edizione;
    public Taglia $Taglia;
    public ?int $IdTutore = null;
    public ?string $Certificato = null;

    public static function Table(): string { return 'iscritti'; }

    public function __construct(
        string|int $id,
        string $nome,
        string $parrocchia,
        string|int $id_parrocchia,
        string|int $anno_edizione,
        string|Taglia $taglia,
        string|int|null $id_tutore = null,
        ?string $certificato = null,
    ) {
        parent::__construct(id: $id, nome: $nome);
        
        $this->Parrocchia = new Parrocchia(
            id: $id_parrocchia,
            nome: $parrocchia
        );
        $this->Edizione = (int)$anno_edizione;

        if ($taglia instanceof Taglia)
        {
            $this->Taglia = $taglia;
        } else {
            $this->Taglia = Taglia::from(value: $taglia);
        }
        
        if (isset($id_tutore))
        {
            $this->IdTutore = (int)$id_tutore;
        }
        if (isset($certificato) && !empty($certificato))
        {
            $this->Certificato = $certificato;
        }
    }

    /** Lists current-year enrollments and optionally filters the resulting objects. */
    public static function All(\mysqli $connection, ?callable $filter = null) : array
    {
        if (!$connection)
            return [];

        $result = $connection->query(query: "CALL IscrizioniList(YEAR(CURRENT_DATE), NULL);");
        if (!$result)
        {
            $connection->next_result();
            return [];
        }
        
        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                id: $row["id_iscrizione"],
                nome: $row["nome"] . " " . $row["cognome"],
                parrocchia: $row["parrocchia"],
                id_parrocchia: $row["id_parrocchia"],
                anno_edizione: $row["anno"],
                taglia: $row["maglia"], 
                id_tutore: $row["id_tutore"],
                certificato: $row["certificato_medico"],
            );
        }
        $result->close();
        $connection->next_result();
        if (!is_null(value: $filter))
            $arr = array_filter(array: $arr, callback: $filter);
        return $arr;
    }
    

    /** Loads an enrollment with its parish, edition, guardian, and certificate reference. */
    public static function ById(\mysqli $connection, int $id) : ?self
    {
        $query = "CALL SingolaIscrizione($id);";
        $result = $connection->query(query: $query);
        if (!$result)
        {
            $connection->next_result();
            return null;
        }

        $obj = null;
        if ($row = $result->fetch_assoc())
        {
            $obj = new self(
                id: $row["id_iscrizione"],
                nome: $row["nome"] . " " . $row["cognome"],
                parrocchia: $row["parrocchia"],
                id_parrocchia: $row["id_parrocchia"],
                anno_edizione: $row["anno"],
                taglia: $row["maglia"],
                id_tutore: $row["id_tutore"],
                certificato: $row["certificato_medico"],
            );
        }
        $result->close();
        $connection->next_result();
        return $obj;
    }

    /** Checks whether a personal record already has an enrollment for the given edition ID. */
    public static function Exists(
        \mysqli $connection, 
        int $id_anagrafica, 
        int $edizione
    ) : bool {
        $query = "SELECT * 
        FROM `iscritti` 
        WHERE `dati_anagrafici` = $id_anagrafica AND `edizione` = $edizione";
        
        $result = $connection->query(query: $query);
        if (!$result)
            return false;
        return $result->num_rows > 0;
    }

    /** Returns the edition ID of an enrollment, or null when the enrollment does not exist. */
    public static function EditionId(\mysqli $connection, int $id): ?int
    {
        $result = $connection->execute_query('SELECT edizione FROM iscritti WHERE id = ?', [$id]);
        $row = $result->fetch_row();
        return $row === null ? null : (int)$row[0];
    }

    /** Returns the personal record ID associated with an enrollment. */
    public static function IdAnagraficaAssociata(\mysqli $connection, int $id) : ?int
    {
        if (!$connection || $id === 0)
            return null;
        
        $result = $connection->query(
            query: "SELECT `dati_anagrafici` FROM `iscritti` WHERE `id` = $id LIMIT 1"
        );
        if (!$result || $result->num_rows === 0)
            return null;

        return (int)$result->fetch_assoc()['dati_anagrafici'];
    }

    /** Creates an enrollment with the given parish, shirt size, and optional guardian and certificate. */
    public static function Create(
        \mysqli $connection,
        int $id_anagrafica, 
        ?int $tutore, 
        ?string $certificato, 
        int $parrocchia, 
        Taglia $taglia, 
        int $edizione
    ): bool {
        if (!$connection)
            return false;
        $query = "INSERT INTO `iscritti` (`dati_anagrafici`, `edizione`, `tutore`, `certificato_medico`, `parrocchia`, `taglia_maglietta`) VALUES (?, ?, ?, ?, ?, ?)";
        
        return (bool)$connection->execute_query(
            query: $query, 
            params: [
                $id_anagrafica, 
                $edizione, 
                $tutore, 
                $certificato, 
                $parrocchia, 
                $taglia->value
            ]
        );
    }

    /** Stores a replacement certificate URI or clears the reference when the input is blank. */
    public static function UpdateCertificato(
        \mysqli $connection, 
        int $id, 
        string $certificato,
    ) : bool {
        if (!$connection)
            return false;
        if (empty($certificato) || strlen(string: trim(string: $certificato)) === 0)
        {
            $certificato = null;
        }
        $query = "UPDATE `iscritti` SET `certificato_medico` = ? WHERE `id` = ?";
        $result = $connection->execute_query(query: $query, params: [
            $certificato,
            $id,
        ]);
        return (bool)$result;
        // return $result !== false && $connection->affected_rows >= 1;
    }

    /** Persists changes to the enrollment’s shirt size, guardian, and parish. */
    public function Update(\mysqli $connection): bool
    {
        if (!$connection)
            return false;
        $query = "UPDATE `iscritti` SET `taglia_maglietta` = ?, `tutore` = ?, `parrocchia` = ? WHERE `id` = ?";
        $result = $connection->execute_query(query: $query, params: [
            $this->Taglia->value,
            $this->IdTutore,
            $this->Parrocchia->Id,
            $this->Id,
        ]);
        // return $result !== false && $connection->affected_rows >= 1;
        return (bool)$result;
    }

    /** Deletes the enrollment and reports whether a row was removed. */
    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection) {
            return false;
        }

        $query = "DELETE FROM `iscritti` WHERE `id` = $id";
        $res = (bool)$connection->query(query: $query);
        return $res && $connection->affected_rows >= 1;
    }
    
}