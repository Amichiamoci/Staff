DELIMITER //

DROP PROCEDURE IF EXISTS `ApiCallValidate` //
CREATE PROCEDURE `ApiCallValidate`(
    IN `key` VARCHAR(128), 
    IN `ip` VARCHAR(15)
)
BEGIN
    DECLARE @id INT DEFAULT NULL;
    DECLARE @hits INT DEFAULT 0;

    SELECT `api_token`.`id` INTO @id 
    FROM `api_token`
    WHERE `api_token`.`key` = `key`;

    SELECT `api_usage`.`hits` INTO @hits
    FROM `api_usage`
    WHERE `api_usage`.`token_id` = @id AND `api_usage`.`ip` = ip;

    IF @id IS NOT NULL THEN
        REPLACE INTO `api_usage` (`token_id`, `ip_address`, `hits`)
        VALUES (`key`, `ip`, IFNULL(@hits, 0) + 1);
    END IF;

    SELECT @id AS "id";
END; //

DELIMITER ;