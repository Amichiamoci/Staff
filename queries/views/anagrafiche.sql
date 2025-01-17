CREATE OR REPLACE VIEW `anagrafiche_espanse` AS
SELECT 
    a.*, 
    `SessoDaCF` (a.`codice_fiscale`) AS "sesso", 
    `Eta` (a.`data_nascita`) AS "eta",
    DATE_FORMAT(a.`data_nascita`, '%d/%m/%Y') AS "data_nascita_italiana",
    a.`codice_fiscale` AS "cf", -- Alias di codice_fiscale
    IF (a.`self_generated`, 'Persona stessa', 'Staffista') AS "creatore_dati",
    IF (DATE_FORMAT(a.data_nascita, "%d/%m") = DATE_FORMAT(CURRENT_DATE, "%d/%m"), 1, 0) AS "is_compleanno",
    t.`label` AS "tipo_documento_nome"
FROM `anagrafiche` AS a
    INNER JOIN `tipi_documento` t ON a.`tipo_documento` = t.`id`
GROUP BY a.`id`;

CREATE OR REPLACE VIEW `compleanni_oggi` AS
SELECT DISTINCT 
    DATE_FORMAT(a.`data_nascita`, "%d/%m") AS "compleanno",
    a.`nome`,
    a.`cognome`,
    a.`eta`,
    a.`email`
FROM `anagrafiche_espanse` AS a
WHERE a.`is_compleanno` = 1;