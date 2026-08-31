<?php

namespace Amichiamoci\Models;

class Distinta extends Squadra
{
    public array $MembriDistinta = [];

    public static function ById(\mysqli $connection, int $id): ?self
    {
        $team = parent::ById(connection: $connection, id: $id);
        if ($team === null)
            return null;

        $query = "SELECT * FROM `distinte` WHERE `squadra_id` = $id ORDER BY `chi` ASC";
        $result = $connection->query(query: $query);
        if (!$result)
            return null;

        $members = [];
        while ($row = $result->fetch_assoc())
        {
            $problems = [];
            foreach (['doc_problem', 'scadenza_problem', 'certificato_problem', 'tutore_problem', 'doc_code_problem'] as $field)
            {
                if (array_key_exists(key: $field, array: $row) && is_string(value: $row[$field]) && trim(string: $row[$field]) !== '')
                {
                    $problems[] = trim(string: $row[$field]);
                }
            }

            $members[] = [
                'Id' => (int)($row['id'] ?? 0),
                'AnagraficaId' => (int)($row['id'] ?? 0),
                'IscrizioneId' => (int)($row['iscrizione'] ?? 0),
                'Nome' => (string)($row['chi'] ?? ''),
                'Sesso' => is_string(value: $row['sesso'] ?? null) ? $row['sesso'] : '?',
                'HaProblemi' => count(value: $problems) > 0,
                'Problemi' => $problems,
            ];
        }
        $result->close();

        $distinta = new self(
            id: $team->Id,
            nome: $team->Nome,
            parrocchia: $team->Parrocchia->Nome,
            id_parrocchia: $team->Parrocchia->Id,
            sport: $team->Sport->Nome,
            id_sport: $team->Sport->Id,
            membri: $team->Membri,
            iscrizione_membri: $team->IdIscritti,
            referenti: $team->Referenti,
        );
        $distinta->MembriDistinta = $members;

        return $distinta;
    }
}
