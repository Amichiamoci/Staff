CREATE OR REPLACE VIEW `tornei_sport` AS
SELECT 
    t.`id`,
	t.`nome`,

    e.`id` AS "id_edizione",
    e.`anno`,

    t.`sport` AS "id_sport",
    s.`nome` AS "nome_sport",
    s.`area` AS "area_sport",

    `tipi_torneo`.`nome` AS "nome_tipo",
    `tipi_torneo`.`id` AS "id_tipo"
FROM `tornei` t
    INNER JOIN `edizioni` e ON e.`id` = t.`edizione`
    INNER JOIN `sport` s ON s.`id` = t.`sport`
    INNER JOIN `tipi_torneo` ON `tipi_torneo`.`id` = t.`tipo_torneo`;

CREATE OR REPLACE VIEW `tornei_espanso` AS
SELECT 
    t.*,
    GROUP_CONCAT(DISTINCT s.`nome` SEPARATOR ', ') AS "nomi_squadre",
    COUNT(DISTINCT s.`id`) AS "numero_squadre",
    GROUP_CONCAT(DISTINCT s.`id` SEPARATOR ', ') AS "id_squadre",
    
    GROUP_CONCAT(DISTINCT partite.id SEPARATOR ', ') AS "id_partite",
    COUNT(DISTINCT partite.id) AS "numero_partite"
FROM `tornei_sport` t
	LEFT OUTER JOIN `partecipaz_squad_torneo` p ON t.`id` = p.`torneo`
    LEFT OUTER JOIN `squadre` s ON p.`squadra` = s.`id`
    LEFT OUTER JOIN `partite` ON `partite`.`torneo` = t.`id`
GROUP BY t.`id`;

CREATE OR REPLACE VIEW `tornei_attivi` AS
SELECT t.*
FROM `tornei_espanso` t
WHERE t.anno = YEAR(CURRENT_DATE);


CREATE OR REPLACE VIEW partite_tornei_attivi AS
SELECT 
	p.id,

    t.id AS "torneo", 
    t.nome AS "nome_torneo",
    t.sport,
    t.codice_sport,
    t.area,
    t.anno,

    p.data,
    p.orario,
    p.campo,

    s1.nome AS "casa",
    s1.id AS "id_casa",
    s1.parrocchia AS "id_parrocchia_casa",

    s2.nome AS "ospiti",
    s2.id AS "id_ospiti",
    s2.parrocchia AS "id_parrocchia_ospiti"
FROM partite p
	INNER JOIN tornei_sport t ON t.id = p.torneo
    INNER JOIN squadre s1 ON p.squadra_casa = s1.id
    INNER JOIN squadre s2 ON p.squadra_ospite = s2.id
WHERE t.anno = YEAR(CURRENT_DATE);

CREATE OR REPLACE VIEW partite_settimana AS 
SELECT 
	p.*, 
    IF (p.data IS NULL, 
        'Data non impostata',
        CONCAT(
            DATE_FORMAT(p.data, "%d/%m/%Y"),
            IF (p.orario IS NULL, '', CONCAT(' alle ', p.orario))
        )
    ) AS "data_ora_italiana",
    GROUP_CONCAT(r.home SEPARATOR '|') AS "punteggi_casa", 
    GROUP_CONCAT(r.guest SEPARATOR '|') AS "punteggi_ospiti", 
    GROUP_CONCAT(r.id SEPARATOR '|') AS "id_punteggi",
    GROUP_CONCAT(CONCAT(r.home, ' - ', r.guest) SEPARATOR ', ') AS "punteggio",
    COUNT(r.id) AS "punteggi"
FROM partite_tornei_attivi p
	LEFT OUTER JOIN punteggi r ON r.partita = p.id
WHERE p.data IS NULL OR p.data BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY) AND CURRENT_DATE
-- WHERE p.data IS NULL OR p.data BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) AND CURRENT_DATE
GROUP BY p.id
ORDER BY p.data DESC, p.orario ASC;

--
-- Matches complete
--

CREATE OR REPLACE VIEW `partite_con_campi` AS 
SELECT p.*, 
    c.`nome` AS "nome_campo",
    c.`indirizzo` AS "indirizzo_campo",
    c.`id` AS "id_campo",
    ST_Y(c.`posizione`) AS "latitudine_campo",
    ST_X(c.`posizione`) AS "longitudine_campo",
    UPPER(s.`area`) AS "area_sport"
