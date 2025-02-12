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
    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id === 0)
        {
            return null;
        }
        $query = "CALL StaffData($id, YEAR(CURRENT_DATE))";
        $result = $connection->query(query: $query);
        $data = null;
        if ($result)
        {
            if ($row = $result->fetch_assoc())
            {
                $data = new self(
                    id: $id, 
                    nome: $row['nome'],
                );
                $data->Commissioni = array_map(callback: "trim", array: explode(separator: ',', string: $row["commissioni"]));
                $data->Parrocchia = new Parrocchia(
                    id: $row["id_parrocchia"],
                    nome: $row["parrocchia"],
                );
                $data->Taglia = Taglia::tryFrom(value: $row["maglia"]); 
                $data->CodiceFiscale = $row["cf"];
                $data->Referente = (bool)$row["referente"];
            }
            $result->close();
        }
        $connection->next_result();
        return $data;
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
        bool $is_referente = false) : bool
    {
        if (!$connection)
            return false;
        $maglia_sana = $connection->real_escape_string($maglia);
        $query = "CALL PartecipaStaff($staff, $edizione, '$maglia_sana', '";
        for ($i = 0; $i < count($commissioni); $i++)
        {
            $commissione = (int)$commissioni[$i];
            $query .= "$commissione";
            if ($i < count($commissioni) - 1)
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
        $result = (bool)$connection->query($query);
        $connection->next_result();
        return $result;
    }

    public static function All(\mysqli $connection): array {
        if (!$connection) return [];
        $query = "SELECT * FROM `staff_attuali`";

        return [];
    }

    public static function FromParrocchia(\mysqli $connection, int $id): array {
        if (!$connection) return [];
        $query = "SELECT p.* FROM `partecipazioni_staff` p WHERE p.`id_parrocchia` = $id";
        
        $result = $connection->query(query: $query);
        if (!$result) {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $base =  new self(
                id: (int)$row['id_staffista'],
                nome: $row['nome'] . ' ' . $row['cognome'],
            );
            if (
                array_key_exists(key: 'lista_commissioni', array: $row) && 
                isset($row['lista_commissioni'])
            ) {
                $base->Commissioni = array_map(
                    callback: 'trim', 
                    array: explode(separator: ',', string: $row['lista_commissioni'])
                );
            }
            $base->CodiceFiscale = $row['codice_fiscale'];
            if (
                array_key_exists(key: 'referente', array: $row) &&
                isset($row['referente'])
            ) {
                $base->Referente = (bool)$row['referente'];
            }
            $base->Parrocchia = new Parrocchia(
                id: $row['id_parrocchia'],
                nome: $row['parrocchia'],
            );
            $arr[] = $base;
        }
        return $arr;
    }

    public static function MaglieDellaParrocchia(\mysqli $connection, int $parrocchia, int $anno): array
    {
        $p = new Parrocchia(id: $parrocchia, nome: 'Dummy');
        return $p->Maglie(connection: $connection, year: $anno);
    }
}