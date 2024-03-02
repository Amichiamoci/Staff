DELIMITER //

DROP PROCEDURE IF EXISTS StaffList //
CREATE PROCEDURE StaffList(IN anno YEAR)
BEGIN
    DECLARE query_anno YEAR DEFAULT YEAR(CURRENT_DATE);
    IF anno IS NOT NULL THEN
        SET query_anno = anno;
    ELSE
        SET query_anno = YEAR(CURRENT_DATE);
    END IF;

    SELECT a.*, parrocchie.nome AS parrocchia, 
        s.id AS id_staffista, 
        s.is_referente AS referente, 
        GROUP_CONCAT(c.nome
            SEPARATOR ', ') AS lista_commissioni
    FROM staffisti AS s
    INNER JOIN (partecipaz_staff_ediz AS p) ON p.staff = s.id
    INNER JOIN (edizioni AS e) ON p.edizione = e.id
    LEFT JOIN (anagrafiche AS a) ON s.dati_anagrafici=a.id
    LEFT JOIN parrocchie ON s.parrocchia=parrocchie.id
    LEFT JOIN (ruoli_staff AS r) ON s.id = r.staffista
    LEFT JOIN (commissioni AS c) ON r.commissione = c.id
    WHERE e.anno = query_anno AND r.edizione = e.id
    GROUP BY a.id, parrocchie.nome
    ORDER BY parrocchie.nome, a.cognome, a.nome;
END; //

DROP PROCEDURE IF EXISTS RawStaffList //
CREATE PROCEDURE RawStaffList()
BEGIN
    SELECT CONCAT(a.nome, ' ', a.cognome) AS nome_completo, s.id AS staff 
    FROM staffisti AS s 
    LEFT JOIN (anagrafiche AS a) ON s.dati_anagrafici = a.id;
END; //

DROP PROCEDURE IF EXISTS IscrizioniList //
CREATE PROCEDURE IscrizioniList(IN anno YEAR, IN anche_non_iscritti BIT)
BEGIN
    SELECT a.id, a.cognome, a.nome, a.data_nascita, a.telefono, a.email, a.codice_fiscale AS cf, a.documento, t.label, p.nome as parrocchia, e.anno, i.id AS codice_iscrizione
    FROM edizioni AS e, iscritti AS i 
    RIGHT JOIN (anagrafiche AS a) ON a.id = i.dati_anagrafici
    LEFT JOIN (parrocchie AS p) ON i.parrocchia = p.id
    LEFT JOIN (tipi_documento AS t) ON a.tipo_documento = t.id
    WHERE e.anno = anno AND (i.edizione = e.id OR anche_non_iscritti)
    ORDER BY e.anno, a.cognome, a.nome, a.data_nascita DESC;
END; //

DROP PROCEDURE IF EXISTS NomeDaAnagrafica //
CREATE PROCEDURE NomeDaAnagrafica(IN anagrafica INT)
BEGIN
    SELECT CONCAT(nome, ' ', cognome) AS nome_completo 
    FROM anagrafiche 
    WHERE id = anagrafica;
END; //

DROP PROCEDURE IF EXISTS GetStaffFromUserId //
CREATE PROCEDURE GetStaffFromUserId(IN id INT)
BEGIN
    SELECT CONCAT(a.nome, ' ', a.cognome) AS nome, a.id AS id_anagrafica, s.id AS staffista
    FROM anagrafiche AS a 
    INNER JOIN (staffisti AS s) ON a.id = s.dati_anagrafici
    WHERE s.id_utente = id;
END; //

DROP PROCEDURE IF EXISTS CreaAnagrafica //
CREATE PROCEDURE CreaAnagrafica(
    IN nome VARCHAR(128), 
    IN cognome VARCHAR(256),
    IN compleanno DATE,
    IN provenienza VARCHAR(256),
    IN telefono VARCHAR(16),
    IN email VARCHAR(64),
    IN cf VARCHAR(64),
    IN doc_type INT,
    IN doc_code VARCHAR(128),
    IN doc_addr VARCHAR(4096))
