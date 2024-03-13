DELIMITER //

DROP PROCEDURE IF EXISTS LastLogEdit //
CREATE PROCEDURE LastLogEdit(IN area VARCHAR(64))
BEGIN
    UPDATE last_log 
    SET time_stamp = CURRENT_TIMESTAMP
    WHERE UPPER(last_log.area) = UPPER(area) OR area IS NULL;
END; //

DROP PROCEDURE IF EXISTS CreaMessaggio //
CREATE PROCEDURE CreaMessaggio(IN utente INT, IN body TEXT)
BEGIN
    INSERT INTO messaggi (testo, autore) VALUES (body, utente);
	CALL LastLogEdit('Messaggi');
END; //

DELIMITER ;

CREATE OR REPLACE VIEW LastLogGet AS
SELECT DISTINCT area, time_stamp, url 
FROM last_log 
ORDER BY time_stamp, area DESC;

CREATE OR REPLACE VIEW eventi_correnti AS 
SELECT 
	e.titolo, e.descrizione, 
    e.locandina, 
    DATE_FORMAT(e.inizio, '%d/%m/%Y alle %H:%i') AS "inizio",
    e.inizio AS "ts",
    DATE_FORMAT(e.fine, '%d/%m/%Y alle %H:%i') AS "fine"
FROM eventi e
    INNER JOIN edizioni ed ON ed.id = e.edizione
WHERE e.inizio >= CURRENT_TIMESTAMP AND ed.anno = YEAR(CURRENT_DATE)
ORDER BY e.inizio ASC;
