DELIMITER //

DROP PROCEDURE IF EXISTS `StopMaintenance` //
CREATE PROCEDURE `StopMaintenance`()
BEGIN
    UPDATE `server_var`
    SET `server_var`.`value` = 'ONLINE'
    WHERE LOWER(`server_var`.`name`) = 'status';
END; //

DROP PROCEDURE IF EXISTS `StartMaintenance` //
CREATE PROCEDURE `StartMaintenance`()
BEGIN
    UPDATE `server_var`
    SET `server_var`.`value` = 'IN MANUTENZIONE'
    WHERE LOWER(`server_var`.`name`) = 'status';
END; //

DROP PROCEDURE IF EXISTS `BlockSystem` //
CREATE PROCEDURE `BlockSystem`()
BEGIN
    UPDATE `server_var`
    SET `server_var`.`value` = 'OFFLINE'
    WHERE LOWER(`server_var`.`name`) = 'status';
END; //

DROP PROCEDURE IF EXISTS `GetSystemStatus` //
CREATE PROCEDURE `GetSystemStatus`(OUT ret VARCHAR(256))
BEGIN
    SELECT `server_var`.`value` INTO ret
    FROM `server_var`
    WHERE LOWER(`server_var`.`name`) = 'status';
END; //

DROP PROCEDURE IF EXISTS `SelectSystemStatus` //
CREATE PROCEDURE `SelectSystemStatus`()
BEGIN
    CALL `GetSystemStatus`(@status);
    SELECT @status AS "status";
END; //

DROP PROCEDURE IF EXISTS `IsSystemAccessible` //
CREATE PROCEDURE `IsSystemAccessible`()
BEGIN
    CALL `GetSystemStatus`(@status);

    IF @status IS NULL OR NOT LOWER(@status) = 'online' THEN
        SELECT 'No' AS 'result';
    ELSE
        SELECT 'Yes' AS 'result';
    END IF;
END; //

DELIMITER ;