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