DELIMITER //

DROP PROCEDURE IF EXISTS CreaSquadra //
CREATE PROCEDURE CreaSquadra(IN nome VARCHAR(128), IN parrocchia INT, IN sport INT, IN membri VARCHAR(64), IN edizione INT)
BEGIN
    DECLARE element VARCHAR(128);
    DECLARE id INT DEFAULT 0;
    
    INSERT INTO squadre (nome, parrocchia, sport, edizione) VALUES (nome, parrocchia, sport, edizione);
    
    SET id = LAST_INSERT_ID();

    IF id <> 0 THEN
        SET @arr = membri;
        WHILE @arr != '' DO
            SET element = SUBSTRING_INDEX(@arr, ',', 1);
            
            INSERT INTO squadre_iscritti (squadra, iscritto) VALUES (id, element);
            
            IF LOCATE(',', @arr) > 0 THEN
                SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
            ELSE
                SET @arr = '';
            END IF;
        END WHILE;
    END IF;

    SELECT id;
END; //

DROP PROCEDURE IF EXISTS ModificaSquadra //
CREATE PROCEDURE ModificaSquadra(
    IN id INT, 
    IN nome VARCHAR(128), 
    IN parrocchia INT, 
    IN sport INT, 
    IN membri VARCHAR(128))
proc_body:BEGIN

    DECLARE element VARCHAR(128) DEFAULT NULL;
    
    IF NOT EXISTS (SELECT * FROM squadre WHERE squadre.id = id) THEN
        SELECT 0 AS "Result";
        LEAVE proc_body;
    END IF;

    -- Aggiorno info sulla squadra
    UPDATE squadre
    SET squadre.nome = nome,
        squadre.parrocchia = parrocchia,
        squadre.sport = sport
    WHERE squadre.id = id;
    
    -- Cancello tutti i membri che non fanno piu' parte
    DELETE 
    FROM squadre_iscritti
    WHERE squadre_iscritti.squadra = id;

    -- Creo nuovi membri e reinserisco gli altri
    SET @arr = membri;
    WHILE NOT @arr = '' DO
        SET element = SUBSTRING_INDEX(@arr, ',', 1);
        
        INSERT INTO squadre_iscritti (squadra, iscritto) VALUES (id, element);
        
        IF LOCATE(',', @arr) > 0 THEN
            SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
        ELSE
            SET @arr = '';
        END IF;
    END WHILE;

    SELECT 1 AS "Result";
END; //

DROP PROCEDURE IF EXISTS SquadreList //
CREATE PROCEDURE SquadreList(IN anno YEAR, IN sport INT)
BEGIN
    DECLARE query_anno YEAR DEFAULT YEAR(CURRENT_DATE);
    IF anno IS NOT NULL AND anno <> 0 THEN
        SET query_anno = anno;
    ELSE
        SET query_anno = YEAR(CURRENT_DATE);
    END IF;

    SELECT s.id AS id_squadra, 
        s.nome AS nome, 
        parrocchie.nome AS parrocchia, 
        sp.nome AS nome_sport,
        GROUP_CONCAT(CONCAT(a.nome, ' ', a.cognome)
            SEPARATOR ', ') AS lista_membri
    FROM squadre AS s
    LEFT JOIN (squadre_iscritti AS si) ON si.squadra = s.id
    LEFT JOIN (iscritti AS i) ON si.iscritto = i.id
    LEFT JOIN (anagrafiche AS a) ON i.dati_anagrafici = a.id
    LEFT JOIN parrocchie ON s.parrocchia = parrocchie.id
    LEFT JOIN (sport AS sp) ON s.sport = sp.id
    INNER JOIN (edizioni AS e) ON e.id = s.edizione
    WHERE e.anno = query_anno AND (sp.id = sport OR sport IS NULL)
    GROUP BY s.id, s.sport, parrocchie.nome
    ORDER BY sp.nome, parrocchie.nome, s.nome;
END; //

DROP PROCEDURE IF EXISTS GetSquadra //
CREATE PROCEDURE GetSquadra(IN id INT)
BEGIN
    SELECT 
        s.id,
        s.nome, 
        GROUP_CONCAT(CONCAT(a.nome, ' ', a.cognome) SEPARATOR ',') AS membri,
        GROUP_CONCAT(i.id SEPARATOR ',') AS id_iscr_membri,
        GROUP_CONCAT(a.id SEPARATOR ',') AS id_anag_membri,
        p.nome AS "parrocchia",
        p.id AS "id_parrocchia",
        sp.nome AS "sport",
        sp.id AS "id_sport"
    FROM squadre AS s
        INNER JOIN parrocchie p ON s.parrocchia = p.id
        INNER JOIN sport sp ON s.sport = sp.id
        LEFT OUTER JOIN squadre_iscritti si ON si.squadra = s.id
        LEFT OUTER JOIN iscritti i ON si.iscritto = i.id
        LEFT OUTER JOIN anagrafiche a ON i.dati_anagrafici = a.id
    WHERE s.id = id
    GROUP BY s.id;
