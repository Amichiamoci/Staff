/*
CREATE OR REPLACE VIEW LastLogGet AS
SELECT DISTINCT area, time_stamp, url 
FROM last_log 
ORDER BY time_stamp, area DESC;
*/

CREATE OR REPLACE VIEW eventi_a_breve AS 
SELECT e.*
FROM eventi_correnti e
WHERE TIMESTAMPDIFF(MINUTE, CURRENT_TIMESTAMP, e.ts) <= 31;

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

CREATE OR REPLACE VIEW squadre_attuali AS
SELECT 
	s.nome, 
	p.nome AS "parrocchia", s.parrocchia AS "id_parrocchia",  
    e.anno AS "edizione", e.id AS "id_edizione",
    sp.nome AS "sport", s.sport AS "id_sport",
    COUNT(DISTINCT si.iscritto) AS "membri"
FROM squadre s
	INNER JOIN edizioni e ON s.edizione = e.id
    INNER JOIN parrocchie p ON p.id = s.parrocchia
    INNER JOIN sport sp ON s.sport = sp.id
    LEFT OUTER JOIN squadre_iscritti si ON si.squadra = s.id
WHERE e.anno = YEAR(CURRENT_DATE)
GROUP BY s.id
ORDER BY sp.id, membri DESC;

/*
CREATE OR REPLACE VIEW partite_recenti AS
SELECT 
    p.*,
    EsitoPartita(p.id, p.id_casa) AS "esito_casa",
    EsitoPartita(p.id, p.id_ospiti) AS "esito_ospiti"
FROM partite_settimana p
WHERE p.punteggi > 0
ORDER BY p.sport ASC, p.torneo DESC, p.data DESC, p.orario DESC;
*/

CREATE OR REPLACE VIEW campi_attuali AS
SELECT c.*, ST_X(c.posizione) AS "lon", ST_Y(c.posizione) AS "lat"
FROM campi c
WHERE EXISTS (
    SELECT * 
    FROM partite_tornei_attivi p 
    WHERE p.campo = c.id);

CREATE OR REPLACE VIEW classifica_parrocchie AS
SELECT p.id, p.nome, r.punteggio, (COUNT(DISTINCT r2.parrocchia) + 1) AS "posizione"
FROM punteggio_parrocchia r
    INNER JOIN edizioni e ON e.id = r.edizione
    LEFT OUTER JOIN punteggio_parrocchia r2 ON r2.edizione = r.edizione AND r2.parrocchia <> r.parrocchia AND r2.punteggio > r.punteggio
    INNER JOIN parrocchie p ON p.id = r.parrocchia
WHERE e.anno = YEAR(CURRENT_DATE)
GROUP BY r.parrocchia
ORDER BY r.punteggio DESC;