FROM `partite_tornei_attivi` p
    LEFT OUTER JOIN `campi` c ON p.`campo` = c.`id`
    INNER JOIN `sport` s ON s.`id` = p.`codice_sport`
;

CREATE OR REPLACE VIEW `partite_completo` AS 
SELECT p.*, 
    
    s1.`nome` AS "squadra_casa",
    s1.`id` AS "squadra_casa_id",
    s2.`nome` AS "squadra_ospite",
    s2.`id` AS "squadra_ospite_id",

    p1.`nome` AS "nome_parrocchia_casa",
    p2.`nome` AS "nome_parrocchia_ospite",
    
    GROUP_CONCAT(r.home SEPARATOR '|') AS "punteggi_casa", 
    GROUP_CONCAT(r.guest SEPARATOR '|') AS "punteggi_ospiti", 
    GROUP_CONCAT(r.id SEPARATOR '|') AS "id_punteggi",
    GROUP_CONCAT(CONCAT(r.home, ' - ', r.guest) SEPARATOR ', ') AS "punteggio",
    COUNT(r.id) AS "punteggi"

FROM `partite_con_campi` p

    INNER JOIN `squadre` s1 ON s1.`id` = p.`id_casa`
    INNER JOIN `squadre` s2 ON s2.`id` = p.`id_ospiti`

    INNER JOIN `parrocchie` p1 ON p1.`id` = s1.`parrocchia`
    INNER JOIN `parrocchie` p2 ON p2.`id` = s2.`parrocchia`

    
	LEFT OUTER JOIN `punteggi` r ON r.`partita` = p.`id`
GROUP BY p.`id`
ORDER BY p.`data` DESC, p.`orario` ASC
;


--
-- Matches to play
-- 

CREATE OR REPLACE VIEW `partite_da_giocare` AS
SELECT p.* 
FROM `partite_tornei_attivi` p
WHERE NOT EXISTS(SELECT * FROM `punteggi` r WHERE r.`partita` = p.`id`);

CREATE OR REPLACE VIEW `partite_da_giocare_oggi` AS 
SELECT p.*
FROM `partite_da_giocare` p
WHERE p.`data` = CURRENT_DATE;

CREATE OR REPLACE VIEW `partite_da_giocare_oggi_con_campi` AS 
SELECT p.*, 
    c.`nome` AS "nome_campo",
    c.`indirizzo` AS "indirizzo_campo",
    c.`id` AS "id_campo",
    ST_Y(c.`posizione`) AS "latitudine_campo",
    ST_X(c.`posizione`) AS "longitudine_campo",
    UPPER(s.`area`) AS "area_sport"
FROM `partite_da_giocare_oggi` p
    LEFT OUTER JOIN `campi` c ON p.`campo` = c.`id`
    INNER JOIN `sport` s ON s.`id` = p.`codice_sport`
;

CREATE OR REPLACE VIEW `partite_da_giocare_oggi_completo` AS 
SELECT p.*, 
    
    s1.`nome` AS "squadra_casa",
    s1.`id` AS "squadra_casa_id",
    s2.`nome` AS "squadra_ospite",
    s2.`id` AS "squadra_ospite_id",

    p1.`nome` AS "nome_parrocchia_casa",
    p2.`nome` AS "nome_parrocchia_ospite"

FROM `partite_da_giocare_oggi_con_campi` p

    INNER JOIN `squadre` s1 ON s1.`id` = p.`id_casa`
    INNER JOIN `squadre` s2 ON s2.`id` = p.`id_ospiti`

    INNER JOIN `parrocchie` p1 ON p1.`id` = s1.`parrocchia`
    INNER JOIN `parrocchie` p2 ON p2.`id` = s2.`parrocchia`
;

--
-- Matches of today and yeasterday
--

CREATE OR REPLACE VIEW `partite_oggi_ieri` AS 
SELECT p.*
FROM `partite_settimana` p
WHERE p.`data` = CURRENT_DATE OR p.`data` = subdate(CURRENT_DATE, 1)
ORDER BY p.`data` DESC;

CREATE OR REPLACE VIEW `partite_oggi_ieri_con_campi` AS 
SELECT p.*, 
    c.`nome` AS "nome_campo",
    c.`indirizzo` AS "indirizzo_campo",
    c.`id` AS "id_campo",
    ST_Y(c.`posizione`) AS "latitudine_campo",
    ST_X(c.`posizione`) AS "longitudine_campo",
    UPPER(s.`area`) AS "area_sport"
