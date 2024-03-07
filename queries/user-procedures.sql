DELIMITER //

DROP PROCEDURE IF EXISTS AllUsers //
CREATE PROCEDURE AllUsers()
BEGIN
    
END; //

DROP PROCEDURE IF EXISTS UsersActivity //
CREATE PROCEDURE UsersActivity(IN lim INT)
BEGIN
    IF lim IS NOT NULL AND lim > 0 THEN
        SELECT DISTINCT u.user_name, s.time_log, s.time_start, s.user_flag, s.device_ip
        FROM sessioni AS s
        LEFT JOIN (utenti AS u) ON u.id = s.user_id
        WHERE s.time_log > s.time_start
        ORDER BY s.time_start DESC, s.time_log DESC, u.user_name
        LIMIT lim;
    ELSE
        SELECT DISTINCT u.user_name, s.time_log, s.time_start, s.user_flag, s.device_ip
        FROM sessioni AS s
        LEFT JOIN (utenti AS u) ON u.id = s.user_id
        WHERE s.time_log > s.time_start
        ORDER BY s.time_start DESC, s.time_log DESC, u.user_name;
    END IF;
END; //

DELIMITER ;