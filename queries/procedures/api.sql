DELIMITER //

DROP PROCEDURE IF EXISTS `ApiCallValidate` //
CREATE PROCEDURE `ApiCallValidate`(
    IN `key` VARCHAR(128), 
    IN `ip` VARCHAR(15)
)
BEGIN
    SET @id = NULL;
    SET @hits = NULL;

    SELECT `api_token`.`id` INTO @id 
    FROM `api_token`
    WHERE `api_token`.`key` = `key`;

    SELECT `api_usage`.`hits` INTO @hits
    FROM `api_usage`
    WHERE `api_usage`.`token_id` = @id AND `api_usage`.`ip_address` = ip;

    IF @id IS NOT NULL THEN
        REPLACE INTO `api_usage` (`token_id`, `ip_address`, `hits`)
        VALUES (@id, ip, IFNULL(@hits, 0) + 1);
    END IF;

    SELECT @id AS "id";
END; //

DELIMITER ;