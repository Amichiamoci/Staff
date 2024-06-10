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