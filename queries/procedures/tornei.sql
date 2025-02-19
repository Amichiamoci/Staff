DELIMITER //

DROP PROCEDURE IF EXISTS `TorneiList` //
CREATE PROCEDURE `TorneiList`(IN anno YEAR)
BEGIN
    DECLARE query_anno YEAR DEFAULT YEAR(CURRENT_DATE);
    IF anno IS NOT NULL AND anno <> 0 THEN
        SET query_anno = anno;
    ELSE
        SET query_anno = YEAR(CURRENT_DATE);
    END IF;

    SELECT 
        t.`nome` AS "Torneo", 
        sp.`nome` AS "Sport",
        IF (
            s.`nome` IS NOT NULL, 
            GROUP_CONCAT(s.`nome` SEPARATOR ", "), 
            "Nessuna iscrizione") AS "Squadre", 
        tipi.`nome` AS "Tipologia"
	FROM `tornei` AS t
        LEFT OUTER JOIN `partecipaz_squad_torneo` p ON p.`torneo` = t.`id`
        LEFT OUTER JOIN `squadre` s ON p.`squadra` = s.`id`
        LEFT OUTER JOIN `edizioni` e ON t.`edizione` = e.`id`
        LEFT OUTER JOIN `sport` sp ON t.`sport` = sp.`id`
        LEFT OUTER JOIN `tipi_torneo` tipi ON t.`tipo_torneo` = tipi.`id`
    WHERE e.`anno` = query_anno
    GROUP BY t.`id`;
END; //

DROP PROCEDURE IF EXISTS `TuttiTornei` //
CREATE PROCEDURE `TuttiTornei`(IN anno YEAR)
BEGIN
    SELECT 
        t.`id`, 
        t.`nome`, 
        sp.`nome` AS "sport",
        e.`anno`,
        tp.`nome` AS "tipologia", 
        COUNT(pst.`squadra`) AS "squadre_iscritte",
        GROUP_CONCAT(p.`id` SEPARATOR '|') AS "id_parrocchie",
        GROUP_CONCAT(p.`nome` SEPARATOR '|') AS "nomi_parrocchie", 
        GROUP_CONCAT(s.`nome` SEPARATOR '|') AS "nomi_squadre", 
        GROUP_CONCAT(`PunteggioInTorneo`(t.`id`, t.`tipo_torneo`, s.`id`) SEPARATOR '|') AS "partite"
    FROM `tornei` t
        INNER JOIN `edizioni` e ON t.`edizione` = e.`id`
        INNER JOIN `tipi_torneo` tp ON t.`tipo_torneo` = tp.`id`
        INNER JOIN `sport` sp ON t.`sport` = sp.`id`
        -- Sezione squadre
        LEFT OUTER JOIN `partecipaz_squad_torneo` pst ON pst.`torneo` = t.`id`
        LEFT OUTER JOIN `squadre` s ON s.`id` = pst.`squadra`
        LEFT OUTER JOIN `parrocchie` p ON p.`id` = s.`parrocchia`
    WHERE e.`anno` = anno
    GROUP BY t.`id`;
END; //

DROP PROCEDURE IF EXISTS `Elenco` //
CREATE PROCEDURE `Elenco`(IN torneo INT)
elenco_body:BEGIN
    
    IF torneo IS NULL THEN
        CALL `TuttiTornei`(YEAR(CURRENT_DATE));
        LEAVE elenco_body;
    END IF;
    
    SELECT 
        t.`nome`, 
        sp.`nome` AS "sport",
        e.`anno`,
        tp.`nome` AS "tipologia", 
        GROUP_CONCAT(p.`id` SEPARATOR '|') AS "id_parrocchie",
        GROUP_CONCAT(p.`nome` SEPARATOR '|') AS "nomi_parrocchie", 
        COUNT(pst.`squadra`) AS "squadre_iscritte",
        GROUP_CONCAT(`PunteggioInTorneo`(t.`id`, t.`tipo_torneo`, s.`id`) SEPARATOR '|') AS "partite"
    FROM `tornei` t
        INNER JOIN `edizioni` e ON t.`edizione` = e.`id`
        INNER JOIN `tipi_torneo` tp ON t.`tipo_torneo` = tp.`id`
        INNER JOIN `sport` sp ON t.`sport` = sp.`id`
        -- Sezione squadre
        LEFT OUTER JOIN `partecipaz_squad_torneo` pst ON pst.`torneo` = t.`id`
        LEFT OUTER JOIN `squadre` s ON s.`id` = pst.`squadra`
        LEFT OUTER JOIN `parrocchie` p ON p.`id` = s.`parrocchia`
    WHERE t.`id` = torneo;
END; //

DROP PROCEDURE IF EXISTS `CreaPunteggio` //
CREATE PROCEDURE `CreaPunteggio`(IN partita INT)
BEGIN
    
    INSERT INTO `punteggi` (`partita`, `home`, `guest`) VALUES 
        (partita, '', '');
    
    SELECT LAST_INSERT_ID() AS "id";
END; //

