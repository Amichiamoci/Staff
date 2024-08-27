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

DELIMITER ;