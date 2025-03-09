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
    p.`casa`,
    p.`id_casa`,
    p.`id_parrocchia_casa`,
    p1.`nome` AS "nome_parrocchia_casa",

    p.`ospiti`,
    p.`id_ospiti`,
    p.`id_parrocchia_ospiti`,
    p2.`nome` AS "nome_parrocchia_ospite",

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
	p.`nome`,
    p.`email`,
    
    -- Squadre
    GROUP_CONCAT(p.`casa` SEPARATOR '|') AS "nomi_squadre",
    GROUP_CONCAT(p.`ospiti` SEPARATOR '|') AS "nomi_avversari",

    -- Orari partite
    GROUP_CONCAT(
        IF (p.`orario` IS NULL, '?', p.`orario`) SEPARATOR '|'
    ) AS "orari_partite",

    -- Torneo partite
    GROUP_CONCAT(
        CONCAT(p.`nome_torneo`, ' - ', p.`nome_sport`) SEPARATOR '|'
    ) AS "nomi_tornei_sport",

    -- Molte informazioni sul luogo delle partite
    GROUP_CONCAT(
        IF (p.`nome_campo` IS NULL, '?', p.`nome_campo`) SEPARATOR '|'
    ) AS "nomi_campi",
    GROUP_CONCAT(
        IF (p.`indirizzo_campo` IS NULL, '?', p.`indirizzo_campo`) SEPARATOR '|'
    ) AS "indirizzi_campi",
    GROUP_CONCAT(
        IF (p.`latitudine_campo` IS NULL, '?', p.`latitudine_campo`) SEPARATOR '|'
    ) AS "lat_campi",
    GROUP_CONCAT(
        IF (p.`longitudine_campo` IS NULL, '?', p.`longitudine_campo`) SEPARATOR '|'
    ) AS "lon_campi",

    MIN(p.`necessita_certificato`) AS "necessita_certificato"
FROM `partite_oggi_persona` p
WHERE p.`email` IS NOT NULL
GROUP BY p.`id`;