DROP PROCEDURE IF EXISTS `CreaPunteggioCompleto` //
CREATE PROCEDURE `CreaPunteggioCompleto`(IN partita INT, IN home VARCHAR(8), IN guest VARCHAR(8))
BEGIN
    
    INSERT INTO `punteggi` (`partita`, `home`, `guest`) VALUES 
        (partita, home, guest);
    
    SELECT LAST_INSERT_ID() AS "id";
END; //


DROP PROCEDURE IF EXISTS `CreaCalendario` //
CREATE PROCEDURE `CreaCalendario` (IN torneo INT, IN ritorno BOOLEAN, IN default_campo INT)
calendario_body:BEGIN

    DECLARE num_iscritti INT DEFAULT 0;
    DECLARE partite_previste INT DEFAULT 0;
    DECLARE partite_create INT DEFAULT 0;
    DECLARE tipo_torneo INT DEFAULT 0;
    
    SELECT t.`tipo_torneo` INTO tipo_torneo
    FROM `tornei` t
    WHERE t.`id` = torneo;

    IF tipo_torneo = 0 OR tipo_torneo IS NULL THEN
        -- Se il torneo non esiste usciamo
        LEAVE calendario_body;
    END IF;
    
    SELECT COUNT(*) INTO num_iscritti
    FROM `partecipaz_squad_torneo` pst
    WHERE pst.`torneo` = torneo;

    IF num_iscritti = 0 OR num_iscritti IS NULL THEN
        -- Se il torneo non ha partecipanti usciamo
        LEAVE calendario_body;
    END IF;

    SET partite_previste = num_iscritti * (num_iscritti - 1) / 2;

    CREATE TEMPORARY TABLE IF NOT EXISTS `_Coppie_Partite_1`
    (
        `casa` INT NOT NULL,
        `ospite` INT NOT NULL,
        `decisional` INT NOT NULL,

        PRIMARY KEY (`casa`, `ospite`)
    ) Engine=InnoDB;
    TRUNCATE `_Coppie_Partite_1`;

    CREATE TEMPORARY TABLE IF NOT EXISTS _Coppie_Partite_2
    (
        `casa` INT NOT NULL,
        `ospite` INT NOT NULL,
        `decisional` INT NOT NULL,

        PRIMARY KEY (`casa`, `ospite`)
    ) Engine=InnoDB;
    TRUNCATE `_Coppie_Partite_2`;

    -- Genero tutte le possibili coppie tra le squadre possibili e associo a ciascuna un numero casuale
    INSERT INTO `_Coppie_Partite_1`
        SELECT 
            pst1.`squadra` AS "casa", 
            pst2.`squadra` AS "ospite", 
            FLOOR(RAND() * 100) AS "decisional"
        FROM `partecipaz_squad_torneo` pst1 
            INNER JOIN `partecipaz_squad_torneo` pst2 ON 
                pst1.`squadra` <> pst2.`squadra` AND pst1.`torneo` = pst2.`torneo`
        WHERE pst1.`torneo` = torneo;
    
    -- Creo una tabella copia con il seguente schema: (c,o,d) -> (o,c,d)
    -- E' necessario usare una seconda tabella perche' in MySQL una query non puo' accedere piu' volte alla stessa TEMPORARY TABLE
    INSERT INTO `_Coppie_Partite_2`
        SELECT 
            pst1.`squadra` AS "casa", 
            pst2.`squadra` AS "ospite", 
            t.`decisional`
        FROM `partecipaz_squad_torneo` pst1 
            INNER JOIN `partecipaz_squad_torneo` pst2 ON 
                pst1.`squadra` <> pst2.`squadra` AND pst1.`torneo` = pst2.`torneo`
            INNER JOIN `_Coppie_Partite_1` t ON 
                t.`casa` = pst2.`squadra` AND t.`ospite` = pst1.`squadra`
        WHERE pst1.`torneo` = torneo;

    -- Cancello i record che vanno scartati
    DELETE `_Coppie_Partite_1`
    FROM `_Coppie_Partite_1`
        INNER JOIN `_Coppie_Partite_2` USING (`casa`, `ospite`)
    WHERE `_Coppie_Partite_1`.`decisional` < `_Coppie_Partite_2`.`decisional`;

    -- Non mi serve piu' la seconda tabella
    TRUNCATE `_Coppie_Partite_2`;

    -- Controllo che il numero torni
    SELECT COUNT(*) INTO partite_create FROM `_Coppie_Partite_1`;

    IF partite_create <> partite_previste THEN
        LEAVE calendario_body;
    END IF;

    -- Ora che ho le coppie pianifico le partite effettive
    INSERT INTO `partite` (`torneo`, `campo`, `squadra_casa`, `squadra_ospite`)
        SELECT torneo, default_campo, c.`casa`, c.`ospite`
        FROM `_Coppie_Partite_1` c;
    
    -- Elimino anche la principale tabella di appoggio
    TRUNCATE `_Coppie_Partite_1`;

    IF ritorno THEN
        INSERT INTO `partite` (`torneo`, `campo`, `squadra_casa`, `squadra_ospite`)
            SELECT p.`torneo`, p.`campo`, p.`squadra_ospite`, p.`squadra_casa`
            FROM `partite` p
            WHERE p.`torneo` = torneo;
    END IF;
END; //

DELIMITER ;