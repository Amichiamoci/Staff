CREATE OR REPLACE VIEW `users_extended` AS
SELECT 
    u.`id`, 
    u.`user_name`, 
    u.`is_admin`, 
    u.`is_blocked`, 
    MAX(s.`time_log`) AS "last_seen_time",
    `PrintOnlineTime`(MAX(s.`time_log`)) AS "last_seen",
    MIN(staff.`id`) AS "staff_id",
    MIN(staff.`dati_anagrafici`) AS "anagrafica_id",
    MIN(CONCAT(a.`nome`, ' ', a.`cognome`)) AS "full_name"
FROM `utenti` AS u
    LEFT OUTER JOIN `sessioni` s ON s.`user_id` = u.`id`
    LEFT OUTER JOIN `staffisti` staff ON staff.`id_utente` = u.`id`
    LEFT OUTER JOIN `anagrafiche` a ON a.`id` = staff.`dati_anagrafici`
GROUP BY u.`id`
ORDER BY `last_seen_time` DESC, u.`user_name` DESC;