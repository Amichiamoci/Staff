DELIMITER //

DROP PROCEDURE IF EXISTS `CreaSquadra` //
CREATE PROCEDURE `CreaSquadra`(
    IN _nome VARCHAR(128), 
    IN _parrocchia INT, 
    IN _sport INT, 
    IN _membri VARCHAR(2048), 
    IN _edizione INT,
    IN _referenti VARCHAR(2048))
BEGIN
    DECLARE element VARCHAR(512);
    DECLARE id INT DEFAULT 0;
    
    INSERT INTO `squadre` (`nome`, `parrocchia`, `sport`, `edizione`, `referenti`) VALUES 
    (_nome, _parrocchia, _sport, _edizione, TRIM(_referenti));
    
    SET id = LAST_INSERT_ID();

    IF id <> 0 THEN
        SET @arr = _membri;
        WHILE @arr != '' DO
            SET element = SUBSTRING_INDEX(@arr, ',', 1);
            
            INSERT INTO `squadre_iscritti` (`squadra`, `iscritto`) VALUES (id, element);
            
            IF LOCATE(',', @arr) > 0 THEN
                SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
            ELSE
                SET @arr = '';
            END IF;
        END WHILE;
    END IF;

    SELECT id;
END; //

DROP PROCEDURE IF EXISTS `ModificaSquadra` //
CREATE PROCEDURE `ModificaSquadra`(
    IN _id INT, 
    IN _nome VARCHAR(128), 
    IN _parrocchia INT, 
    IN _sport INT, 
    IN _membri VARCHAR(2048),
    IN _referenti VARCHAR(2048))
proc_body:BEGIN

    DECLARE element VARCHAR(512) DEFAULT NULL;
    
    IF NOT EXISTS (SELECT * FROM `squadre` WHERE `squadre`.`id` = _id) THEN
        SELECT 0 AS "id";
        LEAVE proc_body;
    END IF;

    -- Aggiorno info sulla squadra
    UPDATE `squadre`
    SET `squadre`.`nome` = _nome,
        `squadre`.`parrocchia` = _parrocchia,
        `squadre`.`sport` = _sport,
        `squadre`.`referenti` = TRIM(_referenti)
    WHERE `squadre`.`id` = _id;
    
    -- Cancello tutti i membri che non fanno piu' parte
    DELETE 
    FROM `squadre_iscritti`
    WHERE `squadre_iscritti`.`squadra` = _id;

    -- Creo nuovi membri e reinserisco gli altri
    SET @arr = _membri;
    WHILE NOT @arr = '' DO
        SET element = SUBSTRING_INDEX(@arr, ',', 1);
        
        INSERT INTO `squadre_iscritti` (`squadra`, `iscritto`) VALUES (_id, element);
        
        IF LOCATE(',', @arr) > 0 THEN
            SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
        ELSE
            SET @arr = '';
        END IF;
    END WHILE;

    SELECT _id AS "id";
END; //

DROP PROCEDURE IF EXISTS `SquadreList` //
CREATE PROCEDURE `SquadreList`(IN anno YEAR, IN sport INT)
BEGIN
    DECLARE query_anno YEAR DEFAULT YEAR(CURRENT_DATE);
    IF anno IS NOT NULL AND anno <> 0 THEN
        SET query_anno = anno;
    ELSE
        SET query_anno = YEAR(CURRENT_DATE);
    END IF;

    SELECT 
        s.`id` AS "id_squadra", 
        s.`nome`, 
        s.`referenti`,
        parrocchie.`nome` AS "parrocchia", 
        parrocchie.`id` AS "id_parrocchia",
        sp.`nome` AS "nome_sport",
        sp.`id` AS "id_sport",
        GROUP_CONCAT(CONCAT(a.`nome`, ' ', a.`cognome`) SEPARATOR ', ') AS "lista_membri",
        GROUP_CONCAT(a.`id` SEPARATOR ', ') AS "id_membri",
        COUNT(DISTINCT a.`id`) AS "totale_membri"
    FROM `squadre` AS s
        LEFT OUTER JOIN `squadre_iscritti` si ON si.`squadra` = s.`id`
        LEFT OUTER JOIN `iscritti` i ON si.`iscritto` = i.`id`
        LEFT OUTER JOIN `anagrafiche` a ON i.`dati_anagrafici` = a.`id`
        LEFT OUTER JOIN `parrocchie` ON s.`parrocchia` = parrocchie.`id`
        LEFT OUTER JOIN `sport` sp ON s.`sport` = sp.`id`
        INNER JOIN `edizioni` e ON e.`id` = s.`edizione`
    WHERE e.`anno` = query_anno AND sp.`id` = IFNULL(sport, sp.`id`)
    GROUP BY s.`id`, s.`sport`, parrocchie.`nome`
    ORDER BY sp.`nome`, parrocchie.`nome`, s.`nome`;
END; //

DROP PROCEDURE IF EXISTS `GetSquadra` //
CREATE PROCEDURE `GetSquadra`(IN id INT)
BEGIN
    SELECT 
        s.`id`,
        s.`nome`, 
        s.`referenti`,
        GROUP_CONCAT(CONCAT(a.`nome`, ' ', a.`cognome`) SEPARATOR ',') AS membri,
        GROUP_CONCAT(i.`id` SEPARATOR ',') AS id_iscr_membri,
        GROUP_CONCAT(a.`id` SEPARATOR ',') AS id_anag_membri,
        p.`nome` AS "parrocchia",
        p.`id` AS "id_parrocchia",
        sp.`nome` AS "sport",
        sp.`id` AS "id_sport"
    FROM `squadre` AS s
        INNER JOIN `parrocchie` p ON s.`parrocchia` = p.`id`
        INNER JOIN `sport` sp ON s.`sport` = sp.`id`
        LEFT OUTER JOIN `squadre_iscritti` si ON si.`squadra` = s.`id`
        LEFT OUTER JOIN `iscritti` i ON si.`iscritto` = i.`id`
        LEFT OUTER JOIN `anagrafiche` a ON i.`dati_anagrafici` = a.`id`
    WHERE s.`id` = id
    GROUP BY s.`id`;
END; //

DELIMITER ;