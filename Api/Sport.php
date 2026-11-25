<?php

declare(strict_types=1);

namespace Amichiamoci\Api;

use mysqli;

final class Sport
{
    public function __construct(private mysqli $db)
    {
    }

    /** Returns ranked parish scores for the current edition. */
    public function leaderboard(): array
    {
        return $this->rows(
            'SELECT
                 `id`                        AS "parishId",
                 `nome`                      AS "parishName",
                 CAST(`punteggio` AS SIGNED) AS "points",
                 `posizione`                 AS "rankPosition"
             FROM `classifica_parrocchie`
             ORDER BY `posizione`, `nome`, `id`'
        );
    }

    /** Lists current tournaments with counts of scheduled matches and recorded results. */
    public function tournaments(): array
    {
        return $this->rows(
            'SELECT *
             FROM `api_tournaments`
             ORDER BY `name`, `id`'
        );
    }

    /** Returns standings for a current tournament, or an empty list if it has no standings. */
    public function standings(int $tournament): array
    {
        return $this->rows(
            'SELECT `teamId`, `teamName`, `parishId`, `points`, `matchesRemaining`, `matchesPlanned`
             FROM `api_tournament_standings`
             WHERE `tournamentId` = ?
             ORDER BY `points` DESC, `teamName`, `teamId`',
            [$tournament],
        );
    }

    /** Lists current matches, optionally filtered by tournament and either side's team ID. */
    public function matches(?int $tournament, ?int $team): array
    {
        return $this->matchList(
            '(? IS NULL OR `tournamentId` = ?) AND (? IS NULL OR `homeId` = ? OR `awayId` = ?)',
            [$tournament, $tournament, $team, $team, $team],
        );
    }

    /** Lists today's current-edition matches with a home or away team from the parish. */
    public function todayMatches(int $parish): array
    {
        return $this->matchList(
            '`date` = CURRENT_DATE AND (`homeParishId` = ? OR `awayParishId` = ?)',
            [$parish, $parish],
        );
    }

    private function matchList(string $filter, array $parameters): array
    {
        $rows = $this->rows(
            'SELECT *
             FROM `api_matches`
             WHERE ' . $filter . '
             ORDER BY `date` IS NULL, `date`, `time` IS NULL, `time`, `id`, `scoreId`',
            $parameters,
        );

        $matches = [];
        foreach ($rows as $row) {
            $id = $row['id'];
            $matches[$id] ??= [
                'id' => $id,
                'tournamentId' => $row['tournamentId'],
                'date' => $row['date'],
                'time' => $row['time'],
                'fieldId' => $row['fieldId'],
                'forfeitWinnerTeamId' => $row['forfeitWinnerTeamId'],
                'homeTeam' => ['id' => $row['homeId'], 'name' => $row['homeName']],
                'awayTeam' => ['id' => $row['awayId'], 'name' => $row['awayName']],
                'scores' => [],
            ];
            if ($row['scoreId'] !== null) {
                $matches[$id]['scores'][] = ['home' => $row['homeScore'], 'away' => $row['awayScore']];
            }
        }
        return array_values($matches);
    }

    /** Lists current teams and the managed profiles who are members of each team. */
    public function teams(string $email): array
    {
        return $this->db->execute_query(
            'SELECT DISTINCT a.`teamId`, a.`teamName`, a.`sportId`, a.`personId`
             FROM `api_team_members` a
             WHERE ' . Iscrizioni::CanAccess('a.`personId`') . '
             ORDER BY a.`teamName`, a.`teamId`, a.`personId`',
            [$email, $email],
        )->fetch_all(MYSQLI_ASSOC);
    }

    private function rows(string $query, array $parameters = []): array
    {
        return $this->db->execute_query($query, $parameters)->fetch_all(MYSQLI_ASSOC);
    }
}
