<?php

class PunteggioParrocchia
{
    public int $edizione = 0;
    public int $id_parrocchia = 0;
    public string $parrocchia = "";
    public string $punteggio = "";
    public function __construct(
        string|int|null $edizione,
        string|int|null $id_parrocchia,
        string|null $parrocchia,
        string|null $punteggio
    ) {
        if (isset($edizione))
            $this->edizione = (int)$edizione;
        if (isset($id_parrocchia))
            $this->id_parrocchia = (int)$id_parrocchia;
        if (isset($parrocchia) && is_string($parrocchia))
            $this->parrocchia = $parrocchia;
        if (isset($punteggio) && is_string($punteggio))
            $this->punteggio = $punteggio;
    }

    public static function Insert(
        mysqli $connection,
        string|int|null $edizione,
        string|int|null $parrocchia,
        string|null $punteggio
    ) : bool
    {
        if (!$connection)
            return false;
        $p = new PunteggioParrocchia($edizione, $parrocchia, null, $punteggio);
        if ($p->parrocchia === 0 || $p->edizione === 0)
            return false;
        $p->punteggio = $connection->real_escape_string($p->punteggio);
        $query = "REPLACE INTO `punteggio_parrocchia` (`parrocchia`, `edizione`, `punteggio`) VALUES ($p->id_parrocchia, $p->edizione, '$p->punteggio')";
        return (bool)$connection->query($query);
    }
    public static function All(
        mysqli $connection,
        int $id_edizione
    ) : array
    {
        if (!$connection)
            return array();
        $query = "SELECT l.`id`, l.`nome`, IFNULL (p.`punteggio`, '?') AS \"punti\" 
            FROM `lista_parrocchie_partecipanti` l
                LEFT OUTER JOIN `punteggio_parrocchia` p ON l.`id` = p.`parrocchia`
            WHERE p.`edizione` = $id_edizione
            ORDER BY l.`nome` ASC";
        $result = $connection->query($query);
        if (!$result)
            return array();
        $arr = array();
        while ($row = $result->fetch_assoc())
        {
            $arr[] = new PunteggioParrocchia($id_edizione, $row["id"], $row["nome"], $row["punti"]);
        }
        return $arr;
    }
}