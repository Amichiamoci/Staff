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
