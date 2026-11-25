CREATE OR REPLACE VIEW `email_history` AS
SELECT
    e.`id`,
    e.`dest`                                  AS "destinatario",
    COALESCE(e.`subject`, '')                 AS "oggetto",
    e.`ricevuta`,
    DATE_FORMAT(e.`sent`, '%d/%m/%Y %H:%i')   AS "inviata",
    DATE_FORMAT(e.`opened`, '%d/%m/%Y %H:%i') AS "aperta",
    e.`sent`
FROM `email` e;
