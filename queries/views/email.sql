CREATE OR REPLACE VIEW `email_extended` AS
SELECT 
    `email`.`id`, 
    `email`.`ricevuta`,
    `email`.`dest` AS "destinatario", 
    IFNULL (`email`.`subject`, '') AS "oggetto", 
    DATE_FORMAT(`email`.`sent`, "Inviata il %d/%m/%Y alle %H:%i") AS "inviata", 
    IF (
        `email`.`opened` IS NULL, 
        "Non ancora aperta", 
        DATE_FORMAT(`email`.`opened`, "Aperta il %d/%m/%Y alle %H:%i")) AS "aperta",
    IFNULL (`email`.`body`, "") AS "testo"
FROM `email`
ORDER BY `email`.`sent` DESC;

CREATE OR REPLACE VIEW `email_extended_no_body` AS
SELECT 
    e.`id`, 
    e.`ricevuta`, 
    e.`destinatario`, 
    e.`oggetto`, 
    e.`inviata`,
    e.`aperta`  
FROM `email_extended` AS e;