<?php

namespace Amichiamoci\Models;

class PunteggioParrocchia
{
    public int $Edizione;
    public Parrocchia $Parrocchia;
    public ?string $Punteggio = null;

    public function __construct(
        string|int $edizione,
        string|int $id_parrocchia,
        string $parrocchia,
        ?string $punteggio
    ) {
        $this->Edizione = (int)$edizione;
        $this->Parrocchia = new Parrocchia(
            id: $id_parrocchia,
            nome: $parrocchia
        );
        $this->Punteggio = $punteggio;
    }

    public static function Insert(
        \mysqli $connection,
        string|int $edizione,
        string|int $parrocchia,
        ?string $punteggio
    ) : bool {
        if (!$connection)
            return false;
        
        $edizione = (int)$edizione;
        $parrocchia = (int)$parrocchia;
        
        if ($edizione === 0 || $parrocchia === 0)
            return false;

        $query = "REPLACE INTO `punteggio_parrocchia` (`parrocchia`, `edizione`, `punteggio`) VALUES (?, ?, ?)";
        return (bool)$connection->execute_query($query, [$parrocchia, $edizione, $punteggio]);
    }
    public static function All(
        \mysqli $connection,
        int $year
    ) : array {
        if (!$connection)
            return [];

        $query = "SELECT l.*, p.`punteggio` AS \"punti\" 
            FROM `lista_parrocchie_partecipanti` l
                LEFT OUTER JOIN `punteggio_parrocchia` p ON l.`id` = p.`parrocchia` AND l.`id_edizione` = p.`edizione`
            WHERE l.`anno` = $year
            ORDER BY l.`nome` ASC";
        
        $result = $connection->query($query);
        if (!$result)
            return [];
        
        $arr = [];
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new self(
                edizione: $row['id_edizione'],
                id_parrocchia: $row["id"],
                parrocchia: $row["nome"],
                punteggio: $row["punti"]
            );
        }
        return $arr;
    }
}