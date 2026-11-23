<?php

namespace Amichiamoci\Models;

use mysqli;

final class DashboardStatistics
{
    /** Counts each enrollment once using the existing issue list for each parish in scope. */
    public static function RegistrationReadiness(mysqli $connection, int $year, ?int $parish): array
    {
        $parishes = $connection->execute_query(
            'SELECT p.id, p.nome, COUNT(*) AS enrolled
            FROM iscritti i
            JOIN edizioni e ON e.id = i.edizione
            JOIN parrocchie p ON p.id = i.parrocchia
            WHERE e.anno = ? AND (? IS NULL OR p.id = ?)
            GROUP BY p.id, p.nome
            ORDER BY enrolled DESC, p.nome, p.id',
            [$year, $parish, $parish],
        )->fetch_all(MYSQLI_ASSOC);

        $rows = [];
        $counts = ['Certificati mancanti' => 0, 'Tutori mancanti' => 0, 'Problemi con i documenti' => 0];
        foreach ($parishes as $row) {
            $id = (int)$row['id'];
            $issues = ProblemaIscrizione::Parrocchia($connection, $year, $id);
            foreach ($issues as $issue) {
                $counts['Certificati mancanti'] += (int)($issue->Certificato !== null);
                $counts['Tutori mancanti'] += (int)($issue->Tutore !== null);
                $counts['Problemi con i documenti'] += (int)($issue->Documento !== null
                    || $issue->ScadenzaDocumento !== null || $issue->CodiceDocumento !== null);
            }
            $count = count($issues);
            $rows[] = [
                'label' => $row['nome'],
                'url' => "/staff?church=$id&year=$year",
                'values' => [(int)$row['enrolled'] - $count, $count],
            ];
        }
        return ['rows' => $rows, 'issues' => $counts];
    }

    /** Counts distinct people through the current year, separating first-time and returning participants. */
    public static function Participation(mysqli $connection, int $currentYear): array
    {
        $result = $connection->execute_query(
            'SELECT e.anno, COUNT(DISTINCT i.dati_anagrafici) AS total,
                COUNT(DISTINCT CASE WHEN f.first_year = e.anno THEN i.dati_anagrafici END) AS first_time
            FROM edizioni e
            LEFT JOIN iscritti i ON i.edizione = e.id
            LEFT JOIN (
                SELECT i.dati_anagrafici, MIN(e.anno) AS first_year
                FROM iscritti i JOIN edizioni e ON e.id = i.edizione
                GROUP BY i.dati_anagrafici
            ) f ON f.dati_anagrafici = i.dati_anagrafici
            WHERE e.anno <= ?
            GROUP BY e.anno
            ORDER BY e.anno',
            [$currentYear],
        );

        $rows = [];
        foreach ($result as $row) {
            $year = (int)$row['anno'];
            $total = (int)$row['total'];
            $firstTime = (int)$row['first_time'];
            $rows[] = [
                'label' => $year === $currentYear ? "$year (finora)" : (string)$year,
                'values' => [$total, $firstTime, $total - $firstTime],
            ];
        }
        return $rows;
    }

    /** Counts shirts for participant and staff registrations in size order, with unassigned sizes last. */
    public static function ShirtDemand(mysqli $connection, int $edition, ?int $parish): array
    {
        $result = $connection->execute_query(
            'SELECT taglia_maglietta AS size, COUNT(*) AS quantity, 0 AS staff
            FROM iscritti
            WHERE edizione = ? AND (? IS NULL OR parrocchia = ?)
            GROUP BY taglia_maglietta
            UNION ALL
            SELECT p.maglia AS size, COUNT(*) AS quantity, 1 AS staff
            FROM partecipaz_staff_ediz p JOIN staffisti s ON s.id = p.staff
            WHERE p.edizione = ? AND (? IS NULL OR s.parrocchia = ?)
            GROUP BY p.maglia',
            [$edition, $parish, $parish, $edition, $parish, $parish],
        );

        $counts = array_fill_keys([...Taglia::All(), 'Da assegnare'], [0, 0]);
        foreach ($result as $row) {
            $size = strtoupper(trim($row['size'] ?? ''));
            $size = array_key_exists($size, $counts) ? $size : 'Da assegnare';
            $counts[$size][(int)$row['staff']] += (int)$row['quantity'];
        }

        $rows = [];
        foreach ($counts as $size => $values) {
            if (array_sum($values) > 0) {
                $rows[] = ['label' => $size, 'values' => $values];
            }
        }
        return $rows;
    }

    /** Groups tournament match counts by sport area and includes results assigned by forfeit. */
    public static function TournamentReadiness(mysqli $connection, int $edition): array
    {
        $result = $connection->execute_query(
            "SELECT id, nome, area, SUM(status = 'incomplete') AS incomplete,
                SUM(status = 'scheduled') AS scheduled, SUM(status = 'result') AS result
            FROM (
                SELECT t.id, t.nome, UPPER(TRIM(s.area)) AS area, CASE
                    WHEN p.id IS NULL THEN 'no_matches'
                    WHEN p.a_tavolino IS NOT NULL OR EXISTS (SELECT 1 FROM punteggi r WHERE r.partita = p.id) THEN 'result'
                    WHEN p.data IS NULL OR p.orario IS NULL OR p.campo IS NULL THEN 'incomplete'
                    ELSE 'scheduled'
                END AS status
                FROM tornei t JOIN sport s ON s.id = t.sport
                LEFT JOIN partite p ON p.torneo = t.id
                WHERE t.edizione = ?
            ) matches
            GROUP BY id, nome, area
            ORDER BY incomplete DESC, scheduled DESC, nome, id",
            [$edition],
        );

        $groups = ['Calcio' => [], 'Pallavolo' => [], 'Basket' => []];
        foreach ($result as $row) {
            $id = (int)$row['id'];
            $area = match ($row['area']) {
                'PALLACANESTRO' => 'Basket',
                '' => 'Altri sport',
                default => ucfirst(strtolower($row['area'])),
            };
            $groups[$area][] = [
                'label' => $row['nome'],
                'url' => "/sport/tournament?id=$id",
                'values' => [(int)$row['incomplete'], (int)$row['scheduled'], (int)$row['result']],
            ];
        }

        return $groups;
    }
}