END; //

DROP PROCEDURE IF EXISTS CancellaSquadra //
CREATE PROCEDURE CancellaSquadra(IN id INT)
BEGIN
    DELETE FROM squadre 
    WHERE squadre.id = id;
END; //

DROP PROCEDURE IF EXISTS TorneiList //
CREATE PROCEDURE TorneiList(IN anno YEAR)
BEGIN
    DECLARE query_anno YEAR DEFAULT YEAR(CURRENT_DATE);
    IF anno IS NOT NULL AND anno <> 0 THEN
        SET query_anno = anno;
    ELSE
        SET query_anno = YEAR(CURRENT_DATE);
    END IF;

    SELECT t.nome AS "Torneo", sp.nome AS "Sport",
    IF (s.nome IS NOT NULL, GROUP_CONCAT(DISTINCT s.nome SEPARATOR ", "), "Nessuna iscrizione") AS "Squadre", tipi.nome AS "Tipologia"
	FROM tornei AS t
    LEFT JOIN (partecipaz_squad_torneo AS p) ON p.torneo = t.id
    LEFT JOIN (squadre as s) ON p.squadra = s.id
    LEFT JOIN (edizioni AS e) ON t.edizione = e.id
    LEFT JOIN (sport AS sp) ON t.sport = sp.id
    LEFT JOIN (tipi_torneo AS tipi) ON t.tipo_torneo = tipi.id
    WHERE e.anno = query_anno
    GROUP BY t.id;
END; //

DROP PROCEDURE IF EXISTS TuttiTornei //
CREATE PROCEDURE TuttiTornei(IN anno YEAR)
BEGIN
    SELECT t.id, 
        t.nome, 
        sp.nome AS sport,
        e.anno,
        tp.nome AS tipologia, 
        COUNT(pst.squadra) AS squadre_iscritte,
        GROUP_CONCAT(
            p.id SEPARATOR '|'
        ) AS id_parrocchie,
        GROUP_CONCAT(
            p.nome SEPARATOR '|'
        ) AS nomi_parrocchie, 
        GROUP_CONCAT(
            s.nome SEPARATOR '|'
        ) AS nomi_squadre, 
        GROUP_CONCAT(
            PunteggioInTorneo(t.id, tipo_torneo, s.id) SEPARATOR '|'
        ) AS partite
    FROM tornei t
        INNER JOIN edizioni e ON t.edizione = e.id
        INNER JOIN tipi_torneo tp ON t.tipo_torneo = tp.id
        INNER JOIN sport sp ON t.sport = sp.id
        -- Sezione squadre
        LEFT OUTER JOIN partecipaz_squad_torneo pst ON pst.torneo = t.id
        LEFT OUTER JOIN squadre s ON s.id = pst.squadra
        LEFT OUTER JOIN parrocchie p ON p.id = s.parrocchia
    WHERE e.anno = anno
    GROUP BY t.id;
END; //

DROP PROCEDURE IF EXISTS Elenco //
CREATE PROCEDURE Elenco(IN torneo INT)
elenco_body:BEGIN
    
    IF torneo IS NULL THEN
        CALL TuttiTornei(YEAR(CURRENT_DATE));
        LEAVE elenco_body;
    END IF;
    
    SELECT t.nome, 
        sp.nome AS sport,
        e.anno,
        tp.nome AS tipologia, 
        GROUP_CONCAT(
            p.id SEPARATOR '|'
        ) AS id_parrocchie,
        GROUP_CONCAT(
            p.nome SEPARATOR '|'
        ) AS nomi_parrocchie, 
        COUNT(pst.squadra) AS squadre_iscritte,
        GROUP_CONCAT(
            PunteggioInTorneo(t.id, tipo_torneo, s.id) SEPARATOR '|'
        ) AS partite
    FROM tornei t
        INNER JOIN edizioni e ON t.edizione = e.id
        INNER JOIN tipi_torneo tp ON t.tipo_torneo = tp.id
        INNER JOIN sport sp ON t.sport = sp.id
        -- Sezione squadre
        LEFT OUTER JOIN partecipaz_squad_torneo pst ON pst.torneo = t.id
        LEFT OUTER JOIN squadre s ON s.id = pst.squadra
        LEFT OUTER JOIN parrocchie p ON p.id = s.parrocchia
    WHERE t.id = torneo;
