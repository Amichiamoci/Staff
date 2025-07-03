DELIMITER //

DROP FUNCTION IF EXISTS `CodiceDocumentOk` //
CREATE FUNCTION `CodiceDocumentOk` (codice VARCHAR(128), tipo INT)
RETURNS VARCHAR(128)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE doc_regex VARCHAR(64) DEFAULT NULL;
    DECLARE doc_label VARCHAR(64) DEFAULT NULL;

    IF codice IS NULL OR TRIM(codice) = '' THEN
        -- RETURN 'Codice mancante!';
        RETURN NULL;
    END IF;

    SELECT t.`regex`, t.`label` INTO doc_regex, doc_label
    FROM `tipi_documento` AS t
    WHERE t.`id` = tipo;

    IF doc_regex IS NULL THEN
        RETURN NULL;
    END IF;

    IF TRIM(UPPER(codice)) REGEXP doc_regex THEN
        RETURN NULL;
    END IF;

    RETURN CONCAT(codice, ' non è codice di ', doc_label);
END ; //



DROP FUNCTION IF EXISTS `ProblemaEta` //
CREATE FUNCTION `ProblemaEta` (data_nascita DATE)
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN
    DECLARE eta INT DEFAULT 0;

    SET eta = `Eta`(data_nascita);

    IF data_nascita IS NULL THEN
        RETURN 'Data di nascita mancante';
    END IF;

    IF eta < 0 THEN
        RETURN 'Data di nascita avanti nel tempo!';
    END IF;

    IF eta < 8 THEN
        RETURN CONCAT("Un po' troppo piccolo per partecipare (", eta, ") anni");
    END IF;

    IF eta >= 80 THEN
        RETURN CONCAT("Un po' troppo grande per partecipare (", eta, ") anni");
    END IF;

    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS `ProblemaTaglia` //
CREATE FUNCTION `ProblemaTaglia`(taglia VARCHAR(8))
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN
    IF taglia IS NULL THEN
        RETURN 'Mancante!';
    END IF;

    IF NOT taglia IN ('XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL') THEN
        RETURN CONCAT('Taglia "', taglia, '" non valida!');
    END IF;

    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS `ProblemaEmail` //
CREATE FUNCTION `ProblemaEmail`(email VARCHAR(64))
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN

    IF email IS NULL OR TRIM(email) = '' THEN
        RETURN 'Mancante!';
    END IF;

    SET @email_regex = '[a-z0-9]{1,}[\.\-\_a-z0-9]{0,}[a-z0-9]@[a-z]{1}[\.\-\_a-z0-9]{0,}\.[a-z]{1,10}';

    IF NOT TRIM(LOWER(email)) REGEXP @email_regex THEN
        RETURN CONCAT('Email "', email, '" non valida!');
    END IF;

    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS `ProblemaTelefono` //
CREATE FUNCTION `ProblemaTelefono` (telefono VARCHAR(16))
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN
    IF telefono IS NULL OR TRIM(telefono) = '' THEN
        -- RETURN 'Mancante!';
        RETURN NULL;
    END IF;

    IF NOT TRIM(telefono) REGEXP '[\+]{0,1}[0-9]+' THEN
        RETURN CONCAT('Numero "', telefono, '" non valido!');
    END IF;

    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS `ProblemaTutore` //
CREATE FUNCTION `ProblemaTutore` (data_nascita DATE, tutore INT)
RETURNS VARCHAR(128)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    IF data_nascita IS NULL THEN
        RETURN NULL;
    END IF;

    IF Eta(data_nascita) >= 18 THEN
        RETURN NULL;
    END IF;

    -- Il soggetto e' minorenne

    IF tutore = 0 OR tutore IS NULL OR NOT EXISTS(
        SELECT * 
        FROM `anagrafiche` a
        WHERE a.`id` = tutore) THEN
        -- Non e' memorizzato nessun tutore
        RETURN 'Mancante';
    END IF;
    
    -- Esiste un tutore, pero' puo' essere minorenne

    IF NOT EXISTS(
        SELECT * 
        FROM `anagrafiche` a
        WHERE a.`id` = tutore AND `Eta`(a.`data_nascita`) >= 18) THEN
        
        -- Tutore e' un minore, non va bene!
        RETURN 'Il tutore è minorenne';
    END IF;

    -- Altrimenti ok: tutore maggiorenne
    RETURN NULL;

END ; //

DROP FUNCTION IF EXISTS `EmailVerified` //
CREATE FUNCTION `EmailVerified` (_email VARCHAR(64))
RETURNS VARCHAR(128)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    IF _email IS NULL THEN
        RETURN NULL;
    END IF;

    IF EXISTS(
        SELECT * 
        FROM `email` e
        WHERE e.`dest` = _email AND e.`ricevuta` AND e.`opened` IS NOT NULL) THEN
        -- Esiste una mail almeno aperta dalla persona, esco direttamente
        RETURN NULL;
    END IF;

    IF NOT EXISTS(
        SELECT *
        FROM `email` e
        WHERE e.`dest` = _email) THEN
        -- Non e' stata inviata nessuna email
        RETURN 'Email non ancora verificata';
    END IF;

    IF NOT EXISTS(
        SELECT *
        FROM `email` e
        WHERE e.`dest` = _email AND e.`ricevuta`) THEN
        -- Esiste una mail non ricevuta, ma ne esistono di inviate
        RETURN 'Sembra non sia MAI stata ricevuta nessuna email';
    END IF;
    
    RETURN 'Email di verifica ricevuta, ma non aperta';
END ; //

DELIMITER ;