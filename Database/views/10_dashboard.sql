CREATE OR REPLACE VIEW `dashboard_enrollment_counts` AS
SELECT
    e.`anno`,
    p.`id`,
    p.`nome`,
    COUNT(*) AS "enrolled"
FROM `iscritti` i
	INNER JOIN `edizioni` e   ON e.`id` = i.`edizione`
	INNER JOIN `parrocchie` p ON p.`id` = i.`parrocchia`
GROUP BY e.`anno`, p.`id`, p.`nome`;

CREATE OR REPLACE VIEW `dashboard_first_enrollment` AS
SELECT
    i.`dati_anagrafici`,
    MIN(e.`anno`) AS "first_year"
FROM `iscritti` i
	INNER JOIN `edizioni` e ON e.`id` = i.`edizione`
GROUP BY i.`dati_anagrafici`;

CREATE OR REPLACE VIEW `dashboard_participation` AS
SELECT
    e.`anno`,
    COUNT(DISTINCT i.`dati_anagrafici`)                                              AS "total",
    COUNT(DISTINCT CASE WHEN f.`first_year` = e.`anno` THEN i.`dati_anagrafici` END) AS "first_time"
FROM `edizioni` e
	LEFT OUTER JOIN `iscritti` i                   ON i.`edizione` = e.`id`
	LEFT OUTER JOIN `dashboard_first_enrollment` f ON f.`dati_anagrafici` = i.`dati_anagrafici`
GROUP BY e.`anno`;

CREATE OR REPLACE VIEW `dashboard_shirt_counts` AS
SELECT
    i.`edizione`,
    i.`parrocchia`,
    i.`taglia_maglietta` AS "size",
    COUNT(*)             AS "quantity",
    0                    AS "staff"
FROM `iscritti` i
GROUP BY i.`edizione`, i.`parrocchia`, i.`taglia_maglietta`
UNION ALL
SELECT
    p.`edizione`,
    s.`parrocchia`,
    p.`maglia` AS "size",
    COUNT(*)   AS "quantity",
    1          AS "staff"
FROM `partecipaz_staff_ediz` p
	INNER JOIN `staffisti` s ON s.`id` = p.`staff`
GROUP BY p.`edizione`, s.`parrocchia`, p.`maglia`;

CREATE OR REPLACE VIEW `tournament_match_status` AS
SELECT
    t.`id`,
    t.`nome`,
    t.`edizione`,
    UPPER(TRIM(s.`area`)) AS "area",
    CASE
        WHEN p.`id` IS NULL THEN 'no_matches'
        WHEN p.`a_tavolino` IS NOT NULL OR EXISTS (
            SELECT 1
            FROM `punteggi` r
            WHERE r.`partita` = p.`id`
        ) THEN 'result'
        WHEN p.`data` IS NULL OR p.`orario` IS NULL OR p.`campo` IS NULL THEN 'incomplete'
        ELSE 'scheduled'
    END                   AS "status"
FROM `tornei` t
	INNER JOIN `sport` s        ON s.`id` = t.`sport`
	LEFT OUTER JOIN `partite` p ON p.`torneo` = t.`id`;

CREATE OR REPLACE VIEW `dashboard_tournament_readiness` AS
SELECT
    m.`id`,
    m.`nome`,
    m.`edizione`,
    m.`area`,
    SUM(m.`status` = 'incomplete') AS "incomplete",
    SUM(m.`status` = 'scheduled')  AS "scheduled",
    SUM(m.`status` = 'result')     AS "result"
FROM `tournament_match_status` m
GROUP BY m.`id`, m.`nome`, m.`edizione`, m.`area`;
