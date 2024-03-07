DELIMITER //

DROP FUNCTION IF EXISTS PrintOnlineTime //
CREATE FUNCTION PrintOnlineTime(time_log TIMESTAMP)
RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
    DECLARE time_diff INT DEFAULT 0;

    IF time_log IS NULL THEN
        RETURN 'Mai collegato';
    END IF;

    SET time_diff = TIMESTAMPDIFF(MINUTE, time_log, CURRENT_TIMESTAMP);

    RETURN (CASE 
        WHEN time_diff < 5 THEN 'Online adesso'
        WHEN time_diff < 181 THEN CONCAT("Online ", time_diff, " minuti fa")
        ELSE DATE_FORMAT(
            time_log, 
            IF (
                TIMESTAMPDIFF(DAY, time_log, CURRENT_TIMESTAMP) = 0 AND 
                DAY(time_log) = DAY(CURRENT_TIMESTAMP), 
                    'Online oggi alle %H:%i',
                    'Online il %d/%m/%Y alle %H:%i'
                )
            ) END
        );
END //

DELIMITER ;