END; //

DROP FUNCTION IF EXISTS Punteggio //
CREATE FUNCTION Punteggio(left_score VARCHAR(8), right_score VARCHAR(8))
RETURNS INT 
DETERMINISTIC
BEGIN
    DECLARE left_int INT DEFAULT 0;
    DECLARE right_int INT DEFAULT 0;

    SET left_int = CAST(TRIM(left_score) AS SIGNED);
    SET right_int = CAST(TRIM(right_score) AS SIGNED);
    RETURN CASE 
        WHEN left_int > right_int THEN 1
        WHEN left_int = right_int THEN 0
        ELSE (-1) END;
END ; //

DROP FUNCTION IF EXISTS EsitoPartita //
CREATE FUNCTION EsitoPartita(id INT, team INT)
RETURNS INT 
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE risultato INT DEFAULT NULL;

    IF id IS NULL OR team IS NULL THEN
        RETURN 0;
    END IF;
    
    SELECT SUM(Punteggio(r.home, r.guest)) INTO risultato
    FROM punteggi AS r
        INNER JOIN partite pa ON pa.id = r.partita
    WHERE pa.id = id AND (pa.squadra_casa = team OR pa.squadra_ospite = team);

    IF risultato IS NULL THEN
        RETURN 0; -- La partita non esiste
    END IF;

    IF EXISTS(
        SELECT * 
        FROM partite 
        WHERE partite.id = id AND partite.squadra_casa = team) THEN
        -- Stiamo controllando la squadra di casa
        RETURN CASE 
            WHEN risultato > 0 THEN 3
            WHEN risultato = 0 THEN 1
            ELSE 0 END;
    END IF;  

    -- Stiamo controllando la squadra ospite
    RETURN CASE 
        WHEN risultato < 0 THEN 3
        WHEN risultato = 0 THEN 1
        ELSE 0 END; 
END ; //

DROP FUNCTION IF EXISTS PunteggioInTorneo //
CREATE FUNCTION PunteggioInTorneo(torneo INT, tipo_torneo INT, team INT)
RETURNS VARCHAR (64)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE _punteggio INT DEFAULT 0;
    DECLARE _previste INT DEFAULT 0;
    DECLARE _da_giocare INT DEFAULT 0;

    IF torneo IS NULL OR tipo_torneo IS NULL THEN
        RETURN NULL;
    END IF;

    IF team IS NULL THEN
        RETURN "0 0 0 0";
    END IF;
    
    IF tipo_torneo = 2 THEN
        RETURN PunteggioInEliminazioneDiretta(torneo, team);
    END IF;

    IF tipo_torneo <> 1 THEN
        -- Tipologia non gestita
        RETURN "0 0 0 0";
    END IF;

    SELECT 
        SUM(EsitoPartita(p.id, team)),
        COUNT(DISTINCT p.id),
        SUM(IF (EXISTS(SELECT * FROM punteggi r WHERE r.partita = p.id), 0, 1))
        INTO _punteggio, _previste, _da_giocare
    FROM partite AS p
    WHERE p.torneo = torneo AND (p.squadra_casa = team OR p.squadra_ospite = team);

    IF _punteggio IS NULL THEN
        SET _punteggio = 0;
    END IF;
    IF _da_giocare IS NULL THEN
        SET _da_giocare = 0;
    END IF;

    RETURN CONCAT(team, ' ', _previste, ' ', _da_giocare, ' ', _punteggio);
END ; //

DROP FUNCTION IF EXISTS PunteggioInEliminazioneDiretta //
CREATE FUNCTION PunteggioInEliminazioneDiretta(torneo INT, team INT)
RETURNS VARCHAR (64)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE _punteggio INT DEFAULT 0;
    DECLARE _previste INT DEFAULT 0;
    DECLARE _da_giocare INT DEFAULT 0;

    IF torneo IS NULL OR team IS NULL THEN
        RETURN NULL;
    END IF;
    
    RETURN NULL;
END ; //

