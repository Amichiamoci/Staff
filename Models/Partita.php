<?php

namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\DbEntity;

class Partita implements DbEntity
{
    public int $Id;
    public int $Torneo;
    public ?Campo $Campo = null;
    public ?string $Data = null;
    public ?string $Orario = null;

    public Squadra $Casa;
    public Squadra $Ospiti;

    /** Returns the sport associated with the match. */
    public function getSport(): Sport
    {
        return $this->Casa->Sport;
    }

    public array $Punteggi = [];

    public function __construct(
        string|int $id,
        string|int $torneo,
        ?string $data,
        ?string $orario,

        string|int|null $id_campo,
        string|int|null $nome_campo,
        string|int|null $lat_campo,
        string|int|null $lon_campo,
        string|null $indirizzo_campo,

        string|int $id_sport,
        string $nome_sport,

        int|string $id_casa,
        string $nome_casa,
        int|string $id_parrocchia_casa,
        string $nome_parrocchia_casa,

        int|string $id_ospiti,
        string $nome_ospiti,
        int|string $id_parrocchia_ospiti,
        string $nome_parrocchia_ospiti,

        string|array|null $id_punteggi = null,
        string|array|null $punteggi_casa = null,
        string|array|null $punteggi_ospiti = null,
    ) {
        $this->Id = (int)$id;
        $this->Torneo = (int)$torneo;
        $this->Data = $data;
        $this->Orario = $orario;

        if (isset($id_campo) && isset($nome_campo))
        {
            $this->Campo = new Campo(
                id: $id_campo, 
                nome: $nome_campo, 
                latitudine: $lat_campo, 
                longitudine: $lon_campo,
                indirizzo: $indirizzo_campo,
            );
        }

        $this->Casa = new Squadra(
            id: $id_casa,
            nome: $nome_casa,
            parrocchia: $nome_parrocchia_casa,
            id_parrocchia: $id_parrocchia_casa,
            sport: $nome_sport,
            id_sport: $id_sport,
        );
        $this->Ospiti = new Squadra(
            id: $id_ospiti,
            nome: $nome_ospiti,
            parrocchia: $nome_parrocchia_ospiti,
            id_parrocchia: $id_parrocchia_ospiti,
            sport: $nome_sport,
            id_sport: $id_sport,
        );

        $this->Punteggi = Punteggio::Decompress(
            id: $id_punteggi,
            casa: $punteggi_casa,
            ospiti: $punteggi_ospiti,
            partita: $this->Id
        );
    }

    /** Reconstructs a match and its related teams, venue, and scores from an expanded row. */
    private static function ParseFromRow(array $row): self
    {
        return new self(
            id: $row['id'],
            torneo: $row['id_torneo'],
            data: $row['data'],
            orario: $row['orario'],

            id_campo: $row['id_campo'],
            nome_campo: $row['nome_campo'],
            lat_campo: $row['latitudine_campo'],
            lon_campo: $row['longitudine_campo'],
            indirizzo_campo: $row['indirizzo_campo'],

            id_sport: $row['id_sport'],
            nome_sport: $row['nome_sport'],

            id_casa: $row['id_casa'],
            nome_casa: $row['casa'],
            id_parrocchia_casa: $row['id_parrocchia_casa'],
            nome_parrocchia_casa: $row['nome_parrocchia_casa'],

            id_ospiti: $row['id_ospiti'],
            nome_ospiti: $row['ospiti'],
            id_parrocchia_ospiti: $row['id_parrocchia_ospiti'],
            nome_parrocchia_ospiti: $row['nome_parrocchia_ospiti'],

            id_punteggi: $row['id_punteggi'],
            punteggi_casa: $row['punteggi_casa'],
            punteggi_ospiti: $row['punteggi_ospiti'],
        );
    }

    /** Loads one match from the expanded match view. */
    public static function ById(\mysqli $connection, int $id): ?self
    {
        if (!$connection)
            return null;

        $query = "SELECT * FROM `partite_completo` WHERE `id` = $id";
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
        {
            return null;
        }

        $row = $result->fetch_assoc();
        if (!$row)
        {
            return null;
        }
 
        return self::ParseFromRow(row: $row);
    }

    /** Hydrates the rows returned by a match query using the shared row mapping. */
    private static function ArrayQueryHandler(\mysqli $connection, string $query): array
    {
        if (!$connection)
            return [];
        
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
            return [];

        $arr = [];
        while ($row = $result->fetch_assoc())
            $arr[] = self::ParseFromRow(row: $row);
        return $arr;
    }

    /** Lists matches in active tournaments. */
    public static function All(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: 'SELECT * FROM `partite_tornei_attivi`'
        );
    }

    /** Lists matches scheduled for the current day. */
    public static function Oggi(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: 'SELECT * FROM `partite_oggi`'
        );
    }

    /** Lists matches from the database’s upcoming-match view. */
    public static function DaGiocare(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: 'SELECT * FROM `partite_da_giocare`'
        );
    }

    /** Lists all matches for the given tournament ID or object. */
    public static function Torneo(\mysqli $connection, int|Torneo $torneo): array
    {
        if ($torneo instanceof Torneo)
            $torneo = $torneo->Id;

        return self::ArrayQueryHandler(
            connection: $connection, 
            query: "SELECT * FROM `partite_completo` WHERE `id_torneo` = $torneo" 
        );
    }

    /** Lists matches from the database’s weekly schedule view. */
    public static function Settimana(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: "SELECT * FROM `partite_settimana`" 
        );
    }

    /** Sets or clears the match venue and reports whether a row changed. */
    public static function ImpostaCampo(\mysqli $connection, int $partita, ?int $campo): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(
            query: 'UPDATE `partite` SET `campo` = ? WHERE `id` = ?', 
            params: [
                $campo,
                $partita,
            ],
        );

        if (!$result)
            return false;
        return $connection->affected_rows === 1;
    }

    /** Sets or clears the match time and reports whether a row changed. */
    public static function ImpostaOrario(\mysqli $connection, int $partita, ?string $orario): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(
            query: 'UPDATE `partite` SET `orario` = ? WHERE `id` = ?', 
            params: [
                $orario,
                $partita,
            ],
        );

        if (!$result)
            return false;
        return $connection->affected_rows === 1;
    }

    /** Sets or clears the match date and reports whether a row changed. */
    public static function ImpostaData(\mysqli $connection, int $partita, ?string $data): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(
            query: 'UPDATE `partite` SET `data` = ? WHERE `id` = ?', 
            params: [
                $data,
                $partita,
            ],
        );

        if (!$result)
            return false;
        return $connection->affected_rows === 1;
    }

    /** Removes a match by ID and reports whether a row was deleted. */
    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection)
            return false;

        $result = $connection->query(query: "DELETE FROM `partite` WHERE `id` = $id");
        return (bool)$result && $connection->affected_rows >= 1;
    }

    /** Inserts a blank score for a match and returns the new score ID on success. */
    public static function PunteggioVuoto(\mysqli $connection, int $match): ?int
    {
        if (!$connection)
            return null;

        $result = $connection->query(
            query: "INSERT INTO `punteggi` (`partita`, `home`, `guest`) VALUES ($match, '', '')"
        );

        if (!$result || $connection->affected_rows !== 1)
            return null;
        return $connection->insert_id;
    }
}