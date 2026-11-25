<?php

declare(strict_types=1);

namespace Amichiamoci\Api;

use mysqli;

final class Login
{
    /** Records a Web App login and returns whether the email has an active Portal admin account. */
    public static function record(mysqli $db, string $email, int $tokenId, string $ip): bool
    {
        $admin = $db->execute_query(
            'SELECT 1
             FROM `api_admin_emails`
             WHERE `email` = ?
             LIMIT 1',
            [$email],
        )->fetch_row() !== null;

        $db->execute_query(
            'INSERT INTO `web_app_logins` (`email`, `token_id`, `ip_address`)
             VALUES (?, ?, ?)',
            [$email, $tokenId, $ip],
        );

        return $admin;
    }

    /** Lists the newest login records starting at the given offset, with optional token and profile associations. */
    public static function recent(mysqli $db, int $offset, int $limit): array
    {
        return $db->execute_query(
            'SELECT *
             FROM `api_logins`
             ORDER BY `logged_at` DESC, `email` DESC, `ip_address` DESC
             LIMIT ? OFFSET ?',
            [$limit, $offset],
        )->fetch_all(MYSQLI_ASSOC);
    }
}
