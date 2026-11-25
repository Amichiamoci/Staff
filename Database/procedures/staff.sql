DELIMITER //

DROP PROCEDURE IF EXISTS `PartecipaStaff` //
CREATE PROCEDURE `PartecipaStaff` (
    IN staff_id INT,
    IN edizione_id INT,
    IN taglia VARCHAR(3),
    IN commissioni VARCHAR(64))
BEGIN
    DECLARE gia_partecipa INT DEFAULT 0;
    DECLARE element VARCHAR(128);

    SELECT p.`id`
    INTO gia_partecipa
    FROM `partecipaz_staff_ediz` AS p
    WHERE p.`staff` = staff_id AND p.`edizione` = edizione_id;

    IF gia_partecipa > 0 THEN
        UPDATE `partecipaz_staff_ediz`
        SET `maglia` = taglia
        WHERE `id` = gia_partecipa;

        DELETE
        FROM `ruoli_staff`
        WHERE `ruoli_staff`.`staffista` = staff_id AND `ruoli_staff`.`edizione` = edizione_id;
    ELSE
        INSERT INTO `partecipaz_staff_ediz` (`staff`, `edizione`, `maglia`)
        VALUES (staff_id, edizione_id, taglia);
    END IF;


    SET @arr = commissioni;
    WHILE @arr != '' DO
        SET element = SUBSTRING_INDEX(@arr, ',', 1);

        REPLACE INTO `ruoli_staff` (`commissione`, `staffista`, `edizione`)

      VALUES
            (element, staff_id, edizione_id);

        IF LOCATE(',', @arr) > 0 THEN
            SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
        ELSE
            SET @arr = '';
        END IF;
    END WHILE;
END; //

DROP PROCEDURE IF EXISTS `GetStaffFromUserId` //
CREATE PROCEDURE `GetStaffFromUserId` (IN `id` INT)  BEGIN
    SELECT
        CONCAT(a.`nome`, ' ', a.`cognome`) AS "nome",
        a.`id`                             AS "id_anagrafica",
        s.`id`                             AS "staffista"
    FROM `anagrafiche` AS a
    	INNER JOIN `staffisti` s ON a.`id` = s.`dati_anagrafici`
    WHERE s.`id_utente` = id;
END //

DELIMITER ;
