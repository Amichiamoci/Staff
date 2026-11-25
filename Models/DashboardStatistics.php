<?php

namespace Amichiamoci\Models;

use mysqli;

final class DashboardStatistics
{
    /** Counts each enrollment once using the existing issue list for each parish in scope. */
    public static function RegistrationReadiness(mysqli $connection, int $year, ?int $parish): array
    {
        $parishes = $connection->execute_query(
            'SELECT `id`, `nome`, `enrolled`
             FROM `dashboard_enrollment_counts`
             WHERE `anno` = ? AND (? IS NULL OR `id` = ?)
             ORDER BY `enrolled` DESC, `nome`, `id`',
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
            'SELECT `anno`, `total`, `first_time`
             FROM `dashboard_participation`
             WHERE `anno` <= ?
             ORDER BY `anno`',
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
            'SELECT `size`, `quantity`, `staff`
             FROM `dashboard_shirt_counts`
             WHERE `edizione` = ? AND (? IS NULL OR `parrocchia` = ?)',
            [$edition, $parish, $parish],
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
            'SELECT `id`, `nome`, `area`, `incomplete`, `scheduled`, `result`
             FROM `dashboard_tournament_readiness`
             WHERE `edizione` = ?
             ORDER BY `incomplete` DESC, `scheduled` DESC, `nome`, `id`',
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
