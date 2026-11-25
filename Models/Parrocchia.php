<?php
namespace Amichiamoci\Models;

use Amichiamoci\Models\Templates\NomeIdSemplice;

class Parrocchia extends NomeIdSemplice
{
    public static function Table(): string { return "parrocchie"; }

    /** Resolves the parish associated with a staff user account. */
    public static function ByUserId(\mysqli $connection, int $user) : ?self
    {
        if (!$connection || $user <= 0)
            return null;
        $query  = "SELECT `id`, `nome`
        FROM `parrocchie_staff`
        WHERE `id_utente` = $user
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

    /** Loads the shirt sizes assigned to the parish for one year. */
    public function Maglie(\mysqli $connection, int $year) : array
    {
        if (!$connection || $this->Id === 0)
            return [];

        $query = "SELECT `taglia`, `numero`
            FROM `anni_parrocchie_taglie`
            WHERE `anno` = ? AND `parrocchia` = ?";
        $res = $connection->execute_query(
            query: $query, 
            params: [
                $year,
                $this->Id,
            ],
        );
        if (!$res) 
            return [];

        return $res->fetch_all(mode: MYSQLI_ASSOC);
    }
}