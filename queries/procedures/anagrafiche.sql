DELIMITER //

DROP PROCEDURE IF EXISTS `NomeDaAnagrafica` //
CREATE PROCEDURE `NomeDaAnagrafica` (IN anagrafica INT)
BEGIN
    SELECT CONCAT(`nome`, ' ', `cognome`) AS "nome_completo" 
    FROM `anagrafiche` 
    WHERE `id` = anagrafica;
END; //

DROP PROCEDURE IF EXISTS `CreaAnagrafica` //
CREATE PROCEDURE `CreaAnagrafica` (
    IN nome VARCHAR(128), 
    IN cognome VARCHAR(256),
    IN compleanno DATE,
    IN provenienza VARCHAR(256),
    IN telefono VARCHAR(16),
    IN email VARCHAR(64),
    IN cf VARCHAR(64),
    IN doc_type INT,
    IN doc_code VARCHAR(128),
    IN doc_expires DATE,
    IN doc_addr VARCHAR(4096),
    IN abort_if_existing BOOLEAN)
crea_anagrafica_body:BEGIN
    DECLARE id INT DEFAULT 0;
    DECLARE is_existing BOOLEAN DEFAULT FALSE;

    SELECT a.`id` INTO id 
    FROM `anagrafiche` AS a
    WHERE LOWER(a.`codice_fiscale`) = TRIM(LOWER(cf));

    IF id <> 0 AND abort_if_existing = 1 THEN
        SELECT 0 AS 'id', is_existing;
        LEAVE crea_anagrafica_body;
    END IF;

    IF id <> 0 THEN
        /* User already exists, we just update info */
        UPDATE `anagrafiche` SET
        `anagrafiche`.`nome` = CASE 
            WHEN nome IS NOT NULL AND nome != '' THEN TRIM(nome)
            ELSE `anagrafiche`.`nome` END,
        `anagrafiche`.`cognome` = CASE 
            WHEN cognome IS NOT NULL AND cognome != '' THEN TRIM(cognome)
            ELSE `anagrafiche`.`cognome` END,
        `anagrafiche`.`data_nascita` = CASE 
            WHEN compleanno IS NOT NULL THEN compleanno
            ELSE `anagrafiche`.`data_nascita` END,
        `anagrafiche`.`luogo_nascita` = CASE 
            WHEN provenienza IS NOT NULL AND provenienza != '' THEN TRIM(provenienza)
            ELSE `anagrafiche`.`luogo_nascita` END,
        `anagrafiche`.`telefono` = CASE 
            WHEN telefono IS NOT NULL AND telefono != '' THEN REPLACE(telefono, ' ', '')
            ELSE `anagrafiche`.`telefono` END,
        `anagrafiche`.`email` = CASE 
            WHEN email IS NOT NULL AND email != '' THEN REPLACE(email, ' ', '')
            ELSE `anagrafiche`.`email` END,
        `anagrafiche`.`tipo_documento` = CASE 
            WHEN doc_type IS NOT NULL THEN doc_type
            ELSE `anagrafiche`.`tipo_documento` END,
        `anagrafiche`.`codice_documento` = CASE 
            WHEN doc_code IS NOT NULL AND doc_code != '' THEN REPLACE(UPPER(doc_code), ' ', '')
            ELSE `anagrafiche`.`codice_documento` END,
        `anagrafiche`.`scadenza` = CASE 
            WHEN doc_expires IS NOT NULL THEN doc_expires
            ELSE `anagrafiche`.`scadenza` END,
        `anagrafiche`.`documento` = CASE 
            WHEN doc_addr IS NOT NULL AND doc_addr != '' THEN doc_addr
            ELSE `anagrafiche`.`documento` END
        WHERE `anagrafiche`.`id` = id;
        SET is_existing = TRUE;
    ELSE
        /* Create the record */
        INSERT INTO `anagrafiche` (
            `nome`, 
            `cognome`, 
            `data_nascita`, 
            `luogo_nascita`, 
            `telefono`, 
            `email`, 
            `codice_fiscale`, 
            `tipo_documento`, 
            `codice_documento`, 
            `scadenza`, 
            `documento`, 
            `self_generated`
        ) VALUES (
            TRIM(nome), 
            TRIM(cognome),
            compleanno, 
            TRIM(provenienza),
            REPLACE(telefono, ' ', ''), 
            REPLACE(email, ' ', ''),
            REPLACE(UPPER(cf), ' ', ''), 
            doc_type,
            REPLACE(UPPER(doc_code), ' ', ''), 
            doc_expires, 
            doc_addr,
            abort_if_existing
        );
        SET id = LAST_INSERT_ID();
    END IF;

    SELECT id, is_existing;

END; //

DELIMITER ;