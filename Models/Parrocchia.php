<?php
namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\NomeIdSemplice;

class Parrocchia extends NomeIdSemplice
{
    protected static function Table(): string { return "parrocchie"; }
    public static function ByUserId(\mysqli $connection, int $user) : ?self
    {
        if (!$connection || $user <= 0)
            return null;
        $query  = "SELECT *
        FROM `parrocchie`
            INNER JOIN `staffisti` ON `staffisti`.`parrocchia` = `parrocchie`.`id`
        WHERE `staffisti`.`id_utente` = $user
        LIMIT 1";

        $result = $connection->query(query: $query);

        if (!$result || $result->num_rows === 0)
        {
            return null;
        }
        if ($row = $result->fetch_assoc())
        {
            return new self(id: $row["id"], nome: $row["nome"]);
        }
        return null;
    }
}