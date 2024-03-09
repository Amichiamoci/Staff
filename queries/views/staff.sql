CREATE OR REPLACE VIEW `partecipazioni_staff` AS
SELECT 
    a.*, 
    `parrocchie`.`nome` AS "parrocchia", 
    `parrocchie`.`id` AS "id_parrocchia",
    s.`id` AS "id_staffista", 
    GROUP_CONCAT(DISTINCT e.`anno` SEPARATOR ', ') AS "partecipazioni",
    COUNT(DISTINCT e.`id`) AS "numero_partecipazioni"
FROM `staffisti` AS s
    INNER JOIN `anagrafiche` a ON s.`dati_anagrafici` = a.`id`
    LEFT OUTER JOIN `parrocchie` ON s.`parrocchia` = `parrocchie`.`id`
    LEFT OUTER JOIN `partecipaz_staff_ediz` p ON s.`id` = p.`staff`
    LEFT OUTER JOIN `edizioni` e ON p.`edizione` = e.`id`
GROUP BY s.`id`
ORDER BY `parrocchie`.`nome`, a.`cognome`, a.`nome`;

CREATE OR REPLACE VIEW `staff_list_raw` AS
SELECT 
    CONCAT(a.`nome`, ' ', a.`cognome`) AS "nome_completo", 
    s.`id` AS "staff" 
FROM `staffisti` AS s 
    LEFT OUTER JOIN `anagrafiche` a ON s.`dati_anagrafici` = a.`id`;

CREATE OR REPLACE VIEW `staff_data` AS
SELECT 
    IFNULL (parr.`nome`, 'Non specificata') AS "parrocchia",
    IFNULL (parr.`id`, 0) AS "id_parrocchia",
    IFNULL (r.`comm`, 'Nessuna commissione') AS "commissioni",
    IFNULL (r.`tot_comm`, 0) AS "totale_commissioni",
    IFNULL (r.`maglia`, 'Non scelta') AS "maglia",
    IFNULL (r.`is_referente`, 0) AS "referente",
    IFNULL (a.`codice_fiscale`, '') AS "cf",
    CONCAT(a.`nome`, ' ', a.`cognome`) AS "nome"
FROM `staffisti` AS s
    INNER JOIN `anagrafiche` a ON a.`id` = s.`dati_anagrafici`
    LEFT OUTER JOIN `parrocchie` parr ON parr.`id` = s.`parrocchia`
    LEFT OUTER JOIN (
        SELECT 
            p.`maglia`, 
            p.`staff`, 
            p.`is_referente`,
            GROUP_CONCAT(DISTINCT c.`nome` SEPARATOR ', ') AS "comm",
            COUNT(DISTINCT c.`nome`) AS "tot_comm"
        FROM `edizioni` AS e
            LEFT OUTER JOIN `partecipaz_staff_ediz` p ON e.`id` = p.`edizione`
            LEFT OUTER JOIN `ruoli_staff` r ON r.`staffista` = p.`staff` AND r.`edizione` = e.`id`
            LEFT OUTER JOIN `commissioni` c ON r.`commissione` = c.`id`
        WHERE e.`anno` = anno
        GROUP BY p.`staff`
    ) r ON r.`staff` = s.`id`
WHERE s.`id` = staff_id;

CREATE OR REPLACE VIEW `staff_per_edizione` AS
SELECT 
    a.*, 
    `parrocchie`.`nome` AS "parrocchia", 
    `parrocchie`.`id` AS "id_parrocchia",
    s.`id` AS "id_staffista", 
    p.`is_referente` AS "referente", 
    GROUP_CONCAT(DISTINCT c.`nome` SEPARATOR ', ') AS "lista_commissioni",
    e.`anno`,
    e.`id` AS "id_edizione"
FROM `staffisti` AS s
    INNER JOIN `partecipaz_staff_ediz` p ON p.`staff` = s.`id`
    INNER JOIN `edizioni` e ON p.`edizione` = e.`id`
    LEFT OUTER JOIN `anagrafiche_espanse` a ON s.`dati_anagrafici` = a.`id`
    LEFT OUTER JOIN `parrocchie` ON s.`parrocchia` = `parrocchie`.`id`
    LEFT OUTER JOIN `ruoli_staff` r ON s.`id` = r.`staffista` AND r.`edizione` = e.`id`
    LEFT OUTER JOIN `commissioni` c ON r.`commissione` = c.`id`
GROUP BY e.`anno`, a.`id`, `parrocchie`.`nome`
ORDER BY `parrocchie`.`nome`, a.`cognome`, a.`nome`;

CREATE OR REPLACE VIEW `staff_attuali` AS
SELECT s.*
FROM `staff_per_edizione` s
WHERE s.`anno` = YEAR(CURRENT_DATE);