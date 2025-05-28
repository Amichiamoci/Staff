<?php

namespace Amichiamoci\Models;

class Staff extends StaffBase
{
    public array $Commissioni = [];
    public ?Taglia $Taglia = null;
    public Parrocchia $Parrocchia;
    public bool $Referente = false;
    public ?string $CodiceFiscale = null;
    
    public function InCommissione(string $commissione): bool
    {
        return in_array(
            needle: strtolower(string: trim(string: $commissione)),
            haystack: 
                array_map(callback: function (string $c): string {
                    return strtolower(string: trim(string: $c));
                }, array: $this->Commissioni)
        );
    }

    public function __construct(
        int|string|null $id, 
        string|null $nome,
        string|int $id_parrocchia,
        string|int $nome_parrocchia,
        array $commissioni = [],
        string|Taglia|null $taglia = null,
        string|int|bool $is_referente = false,
        string|null $codice_fiscale = null, 
    ) {
        parent::__construct(id: $id, nome: $nome);
        $this->Parrocchia = new Parrocchia(
            id: $id_parrocchia, 
            nome: $nome_parrocchia
        );
        $this->Commissioni = array_filter(array: $commissioni, callback: function (string $c): bool {
            return strlen(string: trim(string: $c)) > 0;
        });
        if (isset($taglia))
        {
            $this->Taglia = ($taglia instanceof Taglia) ? $taglia : Taglia::from(value: $taglia);
        }
        $this->Referente = (bool)$is_referente;
        if (isset($codice_fiscale))
        {
            $this->CodiceFiscale = $codice_fiscale;
        }
    }
    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id === 0)
        {
            return null;
        }

        $query = "SELECT * FROM `staff_correnti_incompleto` WHERE  `id_staffista` = $id";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }

        if ($row = $result->fetch_assoc())
        {
            return new self(
                id: $id, 
                nome: $row['nome'] . ' ' . $row['cognome'],
                id_parrocchia: $row["id_parrocchia"],
                nome_parrocchia: $row["parrocchia"],
                commissioni: array_map(callback: "trim", array: explode(separator: ',', string: $row["lista_commissioni"])),
                taglia: $row["maglia"],
                is_referente: $row["referente"],
                codice_fiscale: $row["cf"],
            );
        }
        return null;
    }
    public static function Create(\mysqli $connection, int $id_anagrafica, int $user, int $parrocchia) : ?int
    {
        if (!$connection)
            return null;

        // Insert the new row
        $query = "INSERT INTO `staffisti` (`dati_anagrafici`, `id_utente`, `parrocchia`) VALUES ($id_anagrafica, $user, $parrocchia)";
        if (
            !$connection->query(query: $query) || 
            $connection->affected_rows !== 1 ||
            empty($connection->insert_id)
        ) {
            return null;
        }
        return (int)$connection->insert_id;
    }
    public static function ChangeParrocchia(\mysqli $connection, int $staff, int|Parrocchia $parrocchia) : bool
    {
        if ($parrocchia instanceof Parrocchia) {
            $parrocchia = $parrocchia->Id;
        }
        if (!$connection || $staff === 0 || $parrocchia === 0)
            return false;
        $query = "UPDATE `staffisti` SET `parrocchia` = $parrocchia WHERE `id` = $staff";
        return (bool)$connection->query(query: $query) && $connection->affected_rows === 1;
    }
    public static function Partecipa(
        \mysqli $connection, 
        int $staff, 
        int $edizione, 
        string $maglia, 
        array $commissioni, 
        bool $is_referente = false,
    ): bool
    {
        if (!$connection)
            return false;
        $maglia_sana = $connection->real_escape_string(string: $maglia);
        $query = "CALL PartecipaStaff($staff, $edizione, '$maglia_sana', '";
        for ($i = 0; $i < count(value: $commissioni); $i++)
        {
            $commissione = (int)$commissioni[$i];
            $query .= "$commissione";
            if ($i < count(value: $commissioni) - 1)
            {
                $query .= ",";
            }
        }
        $query .= "', ";
        if ($is_referente) {
            $query .= "1";
        } else {
            $query .= "0";
        }
        $query .= ");";
        $result = (bool)$connection->query(query: $query);
        $connection->next_result();
        return $result;
    }

    public static function All(\mysqli $connection): array {
        if (!$connection) 
            return [];

        $query = "SELECT * FROM `staff_attuali`";
        $result = $connection->query(query: $query);
        if (!$result) return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                id: $row['id_staffista'], 
                nome: $row['nome'] . ' ' . $row['cognome'],
                id_parrocchia: $row["id_parrocchia"],
                nome_parrocchia: $row["parrocchia"],
                commissioni: 
                    array_map(callback: function (string $c): string {
                        return trim(string: $c);
                    },
                    array: explode(
                        separator: ',', 
                        string: $row["lista_commissioni"] ?? '',
                    )),
                taglia: $row["maglia"],
                is_referente: $row["referente"],
                codice_fiscale: $row["cf"]
            );
        }

        return $arr;
    }

    public static function FromParrocchia(\mysqli $connection, int $id, int $anno): array {
        if (!$connection) return [];
        $query = "SELECT * FROM `staff_per_edizione` WHERE `id_parrocchia` = $id AND `anno` = $anno";
        
        $result = $connection->query(query: $query);
        if (!$result) {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                id: $row['id_staffista'], 
                nome: $row['nome'] . ' ' . $row['cognome'],
                id_parrocchia: $row["id_parrocchia"],
                nome_parrocchia: $row["parrocchia"],
                commissioni: array_map(callback: "trim", array: explode(separator: ',', string: $row["lista_commissioni"])),
                taglia: $row["maglia"],
                is_referente: $row["codice_fiscale"],
                codice_fiscale: $row["referente"],
            );
        }
        return $arr;
    }

    public static function MaglieDellaParrocchia(\mysqli $connection, int $parrocchia, int $anno): array
    {
        $p = new Parrocchia(id: $parrocchia, nome: 'Dummy');
        return $p->Maglie(connection: $connection, year: $anno);
    }
}