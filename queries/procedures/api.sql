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

DROP PROCEDURE IF EXISTS `GetAppUserClaims` //
CREATE PROCEDURE `GetAppUserClaims`(
    IN email VARCHAR(128)
)
BEGIN
    SET @is_staff = FALSE;
    SET @is_admin = FALSE;

    IF EXISTS(SELECT * FROM `staff_attuali` WHERE LOWER(`staff_attuali`.`email`) = LOWER(email)) THEN
        SET @is_staff = TRUE;
    END IF;
    
    IF EXISTS(
        SELECT * 
        FROM `users_extended` u 
            INNER JOIN `anagrafiche` a ON a.`id` = u.`anagrafica_id`
        WHERE u.`is_admin` AND NOT u.`is_blocked` AND LOWER(a.`email`) = LOWER(email)
    ) THEN
        SET @is_admin = TRUE;
    END IF;

    SELECT @is_staff AS "referee", @is_admin AS "admin";
END; //

DELIMITER ;