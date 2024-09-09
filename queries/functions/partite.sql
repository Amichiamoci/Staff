DELIMITER //

DROP FUNCTION IF EXISTS `Punteggio` //
CREATE FUNCTION `Punteggio`(left_score VARCHAR(8), right_score VARCHAR(8))
RETURNS INT 
DETERMINISTIC
BEGIN
    DECLARE left_int INT DEFAULT 0;
    DECLARE right_int INT DEFAULT 0;

    SET left_int = CAST(TRIM(left_score) AS SIGNED);
    SET right_int = CAST(TRIM(right_score) AS SIGNED);
    RETURN CASE 
        WHEN left_int > right_int THEN 1
        WHEN left_int = right_int THEN 0
        ELSE (-1) END;
END ; //

DROP FUNCTION IF EXISTS `EsitoPartita` //
CREATE FUNCTION `EsitoPartita`(id INT, team INT)
RETURNS INT 
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE risultato INT DEFAULT NULL;
    DECLARE tavolino INT DEFAULT NULL;
    DECLARE area VARCHAR(16);

    IF id IS NULL OR team IS NULL THEN
        RETURN 0;
    END IF;

    SELECT p.`a_tavolino` INTO tavolino
    FROM `partite` p
    WHERE p.`id` = id;

    IF tavolino IS NOT NULL THEN
        RETURN CASE 
            WHEN tavolino = team THEN 3
            ELSE 0 END; 
    END IF;
    
    SELECT SUM(`Punteggio`(r.`home`, r.`guest`)) INTO risultato
    FROM `punteggi` AS r
        INNER JOIN `partite` pa ON pa.`id` = r.`partita`
    WHERE pa.`id` = id AND (pa.`squadra_casa` = team OR pa.`squadra_ospite` = team);

    IF risultato IS NULL THEN
        RETURN 0; -- La partita non esiste
    END IF;

    SELECT TRIM(UPPER(`sport`.`area`)) INTO area
    FROM `sport`
        INNER JOIN `tornei` ON `tornei`.`sport` = `sport`.`id`
        INNER JOIN `partite` ON `partite`.`torneo` = `tornei`.`id`
    WHERE `partite`.`id` = id;

    IF EXISTS(
        SELECT `partite`.* 
        FROM `partite` 
        WHERE `partite`.`id` = id AND `partite`.`squadra_casa` = team) THEN

        -- Stiamo controllando la squadra di casa
        
        IF area = 'PALLAVOLO' THEN
            RETURN CASE 
                WHEN risultato > 1 THEN 3 -- 2 (o più) a 0
                WHEN risultato = 1 THEN 2 -- 2 a 1
                WHEN risultato = -1 THEN 1 -- 1 a 2
                ELSE 0 END;
        END IF;

        RETURN CASE 
            WHEN risultato > 0 THEN 3
            WHEN risultato = 0 THEN 1
            ELSE 0 END;
    END IF;  

    -- Stiamo controllando la squadra ospite

    IF area = 'PALLAVOLO' THEN
        RETURN CASE 
            WHEN risultato < -1 THEN 3 -- 0 a 2 (o più)
            WHEN risultato = -1 THEN 2 -- 1 a 2
            WHEN risultato = 1 THEN 1 -- 2 a 1
            ELSE 0 END;
    END IF;

    RETURN CASE 
        WHEN risultato < 0 THEN 3
        WHEN risultato = 0 THEN 1
        ELSE 0 END; 
END ; //

DELIMITER ;