FROM `partite_oggi_ieri` p
    LEFT OUTER JOIN `campi` c ON p.`campo` = c.`id`
    INNER JOIN `sport` s ON s.`id` = p.`codice_sport`
;

CREATE OR REPLACE VIEW `partite_oggi_ieri_completo` AS 
SELECT p.*, 
    
    s1.`nome` AS "squadra_casa",
    s1.`id` AS "squadra_casa_id",
    s2.`nome` AS "squadra_ospite",
    s2.`id` AS "squadra_ospite_id",

    p1.`nome` AS "nome_parrocchia_casa",
    p2.`nome` AS "nome_parrocchia_ospite"

FROM `partite_oggi_ieri_con_campi` p

    INNER JOIN `squadre` s1 ON s1.`id` = p.`id_casa`
    INNER JOIN `squadre` s2 ON s2.`id` = p.`id_ospiti`

    INNER JOIN `parrocchie` p1 ON p1.`id` = s1.`parrocchia`
    INNER JOIN `parrocchie` p2 ON p2.`id` = s2.`parrocchia`
;

--
-- Matches of today
--

CREATE OR REPLACE VIEW `partite_oggi` AS 
SELECT p.*
FROM `partite_settimana` p
WHERE p.`data` = CURRENT_DATE;

CREATE OR REPLACE VIEW `partite_oggi_con_campi` AS 
SELECT p.*, 
    c.`nome` AS "nome_campo",
    c.`indirizzo` AS "indirizzo_campo",
    c.`id` AS "id_campo",
    ST_Y(c.`posizione`) AS "latitudine_campo",
    ST_X(c.`posizione`) AS "longitudine_campo",
    UPPER(s.`area`) AS "area_sport"
FROM `partite_oggi` p
    LEFT OUTER JOIN `campi` c ON p.`campo` = c.`id`
    INNER JOIN `sport` s ON s.`id` = p.`codice_sport`
;

CREATE OR REPLACE VIEW `partite_oggi_completo` AS 
SELECT p.*, 
    
    s1.`nome` AS "squadra_casa",
    s1.`id` AS "squadra_casa_id",
    s2.`nome` AS "squadra_ospite",
    s2.`id` AS "squadra_ospite_id",

    p1.`nome` AS "nome_parrocchia_casa",
    p2.`nome` AS "nome_parrocchia_ospite"

FROM `partite_oggi_con_campi` p

    INNER JOIN `squadre` s1 ON s1.`id` = p.`id_casa`
    INNER JOIN `squadre` s2 ON s2.`id` = p.`id_ospiti`

    INNER JOIN `parrocchie` p1 ON p1.`id` = s1.`parrocchia`
    INNER JOIN `parrocchie` p2 ON p2.`id` = s2.`parrocchia`
;

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
    
    -- Squadre
    s.`nome` AS "squadra_casa",
    s.`id` AS "squadra_casa_id",
    s2.`nome` AS "squadra_ospite",
    s2.`id` AS "squadra_ospite_id",

    p1.`nome` AS "nome_parrocchia_casa",
    p1.`id` AS "id_parrocchia_casa",
    p2.`nome` AS "nome_parrocchia_ospite",
    p2.`id` AS "id_parrocchia_ospite",

    -- Orari partite
    p.`orario`,
    p.`data`,

    -- Torneo partite
    p.`sport`,
    p.`codice_sport`,
    p.`nome_torneo` AS "torneo",
    p.`torneo` AS "codice_torneo",

    -- Molte informazioni sul luogo delle partite
    p.`nome_campo`,
    p.`indirizzo_campo`,
    p.`id_campo`,
    p.`latitudine_campo`,
    p.`longitudine_campo`,

    -- Se ha il certificato medico
    IF (i.certificato_medico IS NULL, 1, 0) AS "necessita_certificato"
