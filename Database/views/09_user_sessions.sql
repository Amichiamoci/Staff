CREATE OR REPLACE VIEW `user_sessions` AS
SELECT
    s.`id`,
    u.`id` AS "user_id",
    u.`user_name`,
    s.`time_start`,
    s.`time_log`,
    s.`user_flag`,
    s.`device_ip`
FROM `sessioni` s
	INNER JOIN `utenti` u ON u.`id` = s.`user_id`;
