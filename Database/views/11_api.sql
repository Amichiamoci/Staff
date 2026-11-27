CREATE OR REPLACE VIEW `api_guardian_access` AS
SELECT
    i.`dati_anagrafici`                    AS "personId",
    CAST(LOWER(TRIM(g.`email`)) AS BINARY) AS "email"
FROM `iscritti` i
	INNER JOIN `edizioni` e    ON e.`id` = i.`edizione`
	INNER JOIN `anagrafiche` g ON g.`id` = i.`tutore`
WHERE e.`anno` = YEAR(CURRENT_DATE);

CREATE OR REPLACE VIEW `api_people` AS
SELECT
    a.`id`,
    a.`nome`                                    AS "firstName",
    a.`cognome`                                 AS "lastName",
    a.`data_nascita`                            AS "birthDate",
    a.`luogo_nascita`                           AS "birthPlace",
    a.`email`,
    a.`telefono`                                AS "phone",
    a.`codice_fiscale`                          AS "taxCode",
    a.`tipo_documento`                          AS "documentTypeId",
    a.`scadenza`                                AS "documentExpiry",
    NULLIF(TRIM(a.`documento`), '') IS NOT NULL AS "hasDocument"
FROM `anagrafiche` a;

CREATE OR REPLACE VIEW `api_subscriptions` AS
SELECT
    i.`id`,
    i.`dati_anagrafici`                                  AS "personId",
    i.`edizione`                                         AS "editionId",
    i.`parrocchia`                                       AS "parishId",
    i.`taglia_maglietta`                                 AS "shirtSize",
    i.`tutore`                                           AS "guardianId",
    NULLIF(TRIM(i.`certificato_medico`), '') IS NOT NULL AS "hasCertificate",
    a.`email`,
    e.`anno`
FROM `iscritti` i
	INNER JOIN `edizioni` e    ON e.`id` = i.`edizione`
	INNER JOIN `anagrafiche` a ON a.`id` = i.`dati_anagrafici`;

CREATE OR REPLACE VIEW `api_tournaments` AS
SELECT
    t.`id`,
    t.`nome`           AS "name",
    t.`id_sport`       AS "sportId",
    t.`nome_sport`     AS "sport",
    t.`nome_tipo`      AS "type",
    t.`numero_squadre` AS "teamCount",
    t.`numero_partite` AS "matchCount",
    (
        SELECT COUNT(*)
        FROM `tournament_match_status` m
        WHERE m.`id` = t.`id` AND m.`status` = 'result'
    )                  AS "matchesWithResults"
FROM `tornei_attivi` t;

CREATE OR REPLACE VIEW `api_tournament_standings` AS
SELECT
    t.`id`                                             AS "tournamentId",
    c.`id_squadra`                                     AS "teamId",
    c.`nome_squadra`                                   AS "teamName",
    c.`id_parrocchia`                                  AS "parishId",
    CAST(NULLIF(c.`punteggio`, '') AS SIGNED)          AS "points",
    CAST(NULLIF(c.`partite_da_giocare`, '') AS SIGNED) AS "matchesRemaining",
    CAST(NULLIF(c.`partite_previste`, '') AS SIGNED)   AS "matchesPlanned"
FROM `classifica_torneo` c
	INNER JOIN `tornei_attivi` t ON t.`id` = c.`id_torneo`;

CREATE OR REPLACE VIEW `api_matches` AS
SELECT
    m.`id`,
    m.`id_torneo`            AS "tournamentId",
    m.`anno`                 AS "editionYear",
    m.`data`                 AS "date",
    m.`orario`               AS "time",
    m.`id_campo`             AS "fieldId",
    m.`id_casa`              AS "homeId",
    m.`casa`                 AS "homeName",
    m.`id_ospiti`            AS "awayId",
    m.`ospiti`               AS "awayName",
    m.`id_sport`             AS "sportId",
    m.`nome_sport`           AS "sportName",
    m.`a_tavolino`           AS "forfeitWinnerTeamId",
    p.`id`                   AS "scoreId",
    p.`home`                 AS "homeScore",
    p.`guest`                AS "awayScore",
    m.`id_parrocchia_casa`   AS "homeParishId",
    m.`id_parrocchia_ospiti` AS "awayParishId"
FROM `partite_espanso` m
	LEFT OUTER JOIN `punteggi` p ON p.`partita` = m.`id` AND m.`a_tavolino` IS NULL;

CREATE OR REPLACE VIEW `api_team_members` AS
SELECT
    s.`id`    AS "teamId",
    s.`nome`  AS "teamName",
    s.`sport` AS "sportId",
    a.`id`    AS "personId",
    a.`email`
FROM `squadre` s
	INNER JOIN `edizioni` e              ON e.`id` = s.`edizione`
	LEFT OUTER JOIN `squadre_iscritti` m ON m.`squadra` = s.`id`
	LEFT OUTER JOIN `iscritti` i         ON i.`id` = m.`iscritto` AND i.`edizione` = e.`id`
	LEFT OUTER JOIN `anagrafiche` a      ON a.`id` = i.`dati_anagrafici`
WHERE e.`anno` = YEAR(CURRENT_DATE);

CREATE OR REPLACE VIEW `api_admin_emails` AS
SELECT CAST(LOWER(TRIM(a.`email`)) AS BINARY) AS "email"
FROM `anagrafiche` a
	INNER JOIN `staffisti` s ON s.`dati_anagrafici` = a.`id`
	INNER JOIN `utenti` u    ON u.`id` = s.`id_utente`
WHERE u.`is_admin` = 1 AND u.`is_blocked` = 0;

CREATE OR REPLACE VIEW `api_login_people` AS
SELECT
    CAST(LOWER(TRIM(a.`email`)) AS BINARY) AS "email",
    MIN(a.`id`)                            AS "person_id"
FROM `anagrafiche` a
GROUP BY CAST(LOWER(TRIM(a.`email`)) AS BINARY);

CREATE OR REPLACE VIEW `api_logins` AS
SELECT
    l.`logged_at`,
    l.`email`,
    l.`token_id`,
    l.`ip_address`,
    t.`name` AS "token_name",
    a.`person_id`
FROM `web_app_logins` l
	LEFT OUTER JOIN `api_token` t        ON t.`id` = l.`token_id`
	LEFT OUTER JOIN `api_login_people` a ON a.`email` = CAST(l.`email` AS BINARY);