DROP PROCEDURE IF EXISTS CreaCalendario //
CREATE PROCEDURE CreaCalendario(IN torneo INT)
calendario_body:BEGIN

    DECLARE num_iscritti INT DEFAULT 0;
    DECLARE partite_previste INT DEFAULT 0;
    DECLARE partite_create INT DEFAULT 0;
    DECLARE tipo_torneo INT DEFAULT 0;
    

    SELECT t.tipo_torneo INTO tipo_torneo
    FROM tornei t
    WHERE t.id = torneo;

    IF tipo_torneo = 0 OR tipo_torneo IS NULL THEN
        -- Se il torneo non esiste usciamo
        LEAVE calendario_body;
    END IF;
    
    SELECT COUNT(*) INTO num_iscritti
    FROM partecipaz_squad_torneo pst
    WHERE pst.torneo = torneo;

    IF num_iscritti = 0 OR num_iscritti IS NULL THEN
        -- Se il torneo non ha partecipanti usciamo
        LEAVE calendario_body;
    END IF;

    SET partite_previste = num_iscritti * (num_iscritti - 1) / 2;

    CREATE TEMPORARY TABLE IF NOT EXISTS _Coppie_Partite_1
    (
        casa INT NOT NULL REFERENCES squadre(id),
        ospite INT NOT NULL REFERENCES squadre(id),
        decisional INT NOT NULL 
    ) Engine=InnoDB;
    TRUNCATE _Coppie_Partite_1;
    CREATE TEMPORARY TABLE IF NOT EXISTS _Coppie_Partite_2
    (
        casa INT NOT NULL REFERENCES squadre(id),
        ospite INT NOT NULL REFERENCES squadre(id),
        decisional INT NOT NULL 
    ) Engine=InnoDB;
    TRUNCATE _Coppie_Partite_2;

    -- Genero tutte le possibili coppie tra le squadre possibili e associo a ciascuna un numero casuale
    INSERT INTO _Coppie_Partite_1
        SELECT pst1.squadra AS casa, pst2.squadra AS ospite, FLOOR(RAND() * 100) AS decisional
        FROM partecipaz_squad_torneo pst1 
            INNER JOIN partecipaz_squad_torneo pst2 ON pst1.squadra <> pst2.squadra AND pst1.torneo = pst2.torneo
        WHERE pst1.torneo = torneo;
    -- Creo una tabella copia con il seguente schema: (c,o,d) -> (o,c,d)
    -- E' necessario usare una seconda tabella perche' in MySQL una query non puo' accedere piu' volte alla stessa TEMPORARY TABLE
    INSERT INTO _Coppie_Partite_2
        SELECT pst1.squadra AS casa, pst2.squadra AS ospite, t.decisional
        FROM partecipaz_squad_torneo pst1 
            INNER JOIN partecipaz_squad_torneo pst2 ON pst1.squadra <> pst2.squadra AND pst1.torneo = pst2.torneo
            INNER JOIN _Coppie_Partite_1 t ON t.casa = pst2.squadra AND t.ospite = pst1.squadra
        WHERE pst1.torneo = torneo;

    -- Cancello i record che vanno scartati
    DELETE _Coppie_Partite_1
    FROM _Coppie_Partite_1
        INNER JOIN _Coppie_Partite_2 USING(casa, ospite)
    WHERE _Coppie_Partite_1.decisional < _Coppie_Partite_2.decisional;

    -- Non mi serve piu' la seconda tabella
    TRUNCATE _Coppie_Partite_2;

    -- Controllo che il numero torni
    SELECT COUNT(*) INTO partite_create FROM _Coppie_Partite_1;

    IF partite_create <> partite_previste THEN
        LEAVE calendario_body;
    END IF;

    -- Ora che ho le coppie pianifico le partite effettive

    INSERT INTO partite (torneo, data, orario, campo, squadra_casa, squadra_ospite)
        SELECT torneo, NULL, NULL, NULL, c.casa, c.ospite
        FROM _Coppie_Partite_1 c;
    
    -- Elimino anche la principale tabella di appoggio
    TRUNCATE _Coppie_Partite_1;
END; //

DELIMITER ;

CREATE OR REPLACE VIEW tornei_sport AS
SELECT 
    t.id,
	t.nome,
    e.anno,
    t.sport AS "codice_sport",
    sp.nome AS "sport",
    tipi_torneo.nome AS "tipo",
    tipi_torneo.id AS "id_tipo"
FROM tornei t
    INNER JOIN edizioni e ON e.id = t.edizione
    INNER JOIN sport sp ON sp.id = t.sport
    INNER JOIN tipi_torneo ON tipi_torneo.id = t.tipo_torneo;

CREATE OR REPLACE VIEW tornei_attivi AS
SELECT 
    t.*,
    IF (
        COUNT(DISTINCT s.id) = 0, 
        'Nessuna squadra iscritta', 
        GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ')
    ) AS "squadre",
    COUNT(DISTINCT s.id) AS "numero_squadre",
    IF (
        COUNT(DISTINCT partite.id) > 0,
        CONCAT('Già creato, ', COUNT(DISTINCT partite.id), ' partite previste'),
        'Da creare'
    ) AS "calendario",
    COUNT(DISTINCT partite.id) AS "partite"
