<?php

namespace Amichiamoci\Models;

use mysqli;

final class Settings
{
    /** Returns the feature flags seeded by the database schema and editable by administrators. */
    public static function Read(mysqli $connection): array
    {
        $rows = $connection->query('SELECT name, enabled FROM settings')->fetch_all(MYSQLI_ASSOC);
        $flags = array_column($rows, 'enabled', 'name');

        return [
            'subscriptions_enabled' => (bool)($flags['subscriptions_enabled']),
            'teams_enabled' => (bool)($flags['teams_enabled']),
            'tournaments_enabled' => (bool)($flags['tournaments_enabled'] ?? false),
            'api_enabled' => (bool)($flags['api_enabled'] ?? false),
        ];
    }

    /** Saves the feature flags in one statement. */
    public static function Save(
        mysqli $connection,
        bool $subscriptionsEnabled,
        bool $teamsEnabled,
        bool $tournamentsEnabled,
        bool $apiEnabled,
    ): void {
        $connection->execute_query(
            "INSERT INTO settings (name, enabled) VALUES ('subscriptions_enabled', ?), ('teams_enabled', ?), ('tournaments_enabled', ?), ('api_enabled', ?)
            ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)",
            [(int)$subscriptionsEnabled, (int)$teamsEnabled, (int)$tournamentsEnabled, (int)$apiEnabled],
        );
    }
}
