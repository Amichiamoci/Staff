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

--
-- User based queries
--

CREATE OR REPLACE VIEW `partite_oggi_persona` AS
SELECT 
    a.`id`,
	a.`nome`,
    a.`cognome`,
    a.`email`,

    p.`id` AS "id_partita",
    s.`id` AS "id_squadra",
    
    -- Squadre
    p.`casa`,
    p.`id_casa`,
    p.`id_parrocchia_casa`,
    p1.`nome` AS "nome_parrocchia_casa",

    p.`ospiti`,
    p.`id_ospiti`,
    p.`id_parrocchia_ospiti`,
    p2.`nome` AS "nome_parrocchia_ospiti",

    -- Orari partite
    p.`orario`,
    p.`data`,

    -- Torneo partite
    p.`id_sport`,
    p.`nome_sport`,
    p.`id_torneo`,
    p.`nome_torneo`,

    -- Molte informazioni sul luogo delle partite
    p.`nome_campo`,
    p.`indirizzo_campo`,
    p.`id_campo`,
    p.`latitudine_campo`,
    p.`longitudine_campo`,

    -- Se ha il certificato medico
    IF (i.`certificato_medico` IS NULL, 1, 0) AS "necessita_certificato"
FROM `anagrafiche` a
	INNER JOIN `iscritti` i ON i.`dati_anagrafici` = a.`id` -- Iscrizione
    INNER JOIN `squadre_iscritti` si ON si.`iscritto` = i.`id` -- Partecipazione in squadra
    INNER JOIN `squadre` s ON s.`id` = si.`squadra` -- Squadra dove si partecipa
    INNER JOIN `partite_da_giocare_oggi` p ON p.`id_casa` = s.`id` OR p.`id_ospiti` = s.`id` -- Partita da fare
    INNER JOIN `parrocchie` p1 ON p1.`id` = p.`id_parrocchia_casa`
    INNER JOIN `parrocchie` p2 ON p2.`id` = p.`id_parrocchia_ospiti`
;

CREATE OR REPLACE VIEW `chi_gioca_oggi` AS
SELECT 
    p.`id`,
	p.`nome`,
    p.`email`,
    
    -- Squadre
    GROUP_CONCAT(IF (p.`id_squadra` = p.`id_casa`, p.`casa`, p.`ospiti`) SEPARATOR '|') AS "nomi_squadre",
    GROUP_CONCAT(IF (p.`id_squadra` = p.`id_casa`, p.`ospiti`, p.`casa`) SEPARATOR '|') AS "nomi_avversari",

    -- Orari partite
    GROUP_CONCAT(
        IFNULL(p.`orario`, '?') SEPARATOR '|'
    ) AS "orari_partite",

    -- Torneo partite
    GROUP_CONCAT(
        CONCAT(p.`nome_torneo`, ' - ', p.`nome_sport`) SEPARATOR '|'
    ) AS "nomi_tornei_sport",

    -- Molte informazioni sul luogo delle partite
    GROUP_CONCAT(
        IFNULL(p.`nome_campo`, '?') SEPARATOR '|'
    ) AS "nomi_campi",
    GROUP_CONCAT(
        IFNULL(p.`indirizzo_campo`, '?') SEPARATOR '|'
    ) AS "indirizzi_campi",
    GROUP_CONCAT(
        IFNULL(p.`latitudine_campo`, '?') SEPARATOR '|'
    ) AS "lat_campi",
    GROUP_CONCAT(
        IFNULL(p.`longitudine_campo`, '?') SEPARATOR '|'
    ) AS "lon_campi",

    MIN(p.`necessita_certificato`) AS "necessita_certificato"
FROM `partite_oggi_persona` p
WHERE p.`email` IS NOT NULL
GROUP BY p.`id`;