DELIMITER //

DROP FUNCTION IF EXISTS `ScadeInGiorni` //
CREATE FUNCTION `ScadeInGiorni` (scadenza DATE, giorni INT)
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN
    DECLARE diff INT DEFAULT 0;

    IF scadenza IS NULL OR giorni < 0 THEN
        RETURN NULL;
    END IF;

    SET diff = DATEDIFF(scadenza, CURRENT_DATE);
    IF diff >= giorni THEN
        RETURN NULL;
    END IF;

    IF diff < 0 THEN
        RETURN 'Documento scaduto!';
    END IF;

    RETURN DATE_FORMAT(scadenza, 'Documento scade il %d/%m/%Y');
END ; //

DROP FUNCTION IF EXISTS `SessoDaCF` //
CREATE FUNCTION `SessoDaCF` (cf VARCHAR(16))
RETURNS CHAR(1) 
DETERMINISTIC
BEGIN
    DECLARE giorno INT DEFAULT NULL;

    IF cf IS NULL OR LENGTH(TRIM(cf)) <> 16 THEN
        RETURN NULL;
    END IF;

    SET giorno = CAST(SUBSTRING(TRIM(cf), 10, 2) AS SIGNED);
    
    IF giorno > 40 THEN
        RETURN 'F';
    ELSE
        RETURN 'M';
    END IF;

END ; //

DROP FUNCTION IF EXISTS `Eta` //
CREATE FUNCTION `Eta` (data_nascita DATE)
RETURNS INT 
DETERMINISTIC
BEGIN
    IF data_nascita IS NULL THEN
        RETURN 0;
    END IF;

    RETURN TIMESTAMPDIFF(YEAR, data_nascita, CURRENT_DATE);
END ; //

DROP FUNCTION IF EXISTS `DominioEmail` //
CREATE FUNCTION `DominioEmail`(`email` VARCHAR(320))
RETURNS VARCHAR(255)
DETERMINISTIC
BEGIN
    DECLARE pos INT;
    DECLARE domain_part VARCHAR(255);

    IF `email` IS NULL THEN
        RETURN NULL;
    END IF;

    SET pos = LOCATE('@', `email`);

    IF pos = 0 OR pos = LENGTH(`email`) THEN
        RETURN NULL;
    END IF;

    SET domain_part = SUBSTRING(`email`, pos + 1);

    IF domain_part = '' THEN
        RETURN NULL;
    END IF;

    RETURN LOWER(domain_part);
END ; //

--
-- Italian tax code (Codice Fiscale) checking functions
--

DROP FUNCTION IF EXISTS `RimuoviAccenti` //
CREATE FUNCTION `RimuoviAccenti`(s VARCHAR(255))
RETURNS VARCHAR(255)
DETERMINISTIC
BEGIN
    SET s = UPPER(s);

    SET s = REPLACE(s, 'À', 'A');
    SET s = REPLACE(s, 'Á', 'A');
    SET s = REPLACE(s, 'Â', 'A');
    SET s = REPLACE(s, 'Ã', 'A');
    SET s = REPLACE(s, 'Ä', 'A');
    SET s = REPLACE(s, 'Å', 'A');

    SET s = REPLACE(s, 'È', 'E');
    SET s = REPLACE(s, 'É', 'E');
    SET s = REPLACE(s, 'Ê', 'E');
    SET s = REPLACE(s, 'Ë', 'E');

    SET s = REPLACE(s, 'Ì', 'I');
    SET s = REPLACE(s, 'Í', 'I');
    SET s = REPLACE(s, 'Î', 'I');
    SET s = REPLACE(s, 'Ï', 'I');

    SET s = REPLACE(s, 'Ò', 'O');
    SET s = REPLACE(s, 'Ó', 'O');
    SET s = REPLACE(s, 'Ô', 'O');
    SET s = REPLACE(s, 'Õ', 'O');
    SET s = REPLACE(s, 'Ö', 'O');

    SET s = REPLACE(s, 'Ù', 'U');
    SET s = REPLACE(s, 'Ú', 'U');
    SET s = REPLACE(s, 'Û', 'U');
    SET s = REPLACE(s, 'Ü', 'U');

    SET s = REPLACE(s, 'Ç', 'C');
    SET s = REPLACE(s, 'Ñ', 'N');

    RETURN s;
END ; //

DROP FUNCTION IF EXISTS `CodiceFiscaleCognome` //
CREATE FUNCTION `CodiceFiscaleCognome`(`surname` VARCHAR(255))
RETURNS CHAR(3)
DETERMINISTIC
BEGIN
    DECLARE s VARCHAR(255);
    DECLARE cons VARCHAR(255);
    DECLARE vows VARCHAR(255);
    DECLARE res VARCHAR(3);

    SET s = UPPER(`RimuoviAccenti`(`surname`));
    SET s = REGEXP_REPLACE(s, '[^A-Z]', '');

    SET cons = REGEXP_REPLACE(s, '[AEIOU]', '');
    SET vows = REGEXP_REPLACE(s, '[^AEIOU]', '');

    SET res = LEFT(CONCAT(cons, vows, 'XXX'), 3);
    RETURN res;
END ; //

DROP FUNCTION IF EXISTS `CodiceFiscaleNome` //
CREATE FUNCTION `CodiceFiscaleNome`(`name` VARCHAR(255))
RETURNS CHAR(3)
DETERMINISTIC
BEGIN
    DECLARE n VARCHAR(255);
    DECLARE cons VARCHAR(255);
    DECLARE vows VARCHAR(255);
    DECLARE res VARCHAR(3);

    SET n = UPPER(`RimuoviAccenti`(`name`));
    SET n = REGEXP_REPLACE(n, '[^A-Z]', '');

    SET cons = REGEXP_REPLACE(n, '[AEIOU]', '');
    SET vows = REGEXP_REPLACE(n, '[^AEIOU]', '');

    IF LENGTH(cons) >= 4 THEN
        SET res = CONCAT(
            SUBSTRING(cons, 1, 1),
            SUBSTRING(cons, 3, 1),
            SUBSTRING(cons, 4, 1)
        );
    ELSE
        SET res = LEFT(CONCAT(cons, vows, 'XXX'), 3);
    END IF;

    RETURN res;
END ; //

DROP FUNCTION IF EXISTS `CodiceFiscaleCorretto` //
CREATE FUNCTION `CodiceFiscaleCorretto`(
    `codice_fiscale` CHAR(16),
    `name` VARCHAR(255),
    `surname` VARCHAR(255)
)
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE expected CHAR(6);

    SET expected = CONCAT(
        `CodiceFiscaleCognome`(`surname`),
        `CodiceFiscaleNome`(`name`)
    );

    RETURN expected = LEFT(TRIM(UPPER(`codice_fiscale`)), 6);
END ; //

DELIMITER ;