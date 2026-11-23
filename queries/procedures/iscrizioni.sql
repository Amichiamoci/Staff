DELIMITER //

CREATE OR REPLACE PROCEDURE `IscrizioniList` (IN requested_year YEAR, IN requested_parish INT)
BEGIN
    -- A NULL year means the current year. A NULL parish includes every parish.
    SELECT
        a.`id`, 
        a.`cognome`, 
        a.`nome`, 
        a.`luogo_nascita`,
        a.`data_nascita_italiana` AS "data_nascita", 
        a.`telefono`, 
        a.`email`, 
        a.`cf`, 
        a.`documento`, 
        a.`tipo_documento`,
        a.`tipo_documento_nome`,
        a.`codice_documento`, 
        a.`scadenza`,
        a.`eta`,
        a.`sesso`,
        p.`nome` AS "parrocchia", 
        p.`id` AS "id_parrocchia",
        e.`anno`,
        e.`id` AS "id_edizione",
        LPAD(HEX(i.`id`), 8, '0') AS "codice_iscrizione",
        NULL AS "iscrivi",
        i.`id` AS id_iscrizione,
        a.`creatore_dati`,
        i.`certificato_medico`,
        i.`taglia_maglietta` AS "maglia",
        CONCAT (a2.`cognome`, ' ', a2.`nome`) AS "tutore",
        i.`tutore` AS "id_tutore"
    FROM `iscritti` i
        INNER JOIN `edizioni` e ON e.`id` = i.`edizione`
        INNER JOIN `anagrafiche_espanse` a ON a.`id` = i.`dati_anagrafici`
        INNER JOIN `parrocchie` p ON p.`id` = i.`parrocchia`
        LEFT JOIN `anagrafiche` a2 ON a2.`id` = i.`tutore`
    WHERE e.`anno` = COALESCE(requested_year, YEAR(CURRENT_DATE))
      AND (requested_parish IS NULL OR p.`id` = requested_parish)
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
        a.`sesso`,
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
        a.`data_nascita_italiana` AS "data_nascita",
        a.`eta`, 
        a.`telefono`, 
        a.`email`, 
        a.`cf`, 
        a.`documento`, 
        a.`codice_documento`, 
        t.`label`, 
        a.`sesso`,
        a.`creatore_dati`,
        CONCAT('Iscrivi per il ', IFNULL (anno, YEAR(CURRENT_DATE))) AS "iscrivi"
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

DROP PROCEDURE IF EXISTS `IscriviEdizioneCorrente` //
CREATE PROCEDURE `IscriviEdizioneCorrente` (IN anagrafica INT, IN parrocchia INT, IN taglia VARCHAR(8), IN tutore INT, IN certificato VARCHAR(2048))
lista_body:BEGIN 
    DECLARE _ediz INT DEFAULT 0;
    DECLARE _id INT DEFAULT 0;

    SELECT e.`id` INTO _ediz
    FROM `edizioni` e
    WHERE e.`anno` = YEAR(CURRENT_DATE);

    INSERT INTO `iscritti` (`dati_anagrafici`, `edizione`, `tutore`, `parrocchia`, `taglia_maglietta`, `certificato_medico`) 
    VALUES (anagrafica, _ediz, tutore, parrocchia, taglia, certificato);

    SELECT LAST_INSERT_ID() INTO _id;

    SELECT _id AS "id", p.`nome` AS "nome_parrocchia"
    FROM `parrocchie` p
    WHERE p.`id` = parrocchia;
END ; //

DELIMITER ;