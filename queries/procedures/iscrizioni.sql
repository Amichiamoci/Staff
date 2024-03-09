DELIMITER //

DROP PROCEDURE IF EXISTS `IscrizioniList` //
CREATE PROCEDURE `IscrizioniList` (IN anno YEAR, IN id_parrocchia INT)
BEGIN
    -- anno IS NULL AND id_parrocchia IS NULL -> tutte le anagrafiche, di tutte le parrocchie 
    -- anno = X AND id_parrocchia IS NULL -> tutti gli iscritti dell'anno X
    -- anno = X AND id_parrocchia = Y -> tutti gli iscritti dell'anno X della parrocchia Y
    
    SELECT 
        a.`id`, 
        a.`cognome`, 
        a.`nome`, 
        a.`data_nascita_italiana` AS "data_nascita", 
        a.`telefono`, 
        a.`email`, 
        a.`cf`, 
        a.`documento`, 
        a.`codice_documento`, t.`label`, 
        p.`nome` AS "parrocchia", 
        p.`id` AS "id_parrocchia",
        e.`anno`,
        e.`id` AS "id_edizione",
        IF (i.`id` IS NOT NULL, 
            LPAD(HEX(i.`id`), 8, '0'), 
            CONCAT('Non iscritto per il ', 
                IF (anno IS NULL, YEAR(CURRENT_DATE), anno))
            ) AS "codice_iscrizione",
        IF (i.id IS NULL, 
            CONCAT('Iscrivi per il ', 
                IF (anno IS NULL, YEAR(CURRENT_DATE), anno)),
            NULL
            ) AS "iscrivi",
        i.`id` AS id_iscrizione,
        a.`creatore_dati`,
        i.`certificato_medico`,
        i.`taglia_maglietta` AS "maglia",
        CONCAT (a2.`cognome`, ' ', a2.`nome`) AS "tutore",
        i.`tutore` AS "id_tutore"
    FROM `anagrafiche_espanse` AS a
        INNER JOIN `tipi_documento` t ON a.`tipo_documento` = t.`id`
        LEFT OUTER JOIN `iscritti` i ON a.`id` = i.`dati_anagrafici`
        LEFT OUTER JOIN `edizioni` e ON i.`edizione` = e.`id`
        LEFT OUTER JOIN `parrocchie` p ON i.`parrocchia` = p.`id`
        LEFT OUTER JOIN `anagrafiche` a2 ON i.`tutore` = a2.`id`
    WHERE (e.`anno` = anno OR anno IS NULL) AND (id_parrocchia = p.`id` OR id_parrocchia IS NULL)
    GROUP BY a.`id`, i.`id`
    HAVING e.`anno` = YEAR(CURRENT_DATE) OR e.`anno` IS NULL
    ORDER BY parrocchia DESC, YEAR(a.`data_nascita`) ASC, a.`cognome` ASC, a.`nome` ASC;
END; //

DROP PROCEDURE IF EXISTS `SingolaIscrizione` //
CREATE PROCEDURE `SingolaIscrizione` (IN id INT)
BEGIN    
    SELECT 
        a.`id`, 
        a.`cognome`, 
        a.`nome`, 
        a.`data_nascita_italiana` AS "data_nascita", 
        a.`eta`,
        a.`telefono`, 
        a.`email`, 
        a.`cf`, 
        a.`sesso`
        a.`documento`, 
        a.`codice_documento`, 
        t.`label`, 
        p.`nome` AS "parrocchia", 
        p.`id` AS "id_parrocchia",
        e.`anno`,
        e.`id` AS "id_edizione",
        LPAD(HEX(i.`id`), 8, '0') AS "codice_iscrizione",
        NULL AS "iscrivi",
        i.`id` AS "id_iscrizione",
        a.`creatore_dati`,
        i.`certificato_medico`,
        i.`taglia_maglietta` AS "maglia",
        CONCAT (a2.`cognome`, ' ', a2.`nome`) AS "tutore",
        i.`tutore` AS "id_tutore"
    FROM `anagrafiche_espanse` AS a
        INNER JOIN `tipi_documento` t ON a.`tipo_documento` = t.`id`
        INNER JOIN `iscritti` i ON a.`id` = i.`dati_anagrafici`
        INNER JOIN `edizioni` e ON i.`edizione` = e.`id`
        INNER JOIN `parrocchie` p ON i.`parrocchia` = p.`id`
        LEFT OUTER JOIN `anagrafiche` a2 ON i.`tutore` = a2.`id`
    WHERE i.`id` = id;
END; //

