DELIMITER //

DROP PROCEDURE IF EXISTS `StaffList` //
CREATE PROCEDURE `StaffList`(IN anno YEAR, IN all_years BOOLEAN)
body:BEGIN
    IF NOT all_years = 1 THEN
        SELECT s.* 
        FROM `staff_per_edizione` s
        WHERE s.`anno` = IFNULL(anno, YEAR(CURRENT_DATE));
        LEAVE body;
    END IF;

    SELECT * FROM `partecipazioni_staff`;
END; //

DROP PROCEDURE IF EXISTS `PartecipaStaff` //
CREATE PROCEDURE `PartecipaStaff` (
    IN staff_id INT, 
    IN edizione_id INT, 
    IN taglia VARCHAR(3), 
    IN commissioni VARCHAR(64), 
    IN referente BOOLEAN)
proc_body:BEGIN
    DECLARE gia_partecipa INT DEFAULT 0;
    DECLARE element VARCHAR(128);

    SELECT p.`id` INTO gia_partecipa 
    FROM `partecipaz_staff_ediz` AS p
    WHERE p.`staff` = staff_id AND p.`edizione` = edizione_id;

    IF gia_partecipa > 0 THEN
        UPDATE `partecipaz_staff_ediz` 
            SET `maglia` = taglia
        WHERE `id` = gia_partecipa;
        LEAVE proc_body;
    END IF;

    INSERT INTO `partecipaz_staff_ediz` (`staff`, `edizione`, `maglia`, `is_referente`) VALUES 
        (staff_id, edizione_id, taglia, referente);
    
    SET @arr = commissioni;
    WHILE @arr != '' DO
        SET element = SUBSTRING_INDEX(@arr, ',', 1);      
        
        INSERT INTO `ruoli_staff` (`commissione`, `staffista`, `edizione`) VALUES
            (`element`, `staff_id`, `edizione_id`);
        
        IF LOCATE(',', @arr) > 0 THEN
            SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
        ELSE
            SET @arr = '';
        END IF;
    END WHILE;
END; //

DROP PROCEDURE IF EXISTS `StaffData` //
CREATE PROCEDURE `StaffData` (IN staff_id INT, IN anno YEAR)
BEGIN
    SELECT 
        IFNULL (parr.`nome`, 'Non specificata') AS "parrocchia",
        IFNULL (parr.`id`, 0) "AS id_parrocchia",
        IFNULL (r.`comm`, 'Nessuna commissione') AS "commissioni",
        IFNULL (r.`tot_comm`, 0) AS "totale_commissioni",
        IFNULL (r.`maglia`, 'Non scelta') AS "maglia",
        IFNULL (r.`is_referente`, 0) AS "referente",
        IFNULL (a.`codice_fiscale`, '') AS "cf",
        CONCAT (a.`nome`, ' ', a.`cognome`) AS "nome"
    FROM `staffisti` AS s
        INNER JOIN `anagrafiche` a ON a.`id` = s.`dati_anagrafici`
        LEFT OUTER JOIN `parrocchie` parr ON parr.`id` = s.`parrocchia`
        LEFT OUTER JOIN (
            SELECT 
                p.`maglia`, 
                p.`staff`, 
                p.`is_referente`,
                GROUP_CONCAT(DISTINCT c.`nome` SEPARATOR ', ') AS "comm",
                COUNT(DISTINCT c.`nome`) AS "tot_comm"
            FROM `edizioni` AS e
                LEFT OUTER JOIN `partecipaz_staff_ediz` p ON e.`id` = p.`edizione`
                LEFT OUTER JOIN `ruoli_staff` r ON r.`staffista` = p.`staff` AND r.`edizione` = e.`id`
                LEFT OUTER JOIN `commissioni` c ON r.`commissione` = c.`id`
            WHERE e.`anno` = anno
            GROUP BY p.`staff`
        ) r ON r.`staff` = s.`id`
    WHERE s.`id` = staff_id;
END; //

DROP PROCEDURE IF EXISTS `GetStaffFromUserId` //
CREATE PROCEDURE `GetStaffFromUserId` (IN `id` INT)  BEGIN
    SELECT CONCAT(a.nome, ' ', a.cognome) AS nome, a.id AS id_anagrafica, s.id AS staffista
    FROM anagrafiche AS a 
        INNER JOIN staffisti s ON a.id = s.dati_anagrafici
    WHERE s.id_utente = id;
END //

DELIMITER ;