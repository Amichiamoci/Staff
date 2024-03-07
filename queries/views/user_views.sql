CREATE OR REPLACE VIEW users_extended AS
SELECT 
    u.id, 
    u.user_name, 
    u.is_admin, 
    u.is_blocked, 
    MAX(s.time_log) AS last_seen_time,
    PrintOnlineTime(MAX(s.time_log)) AS last_seen
FROM utenti AS u
    LEFT OUTER JOIN sessioni s ON s.user_id = u.id
GROUP BY u.id
ORDER BY last_seen_time DESC, u.user_name DESC;