FROM tornei_sport t
	LEFT OUTER JOIN partecipaz_squad_torneo p ON t.id = p.torneo
    LEFT OUTER JOIN squadre s ON p.squadra = s.id
    LEFT OUTER JOIN partite ON partite.torneo = t.id
WHERE t.anno = YEAR(CURRENT_DATE)
GROUP BY t.id;

CREATE OR REPLACE VIEW partite_tornei_attivi AS
SELECT 
	p.id,

    t.id AS "torneo", 
    t.nome AS "nome_torneo",
    t.sport,
    t.codice_sport,
    t.anno,

    p.data,
    p.orario,
    p.campo,

    s1.nome AS "casa",
    s1.id AS "id_casa",
    s2.nome AS "ospiti",
    s2.id AS "id_ospiti"
FROM partite p
	INNER JOIN tornei_sport t ON t.id = p.torneo
    INNER JOIN squadre s1 ON p.squadra_casa = s1.id
    INNER JOIN squadre s2 ON p.squadra_ospite = s2.id
WHERE t.anno = YEAR(CURRENT_DATE);

CREATE OR REPLACE VIEW partite_da_giocare AS
SELECT p.* 
FROM partite_tornei_attivi p
WHERE NOT EXISTS(SELECT * FROM punteggi r WHERE r.partita = p.id);

CREATE OR REPLACE VIEW chi_gioca_oggi AS
SELECT 
	a.nome,
    a.email,
    
    -- Squadre
    GROUP_CONCAT(s.nome SEPARATOR '|') AS "nomi_squadre",
    GROUP_CONCAT(s2.nome SEPARATOR '|') AS "nomi_avversari",

    -- Orari partite
    GROUP_CONCAT(
        IF (p.orario IS NULL, '?', p.orario) SEPARATOR '|'
    ) AS "orari_partite",

    -- Torneo partite
    GROUP_CONCAT(
        CONCAT(p.nome_torneo, ' - ', p.sport) SEPARATOR '|'
    ) AS "nomi_tornei_sport",

    -- Molte informazioni sul luogo delle partite
    GROUP_CONCAT(
        IF (c.nome IS NULL, '?', c.nome) SEPARATOR '|'
    ) AS "nomi_campi",
    GROUP_CONCAT(
        IF (c.indirizzo IS NULL, '?', c.indirizzo) SEPARATOR '|'
    ) AS "indirizzi_campi",
    GROUP_CONCAT(
        IF (c.posizione IS NULL, '?', ST_Y(c.posizione)) SEPARATOR '|'
    ) AS "lat_campi",
    GROUP_CONCAT(
        IF (c.posizione IS NULL, '?', ST_X(c.posizione)) SEPARATOR '|'
    ) AS "lon_campi",

    -- Se ha il certificato medico
    IF (i.certificato_medico IS NULL, 1, 0) AS "necessita_certificato"
FROM anagrafiche a
	INNER JOIN iscritti i ON i.dati_anagrafici = a.id -- Iscrizione
    INNER JOIN squadre_iscritti si ON si.iscritto = i.id -- Partecipazione in squadra
    INNER JOIN squadre s ON s.id = si.squadra -- Squadra dove si partecipa
    INNER JOIN partite_tornei_attivi p ON p.id_casa = s.id OR p.id_ospiti = s.id -- Partita da fare
    INNER JOIN squadre s2 ON (p.id_casa = s2.id OR p.id_ospiti = s2.id) AND s2.id <> s.id -- Squadra avversaria
    LEFT OUTER JOIN campi c ON p.campo = c.id -- Luogo della partita, se impostato
WHERE p.data = CURRENT_DATE
GROUP BY a.id;

CREATE OR REPLACE VIEW partite_settimana AS 
SELECT 
	p.*, 
    IF (p.data IS NULL, 
        'Data non impostata',
        CONCAT(
            DATE_FORMAT(p.data, "%d/%m/%Y"),
            IF (p.orario IS NULL, '', CONCAT(' alle ', p.orario))
        )
    ) AS "data_ora_italiana",
    GROUP_CONCAT(r.home SEPARATOR '|') AS "punteggi_casa", 
    GROUP_CONCAT(r.guest SEPARATOR '|') AS "punteggi_ospiti", 
    GROUP_CONCAT(r.id SEPARATOR '|') AS "id_punteggi"
FROM partite_tornei_attivi p
	LEFT OUTER JOIN punteggi r ON r.partita = p.id
WHERE p.data IS NULL OR p.data BETWEEN DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) AND CURRENT_DATE
GROUP BY p.id
ORDER BY p.data DESC, p.orario ASC;