BEGIN
    DECLARE id INT DEFAULT 0;

    SELECT a.id INTO id FROM anagrafiche AS a
    WHERE LOWER(a.codice_fiscale) = LOWER(cf);

    IF id > 0 THEN
        /*User already exists, we just update info*/
        UPDATE anagrafiche SET
        anagrafiche.nome = CASE 
            WHEN nome IS NOT NULL AND nome != '' THEN nome
            ELSE anagrafiche.nome END,
        anagrafiche.cognome = CASE 
            WHEN cognome IS NOT NULL AND cognome != '' THEN cognome
            ELSE anagrafiche.cognome END,
        anagrafiche.data_nascita = CASE 
            WHEN compleanno IS NOT NULL THEN compleanno
            ELSE anagrafiche.data_nascita END,
        anagrafiche.luogo_nascita = CASE 
            WHEN provenienza IS NOT NULL AND provenienza != '' THEN provenienza
            ELSE anagrafiche.luogo_nascita END,
        anagrafiche.telefono = CASE 
            WHEN telefono IS NOT NULL AND telefono != '' THEN telefono
            ELSE anagrafiche.telefono END,
        anagrafiche.email = CASE 
            WHEN email IS NOT NULL AND email != '' THEN email
            ELSE anagrafiche.email END,
        anagrafiche.tipo_documento = CASE 
            WHEN doc_type IS NOT NULL THEN doc_type
            ELSE anagrafiche.tipo_documento END,
        anagrafiche.codice_documento = CASE 
            WHEN doc_code IS NOT NULL AND doc_code != '' THEN doc_code
            ELSE anagrafiche.codice_documento END,
        anagrafiche.documento = CASE 
            WHEN doc_addr IS NOT NULL AND doc_addr != '' THEN doc_addr
            ELSE anagrafiche.documento END
        WHERE anagrafiche.id = id;
    ELSE
        /*Create the record*/
        INSERT INTO anagrafiche (
            nome, cognome, 
            data_nascita, luogo_nascita, 
            telefono, email, 
            codice_fiscale, tipo_documento, 
            codice_documento, documento)
        VALUES (
            nome, cognome,
            compleanno, provenienza,
            telefono, email,
            cf, doc_type,
            doc_code, doc_addr);
        SET id = LAST_INSERT_ID();
    END IF;

    SELECT id;

END; //

DROP PROCEDURE IF EXISTS PartecipaStaff //
CREATE PROCEDURE PartecipaStaff(IN staff_id INT, IN edizione_id INT, IN taglia VARCHAR(2), IN commissioni VARCHAR(64))
proc_body:BEGIN
    DECLARE gia_partecipa INT DEFAULT 0;
    DECLARE element VARCHAR(128);

    SELECT id INTO gia_partecipa 
    FROM partecipaz_staff_ediz
    WHERE staff = staff_id AND edizione = edizione_id;

    IF gia_partecipa > 0 THEN
        UPDATE partecipaz_staff_ediz SET maglia = taglia
        WHERE id = gia_partecipa;
        LEAVE proc_body;
    END IF;

    INSERT INTO partecipaz_staff_ediz (staff, edizione, maglia) VALUES (staff_id, edizione_id, taglia);
    
    SET @arr = commissioni;
    WHILE @arr != '' DO
        SET element = SUBSTRING_INDEX(@arr, ',', 1);      
        INSERT INTO ruoli_staff (commissione, staffista, edizione) VALUES (element, staff_id, edizione_id);
        IF LOCATE(',', @arr) > 0 THEN
            SET @arr = SUBSTRING(@arr, LOCATE(',', @arr) + 1);
        ELSE
            SET @arr = '';
        END IF;
    END WHILE;
END; //

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
    GROUP BY t.id
END; //

DROP PROCEDURE IF EXISTS GetNomeSquadra //
CREATE PROCEDURE GetNomeSquadra(IN id INT)
BEGIN
    SELECT s.nome
    FROM squadre AS s
    WHERE s.id = id;
END; //

DROP PROCEDURE IF EXISTS CancellaSquadra //
CREATE PROCEDURE CancellaSquadra(IN id INT)
BEGIN
    DELETE FROM squadre 
    WHERE squadre.id = id;
END; //

DELIMITER ;