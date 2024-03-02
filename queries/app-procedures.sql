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

/*
DROP TRIGGER IF EXISTS log_messaggi //
CREATE TRIGGER log_messaggi AFTER INSERT ON messaggi
FOR EACH ROW
BEGIN
	CALL LastLogEdit('Messaggi');
END ; //
*/

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

CREATE OR REPLACE VIEW messaggi_recenti AS
SELECT m.testo, m.time, IF (a.nome IS NULL, u.user_name, CONCAT(a.nome, ' ', a.cognome)) AS autore
FROM messaggi AS m 
    INNER JOIN utenti u ON m.autore = u.id
    LEFT OUTER JOIN (staffisti AS s) ON s.id_utente = u.id
    LEFT OUTER JOIN (anagrafiche AS a) ON s.dati_anagrafici = a.id
ORDER BY m.time DESC
LIMIT 10;

CREATE OR REPLACE VIEW lista_parrocchie_partecipanti AS
SELECT p.*, 
	COUNT(DISTINCT i.id) AS "iscritti", 
    COUNT(DISTINCT s.id) AS "squadre",
    COUNT(DISTINCT staffisti.id) AS "staffisti",
    GROUP_CONCAT(DISTINCT sp.nome SEPARATOR ', ') AS "sport"
FROM parrocchie p
	INNER JOIN iscritti i ON i.parrocchia = p.id
    INNER JOIN edizioni e ON e.id = i.edizione
    LEFT OUTER JOIN squadre s ON s.parrocchia = p.id AND s.edizione = e.id
    LEFT OUTER JOIN sport sp ON s.sport = sp.id
    LEFT OUTER JOIN staffisti ON staffisti.parrocchia = p.id
WHERE e.anno = YEAR(CURRENT_DATE)
GROUP BY p.id
ORDER BY COUNT(DISTINCT i.id) DESC; -- Non rimpiazzare con "iscritti"

CREATE OR REPLACE VIEW staffisti_attuali AS
SELECT
	CONCAT(a.cognome, ' ', a.nome) AS "chi",
    a.telefono,
    a.email,
	IF (pse.is_referente, 'Referente', 'Staffista') AS "ruolo",
    s.parrocchia AS "id_parrocchia"
FROM staffisti s
	INNER JOIN partecipaz_staff_ediz pse ON s.id = pse.staff
    INNER JOIN edizioni e ON pse.edizione = e.id
    INNER JOIN anagrafiche a ON s.dati_anagrafici = a.id
WHERE e.anno = YEAR(CURRENT_DATE);