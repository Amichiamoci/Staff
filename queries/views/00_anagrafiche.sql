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

CREATE OR REPLACE VIEW `statistiche_nascita` AS
SELECT 
    UPPER(a.`luogo_nascita`) AS "luogo", 
    COUNT(a.`id`) AS "nati"
FROM `anagrafiche` a
GROUP BY UPPER(a.`luogo_nascita`)
ORDER BY COUNT(a.`id`) DESC; 

CREATE OR REPLACE VIEW `email_duplicate` AS
SELECT 
	LOWER(TRIM(a.`email`)) AS "email", 
	COUNT(a.`id`) AS "totale", 
    GROUP_CONCAT(a.`id` SEPARATOR ', ') AS "id_anagrafiche",
    GROUP_CONCAT(CONCAT(a.`nome`, ' ', a.`cognome`) SEPARATOR ', ') AS "nomi_anagrafiche"
FROM `anagrafiche` a
WHERE a.`email` IS NOT NULL
GROUP BY LOWER(TRIM(a.`email`))
HAVING COUNT(a.`id`) > 1
ORDER BY COUNT(a.`id`) DESC;

CREATE OR REPLACE VIEW `anagrafiche_senza_email` AS
SELECT
    a.`id`,
    CONCAT(a.`nome`, ' ', a.`cognome`) AS "nome_completo",
    `SessoDaCF` (a.`codice_fiscale`) AS "sesso", 
    `Eta` (a.`data_nascita`) AS "eta"
FROM `anagrafiche` a
WHERE a.`email` IS NULL OR TRIM(a.`email`) = ''
ORDER BY a.`cognome`, a.`nome`;

CREATE OR REPLACE VIEW `statistiche_email` AS
WITH email_uniche AS (
    SELECT DISTINCT a.email
    FROM `anagrafiche` a
    WHERE a.email IS NOT NULL
), email_providers AS (
    SELECT 
        `DominioEmail`(e.`email`) AS "provider",
        COUNT(*) AS "email"
    FROM `email_uniche` e
    GROUP BY `DominioEmail`(e.`email`)
    ORDER BY COUNT(*) DESC
), totale_anagrafiche_senza_email AS (
	SELECT 
        'Nessuna email!' AS "provider",
        COUNT(DISTINCT a.id) AS "email"
    FROM `anagrafiche_senza_email` a
)
    SELECT e.*
    FROM `email_providers` e
UNION ALL
    SELECT a.*
    FROM `totale_anagrafiche_senza_email` a;