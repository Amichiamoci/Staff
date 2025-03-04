<?php
namespace Amichiamoci\Models;
use Amichiamoci\Models\Templates\NomeIdSemplice;

class Parrocchia extends NomeIdSemplice
{
    public static function Table(): string { return "parrocchie"; }
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

    public function Maglie(\mysqli $connection, int $year) : array
    {
        if (!$connection || $this->Id === 0) {
            return [];
        }

        $query = "SELECT * FROM `anni_parrocchie_taglie` WHERE `anno` = $year AND `parrocchia` = " . $this->Id;
        $res = $connection->query(query: $query);
        if (!$res) {
            return [];
        }

        $arr = [];
        while ($row = $res->fetch_assoc())
        {
            $arr[] = [
                'taglia' => $row['taglia'],
                'numero' => (int)$row['numero'],
            ];
        }
        return $arr;
    }
}