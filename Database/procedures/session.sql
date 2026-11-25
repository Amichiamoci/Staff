DELIMITER //

DROP PROCEDURE IF EXISTS UpdateSession //
CREATE PROCEDURE UpdateSession(IN sess_id INT)
BEGIN
    IF sess_id <> 0 THEN
        UPDATE `sessioni`
        SET `time_log` = CURRENT_TIMESTAMP
        WHERE `id` = sess_id;
    END IF;
END; //

DROP PROCEDURE IF EXISTS StartSession //
CREATE PROCEDURE StartSession(IN u_id INT, IN flag VARCHAR(64), IN dev_ip VARCHAR(32))
BEGIN
    DECLARE session_id INT DEFAULT 0;
    IF u_id <> 0 THEN
        INSERT INTO `sessioni` (`user_id`, `user_flag`, `device_ip`)

      VALUES (u_id, flag, dev_ip);
        SET session_id = LAST_INSERT_ID();
    END IF;
    SELECT session_id;
END; //

DELIMITER ;
