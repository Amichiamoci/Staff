CREATE OR REPLACE VIEW `iscrizioni_per_csi` AS
SELECT 
	a.`cognome`, 
    a.`nome`, 
    a.`sesso`, 
    a.`luogo_nascita`, 
    a.`data_nascita_italiana` AS "data_nascita",
    IFNULL (a.`telefono`, '') AS "telefono",
    IFNULL (a.`email`, '') AS "email"
FROM `iscritti` i
	INNER JOIN `anagrafiche_espanse` a ON a.`id` = i.`dati_anagrafici`
    INNER JOIN `edizioni` e ON e.`id` = i.`edizione`
WHERE e.`anno` = YEAR(CURRENT_DATE)
ORDER BY i.`parrocchia`;

CREATE OR REPLACE VIEW `non_iscritti` AS
SELECT a.*, e.anno
FROM `anagrafiche_espanse` a
    CROSS JOIN `edizioni` e
WHERE NOT EXISTS (
    SELECT * 
    FROM `iscritti` i 
    WHERE i.`dati_anagrafici` = a.`id` AND i.`edizione` = e.`id`)
ORDER BY e.`id` ASC, a.`eta` DESC, a.`cognome` ASC;

CREATE OR REPLACE VIEW `distinte` AS 
SELECT 
    -- Filtering fields
    s.`squadra` AS "squadra_id",
    -- Fields
    a.`id`,
    i.`id` AS "iscrizione",
    UPPER(a.`codice_fiscale`) AS "cf",
    CONCAT(a.`nome`, ' ', a.`cognome`) AS "chi",
    `SessoDaCF`(a.`codice_fiscale`) AS "sesso",
    -- Problems
    `CodiceDocumentOk`(a.`codice_documento`, a.`tipo_documento`) AS "doc_code_problem",
    IF (a.`documento` IS NOT NULL, NULL, 'Documento mancante') AS "doc_problem",
    `ScadeInGiorni`(a.scadenza, 1) AS "scadenza_problem",
    IF (i.`certificato_medico` IS NOT NULL, NULL, 'Certificato medico mancante') AS "certificato_problem",
    `ProblemaTutore` (a.`data_nascita`, i.`tutore`) AS "tutore_problem"
FROM `iscritti` i
    INNER JOIN `anagrafiche` a ON i.`dati_anagrafici` = a.`id`
    INNER JOIN `edizioni` e ON i.`edizione` = e.`id`
    INNER JOIN `squadre_iscritti` s ON s.`iscritto` = i.`id`
WHERE e.`anno` = YEAR(CURRENT_DATE);


CREATE OR REPLACE VIEW `anagrafiche_con_iscrizioni_correnti` AS 
SELECT 
    a.*,
    t.`label` AS "nome_tipo_documento",
    `ScadeInGiorni`(a.`scadenza`, 62) AS "scadenza_problem",

    -- Parrocchia
    p.`nome` AS "parrocchia", 
    p.`id` AS "id_parrocchia",
    
    -- Iscrizione
    IF (i.`id` IS NOT NULL, 
        CONCAT('Codice iscrizione ', e.`anno`, ': ', LPAD(HEX(i.`id`), 8, '0')), 
        CONCAT('Non iscritto per il ', e.`anno`) ) AS "codice_iscrizione",
    i.`id` AS "id_iscrizione",
    IF (i.`certificato_medico` IS NULL OR TRIM(i.`certificato_medico`) = '',
        'Mancante', 'Presente') AS "stato_certificato",
    i.`taglia_maglietta` AS "maglia"

FROM `anagrafiche_espanse` AS a
    INNER JOIN `tipi_documento` t ON a.`tipo_documento` = t.`id`
    CROSS JOIN `edizioni` e ON e.`anno` = YEAR(CURRENT_DATE)
    LEFT OUTER JOIN `iscritti` i ON i.`edizione` = e.`id` AND i.`dati_anagrafici` = a.`id`
    LEFT OUTER JOIN `parrocchie` p ON i.`parrocchia` = p.`id`
    LEFT OUTER JOIN `anagrafiche` a2 ON i.`tutore` = a2.`id`
GROUP BY a.`id`, i.`id`
ORDER BY YEAR(a.`data_nascita`) ASC, a.`cognome` ASC, a.`nome` ASC;