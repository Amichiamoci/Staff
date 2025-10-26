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