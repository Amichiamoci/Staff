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

    private static function ArrayQueryHandler(\mysqli $connection, string $query): array
    {
        if (!$connection)
            return [];
        
        $result = $connection->query(query: $query);
        if (!$result || $result->num_rows === 0)
        {
            return [];
        }

        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = self::ParseFromRow(row: $row);
        }
        return $arr;
    }

    public static function All(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: 'SELECT * FROM `partite_tornei_attivi`'
        );
    }

    public static function Oggi(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: 'SELECT * FROM `partite_oggi`'
        );
    }

    public static function DaGiocare(\mysqli $connection): array
    {
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: 'SELECT * FROM `partite_da_giocare`'
        );
    }

    public static function Torneo(\mysqli $connection, int|Torneo $torneo): array
    {
        if ($torneo instanceof Torneo)
        {
            $torneo = $torneo->Id;
        }
        return self::ArrayQueryHandler(
            connection: $connection, 
            query: "SELECT * FROM `partite_completo` WHERE `id_torneo` = $torneo" 
        );
    }

    public static function ImpostaCampo(\mysqli $connection, int $partita, ?int $campo): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(query: 'UPDATE `partite` SET `campo` = ? WHERE `id` = ?', params: [
            $campo,
            $partita,
        ]);
        if (!$result)
            return false;
        return $connection->affected_rows === 1;
    }

    public static function ImpostaOrario(\mysqli $connection, int $partita, ?string $orario): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(query: 'UPDATE `partite` SET `orario` = ? WHERE `id` = ?', params: [
            $orario,
            $partita,
        ]);
        if (!$result)
            return false;
        return $connection->affected_rows === 1;
    }

    public static function ImpostaData(\mysqli $connection, int $partita, ?string $data): bool
    {
        if (!$connection)
            return false;
        
        $result = $connection->execute_query(query: 'UPDATE `partite` SET `data` = ? WHERE `id` = ?', params: [
            $data,
            $partita,
        ]);
        if (!$result)
            return false;
        return $connection->affected_rows === 1;
    }

    public static function Delete(\mysqli $connection, int $id): bool
    {
        if (!$connection)
            return false;

        $result = $connection->query(query: "DELETE FROM `partite` WHERE `id` = $id");
        return (bool)$result && $connection->affected_rows >= 1;
    }

    public static function PunteggioVuoto(\mysqli $connection, int $match): ?int
    {
        if (!$connection)
            return null;

        $result = $connection->query(query: "INSERT INTO `punteggi` (`partita`, `home`, `guest`) VALUES ($match, '', '')");
        if (!$result || $connection->affected_rows !== 1)
        {
            return null;
        }

        return $connection->insert_id;
    }
}