DROP PROCEDURE IF EXISTS `NonIscrittiNonStaff` //
CREATE PROCEDURE `NonIscrittiNonStaff` (IN anno YEAR)
BEGIN
    -- anno IS NULL -> tutte le anagrafiche di chi non e' mai stato iscritto 
    -- anno = X -> tutti i non iscritti e non staff dell'anno X
    
    SELECT 
        a.`id`, 
        a.`cognome`, 
        a.`nome`, 
        a.`data_nascita_italiana` AS "data_nascita" 
        a.`eta`, 
        a.`telefono`, 
        a.`email`, 
        a.`cf`, 
        a.`documento`, 
        a.`codice_documento`, 
        t.`label`, 
        a.`sesso`
        a.`creatore_dati`
    FROM `anagrafiche_espanse` AS a
        INNER JOIN `tipi_documento` t ON a.`tipo_documento` = t.id
    WHERE 
        NOT EXISTS (
            SELECT * 
            FROM `iscritti` i
                INNER JOIN `edizioni` e ON e.`id` = i.`edizione`
            WHERE i.`dati_anagrafici` = a.`id` AND (
                e.`anno` = anno OR anno IS NULL
            )) 
        AND
        NOT EXISTS (
            SELECT * 
            FROM `staffisti` s
                INNER JOIN `partecipaz_staff_ediz` p ON p.`staff` = s.`id`
                INNER JOIN `edizioni` e ON e.`id` = p.`edizione`
            WHERE s.`dati_anagrafici` = a.`id` AND (
                e.`anno` = anno OR anno IS NULL
            )) 
    ORDER BY YEAR(a.`data_nascita`) ASC, a.`cognome` ASC, a.`nome` ASC;
END; //

DROP PROCEDURE IF EXISTS `ContaMaglie` //
CREATE PROCEDURE `ContaMaglie` (IN anno YEAR)
BEGIN
    SET @query = NULL;
    SELECT
        GROUP_CONCAT(
            DISTINCT
            CONCAT(
                'SUM(IF(p.`taglia_maglietta` = "',
                m.`taglia`,
                '", 1, 0)) AS ',
                CONCAT('`', m.`taglia`, '`')
            )
            SEPARATOR ", "
        ) INTO @query
    FROM (
        SELECT `taglia_maglietta` AS "taglia" 
        FROM `iscritti` 
        UNION ALL 
        SELECT `maglia` AS "taglia" 
        FROM `partecipaz_staff_ediz`) m;

    SET @query = CONCAT('SELECT p.`nome` AS "Parrocchia", ', @query, 
        ' FROM (
            SELECT i.`taglia_maglietta`, i.`edizione`, `parrocchie`.`nome`
                FROM `parrocchie`
                    INNER JOIN `iscritti` i ON `parrocchie`.`id` = i.`parrocchia`
            UNION ALL 
            SELECT pse.`maglia` AS "taglia_maglietta", pse.`edizione`, CONCAT("Staffisti ", YEAR(CURRENT_DATE)) AS nome
                FROM `staffisti` s
                    INNER JOIN `partecipaz_staff_ediz` pse ON pse.`staff` = s.`id`) p
        INNER JOIN `edizioni` e ON p.`edizione` = e.`id`
        WHERE e.`anno` = ', anno, ' 
        GROUP BY Parrocchia');

    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END ; //


DROP PROCEDURE IF EXISTS `ListaMaglie` //
CREATE PROCEDURE `ListaMaglie` (IN anno YEAR, IN group_names BOOLEAN)
lista_body:BEGIN 
    SET @_anno = YEAR(CURRENT_DATE);

    IF anno IS NOT NULL THEN
        SET @_anno = anno;
    END IF;

    IF group_names THEN
        CALL `ContaMaglie`(@_anno);
        LEAVE lista_body;
    END IF;

    SELECT 
        a.`cognome` AS "Cognome", 
        a.`nome` AS "Nome", 
        i.`nome` AS "Parrocchia", 
        i.`taglia_maglietta` AS "Taglia"
        FROM (
            SELECT p.`nome`, i.`taglia_maglietta`, i.`dati_anagrafici`, i.`edizione`
            FROM `iscritti` i
                INNER JOIN `parrocchie` p ON i.`parrocchia` = p.`id`
            UNION ALL
            SELECT CONCAT("Staffisti ", YEAR(CURRENT_DATE)) AS "nome", 
                pse.`maglia` AS "taglia_maglietta", 
                s.`dati_anagrafici`, 
                pse.`edizione`
            FROM `staffisti` s
                INNER JOIN `partecipaz_staff_ediz` pse ON pse.`staff` = s.`id`
            ) i
        INNER JOIN `anagrafiche` a ON i.`dati_anagrafici` = a.`id`
        INNER JOIN `edizioni` e ON i.`edizione` = e.`id`
    WHERE e.`anno` = @_anno
    ORDER BY Parrocchia, Taglia, Cognome, Nome;
END ; //


DELIMITER ;