FROM `anagrafiche` a
	INNER JOIN `iscritti` i ON i.`dati_anagrafici` = a.`id` -- Iscrizione
    INNER JOIN `squadre_iscritti` si ON si.`iscritto` = i.`id` -- Partecipazione in squadra
    INNER JOIN `squadre` s ON s.`id` = si.`squadra` -- Squadra dove si partecipa
    INNER JOIN `partite_da_giocare_oggi_con_campi` p ON p.`id_casa` = s.`id` OR p.`id_ospiti` = s.`id` -- Partita da fare
    INNER JOIN `squadre` s2 ON (p.`id_casa` = s2.`id` OR p.`id_ospiti` = s2.`id`) AND s2.`id` <> s.`id` -- Squadra avversaria
    INNER JOIN `parrocchie` p1 ON p1.`id` = s.`parrocchia`
    INNER JOIN `parrocchie` p2 ON p2.`id` = s2.`parrocchia`
;

CREATE OR REPLACE VIEW chi_gioca_oggi AS
SELECT 
	a.nome,
    a.email,
    
    -- Squadre
    GROUP_CONCAT(s.nome SEPARATOR '|') AS "nomi_squadre",
    GROUP_CONCAT(s2.nome SEPARATOR '|') AS "nomi_avversari",

    -- Orari partite
    GROUP_CONCAT(
        IF (p.orario IS NULL, '?', p.orario) SEPARATOR '|'
    ) AS "orari_partite",

    -- Torneo partite
    GROUP_CONCAT(
        CONCAT(p.nome_torneo, ' - ', p.sport) SEPARATOR '|'
    ) AS "nomi_tornei_sport",

    -- Molte informazioni sul luogo delle partite
    GROUP_CONCAT(
        IF (c.nome IS NULL, '?', c.nome) SEPARATOR '|'
    ) AS "nomi_campi",
    GROUP_CONCAT(
        IF (c.indirizzo IS NULL, '?', c.indirizzo) SEPARATOR '|'
    ) AS "indirizzi_campi",
    GROUP_CONCAT(
        IF (c.posizione IS NULL, '?', ST_Y(c.posizione)) SEPARATOR '|'
    ) AS "lat_campi",
    GROUP_CONCAT(
        IF (c.posizione IS NULL, '?', ST_X(c.posizione)) SEPARATOR '|'
    ) AS "lon_campi",

    -- Se ha il certificato medico
    IF (i.certificato_medico IS NULL, 1, 0) AS "necessita_certificato"
FROM anagrafiche a
	INNER JOIN iscritti i ON i.dati_anagrafici = a.id -- Iscrizione
    INNER JOIN squadre_iscritti si ON si.iscritto = i.id -- Partecipazione in squadra
    INNER JOIN squadre s ON s.id = si.squadra -- Squadra dove si partecipa
    INNER JOIN partite_da_giocare_oggi p ON p.id_casa = s.id OR p.id_ospiti = s.id -- Partita da fare
    INNER JOIN squadre s2 ON (p.id_casa = s2.id OR p.id_ospiti = s2.id) AND s2.id <> s.id -- Squadra avversaria
    LEFT OUTER JOIN campi c ON p.campo = c.id -- Luogo della partita, se impostato
GROUP BY a.id;

--
-- Classifica torneo
--

CREATE OR REPLACE VIEW `classifica_torneo` AS
SELECT 
    s.`nome` AS "nome_squadra",
    s.`id` AS "id_squadra",

    s.`sport` AS "id_sport",
    sp.`nome` AS "nome_sport",

    s.`parrocchia` AS "id_parrocchia",
    p.`nome` AS "nome_parrocchia",

    t.`nome` AS "nome_torneo",
    t.`id` AS "id_torneo",

    SPLIT_STR(
        `PunteggioInTorneo`(t.`id`, t.`tipo_torneo`, s.`id`), 
        ' ', 1) AS "partite_previste",
    SPLIT_STR(
        `PunteggioInTorneo`(t.`id`, t.`tipo_torneo`, s.`id`), 
        ' ', 2) AS "partite_da_giocare",
    SPLIT_STR(
        `PunteggioInTorneo`(t.`id`, t.`tipo_torneo`, s.`id`), 
        ' ', 3) AS "punteggio"
FROM `squadre` s
    
    INNER JOIN `sport` sp ON sp.`id` = s.`sport`
    INNER JOIN `parrocchie` p ON p.`id` = s.`parrocchia`

    INNER JOIN `partecipaz_squad_torneo` pt ON pt.`squadra` = s.`id`
    INNER JOIN `tornei` t ON t.`id` = pt.`torneo`
ORDER BY CAST("punteggio" AS UNSIGNED) DESC;