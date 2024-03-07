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

DELIMITER ;