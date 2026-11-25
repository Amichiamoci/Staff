<?php

namespace Amichiamoci\Models;

class Staff extends StaffBase
{
    public array $Commissioni = [];
    public ?Taglia $Taglia = null;
    public Parrocchia $Parrocchia;
    public ?string $CodiceFiscale = null;
    

    /** Checks the commission name after trimming and normalizing case on both sides. */
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

    /** Considers a staff record complete when it has a fiscal code and shirt size. */
    public function IsEnrolled(): bool
    {
        return !empty($this->CodiceFiscale) && !empty($this->Taglia);
    }

    public function __construct(
        int|string|null $id, 
        string|null $nome,
        string|int $id_parrocchia,
        string|int $nome_parrocchia,
        array $commissioni = [],
        string|Taglia|null $taglia = null,
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
        if (isset($codice_fiscale))
        {
            $this->CodiceFiscale = $codice_fiscale;
        }
    }

    /** Loads the current staff record, including staff whose enrollment is incomplete. */
    public static function ById(\mysqli $connection, int $id) : ?self
    {
        if (!$connection || $id === 0)
        {
            return null;
        }

        $query = "SELECT *
            FROM `staff_correnti_incompleto`
            WHERE `id_staffista` = $id";
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
                commissioni: 
                    array_map(callback: function (string $c): string {
                        return trim(string: $c);
                    },
                    array: explode(
                        separator: ',', 
                        string: $row["lista_commissioni"] ?? '',
                    )),
                taglia: $row["maglia"],
                codice_fiscale: $row["cf"],
            );
        }
        return null;
    }

    /**
     * Links an account to a personal record in a parish and returns the new staff ID.
     * Locks both records so concurrent requests cannot create duplicate links.
     */
    public static function Create(\mysqli $connection, int $id_anagrafica, int $user, int $parrocchia) : ?int
    {
        if (!$connection)
            return null;

        $connection->begin_transaction();
        try {
            // Serialize associations for the same account or anagrafica.
            $connection->execute_query('SELECT `id`
                FROM `utenti`
                WHERE `id` = ?
                FOR UPDATE', [$user]);
            $connection->execute_query('SELECT `id`
                FROM `anagrafiche`
                WHERE `id` = ?
                FOR UPDATE', [$id_anagrafica]);
            $connection->execute_query(
                'INSERT INTO `staffisti` (`dati_anagrafici`, `id_utente`, `parrocchia`)
                 SELECT ?, ?, ?
                 WHERE NOT EXISTS (
                     SELECT 1
                     FROM `staffisti`
                     WHERE `id_utente` = ? OR `dati_anagrafici` = ?
                 )',
                [$id_anagrafica, $user, $parrocchia, $user, $id_anagrafica],
            );
            $id = $connection->affected_rows === 1 ? (int)$connection->insert_id : null;
            $connection->commit();
            return $id;
        } catch (\Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }

    /** Changes the staff member’s parish and reports whether a row changed. */
    public static function ChangeParrocchia(\mysqli $connection, int $staff, int|Parrocchia $parrocchia) : bool
    {
        if ($parrocchia instanceof Parrocchia) {
            $parrocchia = $parrocchia->Id;
        }
        if (!$connection || $staff === 0 || $parrocchia === 0)
            return false;
        $query = "UPDATE `staffisti`
            SET `parrocchia` = $parrocchia
            WHERE `id` = $staff";
        return (bool)$connection->query(query: $query) && $connection->affected_rows === 1;
    }

    /** Enrolls a staff member in an edition with their shirt size and commissions. */
    public static function Partecipa(
        \mysqli $connection, 
        int $staff, 
        int $edizione, 
        string $maglia, 
        array $commissioni, 
    ): bool
    {
        if (!$connection)
            return false;
        $result = (bool)$connection->execute_query(
            'CALL `PartecipaStaff`(?, ?, ?, ?)',
            [$staff, $edizione, $maglia, implode(',', array_map('intval', $commissioni))],
        );
        $connection->next_result();
        return $result;
    }

    /** Lists the staff enrolled in the current edition. */
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
                codice_fiscale: $row["cf"]
            );
        }

        return $arr;
    }

    /** Lists the staff of one parish and year. */
    public static function FromParrocchia(\mysqli $connection, int $id, int $anno): array {
        if (!$connection) return [];
        $query = "SELECT *
            FROM `staff_per_edizione`
            WHERE `id_parrocchia` = $id AND `anno` = $anno";
        
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
                commissioni: 
                    array_map(callback: function (string $c): string {
                        return trim(string: $c);
                    },
                    array: explode(
                        separator: ',', 
                        string: $row["lista_commissioni"] ?? '',
                    )),
                taglia: $row["maglia"],
                codice_fiscale: $row["codice_fiscale"],
            );
        }
        return $arr;
    }

    /** Loads a parish’s shirt sizes for the selected year. */
    public static function MaglieDellaParrocchia(\mysqli $connection, int $parrocchia, int $anno): array
    {
        $p = new Parrocchia(id: $parrocchia, nome: 'Dummy');
        return $p->Maglie(connection: $connection, year: $anno);
    }
}