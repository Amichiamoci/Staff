CREATE OR REPLACE VIEW `classifica_parrocchie` AS
SELECT p.*, r.`punteggio`, (COUNT(DISTINCT r2.`parrocchia`) + 1) AS "posizione"
FROM `punteggio_parrocchia` r
    INNER JOIN `edizioni` e ON e.`id` = r.`edizione`
    LEFT OUTER JOIN `punteggio_parrocchia` r2 ON 
        r2.`edizione` = r.`edizione` AND 
        r2.`parrocchia` <> r.`parrocchia` AND 
        r2.`punteggio` > r.`punteggio`
    INNER JOIN `parrocchie` p ON p.`id` = r.`parrocchia`
WHERE e.`anno` = YEAR(CURRENT_DATE)
GROUP BY r.`parrocchia`
ORDER BY r.`punteggio` DESC;


CREATE OR REPLACE VIEW `lista_parrocchie_partecipanti` AS
SELECT p.*, 
	COUNT(DISTINCT i.`id`) AS "iscritti", 
    COUNT(DISTINCT s.`id`) AS "squadre",
    COUNT(DISTINCT `staffisti`.id) AS "staffisti",
    GROUP_CONCAT(DISTINCT sp.`nome` SEPARATOR ', ') AS "sport"
FROM `parrocchie` p
	INNER JOIN `iscritti` i ON i.`parrocchia` = p.`id`
    INNER JOIN `edizioni` e ON e.`id` = i.`edizione`
    LEFT OUTER JOIN `squadre` s ON s.`parrocchia` = p.`id` AND s.`edizione` = e.`id`
    LEFT OUTER JOIN `sport` sp ON s.`sport` = sp.`id`
    LEFT OUTER JOIN `staffisti` ON `staffisti`.`parrocchia` = p.`id`
WHERE e.`anno` = YEAR(CURRENT_DATE)
GROUP BY p.`id`
ORDER BY COUNT(DISTINCT i.`id`) DESC; -- Non rimpiazzare con "iscritti"