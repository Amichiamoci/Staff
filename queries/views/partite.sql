--
-- Matches complete
--

CREATE OR REPLACE VIEW `partite_espanso` AS
SELECT 
	p.`id`,

    t.`id` AS "id_torneo", 
    t.`nome` AS "nome_torneo",
    t.`nome_sport`,
    t.`area_sport`,
    t.`id_sport`,
    t.`anno`,
    t.`id_edizione`,

    p.`data`,
    p.`orario`,

    s1.`nome` AS "casa",
    s1.`id` AS "id_casa",
    s1.`parrocchia` AS "id_parrocchia_casa",

    s2.`nome` AS "ospiti",
    s2.`id` AS "id_ospiti",
    s2.`parrocchia` AS "id_parrocchia_ospiti",

    p.`campo` AS "id_campo",
    c.`nome` AS "nome_campo",
    c.`indirizzo` AS "indirizzo_campo",
    ST_Y(c.`posizione`) AS "latitudine_campo",
    ST_X(c.`posizione`) AS "longitudine_campo"
FROM `partite` p
	INNER JOIN `tornei_sport` t ON t.`id` = p.`torneo`
    INNER JOIN `squadre` s1 ON p.`squadra_casa` = s1.`id`
    INNER JOIN `squadre` s2 ON p.`squadra_ospite` = s2.`id`
    LEFT OUTER JOIN `campi` c ON p.`campo` = c.`id`
;

CREATE OR REPLACE VIEW `partite_completo` AS 
SELECT 
    p.*, 

    p1.`nome` AS "nome_parrocchia_casa",
    p2.`nome` AS "nome_parrocchia_ospiti",
    
    GROUP_CONCAT(r.`home` SEPARATOR '|') AS "punteggi_casa", 
    GROUP_CONCAT(r.`guest` SEPARATOR '|') AS "punteggi_ospiti", 
    GROUP_CONCAT(r.`id` SEPARATOR '|') AS "id_punteggi",
    GROUP_CONCAT(CONCAT(r.`home`, ' - ', r.`guest`) SEPARATOR ', ') AS "punteggio",
    COUNT(r.`id`) AS "punteggi"

FROM `partite_espanso` p
    INNER JOIN `parrocchie` p1 ON p1.`id` = p.`id_parrocchia_casa`
    INNER JOIN `parrocchie` p2 ON p2.`id` = p.`id_parrocchia_ospiti`
	LEFT OUTER JOIN `punteggi` r ON r.`partita` = p.`id`
GROUP BY p.`id`
ORDER BY p.`data` DESC, p.`orario` ASC
;

--
-- Matches of this year, this week, yesterday and today
--

CREATE OR REPLACE VIEW `partite_tornei_attivi` AS
SELECT p.*
FROM `partite_completo` p
WHERE p.`anno` = YEAR(CURRENT_DATE);

CREATE OR REPLACE VIEW `partite_settimana` AS
SELECT p.*
FROM `partite_tornei_attivi` p
WHERE p.`data` IS NULL OR p.`data` BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY) AND CURRENT_DATE;

CREATE OR REPLACE VIEW `partite_oggi_ieri` AS 
SELECT p.*
FROM `partite_settimana` p
WHERE p.`data` = CURRENT_DATE OR p.`data` = SUBDATE(CURRENT_DATE, 1)
ORDER BY p.`data` DESC;

CREATE OR REPLACE VIEW `partite_oggi` AS 
SELECT p.*
FROM `partite_settimana` p
WHERE p.`data` = CURRENT_DATE;

--
-- Matches not yet played
-- 

CREATE OR REPLACE VIEW `partite_da_giocare` AS
SELECT p.* 
FROM `partite_tornei_attivi` p
WHERE p.`punteggi` = 0;

CREATE OR REPLACE VIEW `partite_da_giocare_oggi` AS 
SELECT p.*
FROM `partite_da_giocare` p
WHERE p.`data` = CURRENT_DATE;

-- 
-- Fields of active matches
--

CREATE OR REPLACE VIEW `campi_partite_attive` AS
SELECT 
    c.*,
    ST_Y(c.`posizione`) AS "latitudine",
    ST_X(c.`posizione`) AS "longitudine"
FROM `campi` c
WHERE EXISTS (
    SELECT * 
    FROM `partite_tornei_attivi` p
    WHERE p.`id_campo` = c.`id`
);