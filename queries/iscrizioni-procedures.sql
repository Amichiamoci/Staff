DELIMITER //

DROP PROCEDURE IF EXISTS IscrizioniList //
CREATE PROCEDURE IscrizioniList(IN anno YEAR, IN id_parrocchia INT)
BEGIN
    -- anno IS NULL AND id_parrocchia IS NULL -> tutte le anagrafiche, di tutte le parrocchie 
    -- anno = X AND id_parrocchia IS NULL -> tutti gli iscritti dell'anno X
    -- anno = X AND id_parrocchia = Y -> tutti gli iscritti dell'anno X della parrocchia Y
    
    SELECT 
        a.id, 
        a.cognome, 
        a.nome, 
        DATE_FORMAT(a.data_nascita, '%d/%m/%Y') AS "data_nascita", 
        Eta(a.data_nascita) AS "eta", 
        a.telefono, 
        a.email, 
        a.codice_fiscale AS cf, 
        SessoDaCF(a.codice_fiscale) AS "sesso",
        a.documento, 
        a.codice_documento, t.label, 
        p.nome AS "parrocchia", 
        p.id AS "id_parrocchia",
        e.anno,
        e.id AS "id_edizione",
        IF (i.id IS NOT NULL, 
            LPAD(HEX(i.id), 8, '0'), 
            CONCAT('Non iscritto per il ', 
                IF (anno IS NULL, YEAR(CURRENT_DATE), anno))
            ) AS "codice_iscrizione",
        IF (i.id IS NULL, 
            CONCAT('Iscrivi per il ', 
                IF (anno IS NULL, YEAR(CURRENT_DATE), anno)),
            NULL
            ) AS "iscrivi",
        i.id AS id_iscrizione,
        IF (a.self_generated, 'Persona stessa', 'Staffista') AS "creatore_dati",
        i.certificato_medico,
        i.taglia_maglietta AS "maglia",
        CONCAT (a2.cognome, ' ', a2.nome) AS "tutore",
        i.tutore AS "id_tutore"
    FROM anagrafiche AS a
        INNER JOIN (tipi_documento AS t) ON a.tipo_documento = t.id
        LEFT OUTER JOIN (iscritti AS i) ON a.id = i.dati_anagrafici
        LEFT OUTER JOIN (edizioni AS e) ON i.edizione = e.id
        LEFT OUTER JOIN (parrocchie AS p) ON i.parrocchia = p.id
        LEFT OUTER JOIN (anagrafiche AS a2) ON i.tutore = a2.id
    WHERE (e.anno = anno OR anno IS NULL) AND (id_parrocchia = p.id OR id_parrocchia IS NULL)
    GROUP BY a.id, i.id
    HAVING e.anno = YEAR(CURRENT_DATE) OR e.anno IS NULL
    ORDER BY parrocchia DESC, YEAR(a.data_nascita) ASC, a.cognome ASC, a.nome ASC;
END; //

DROP PROCEDURE IF EXISTS SingolaIscrizione //
CREATE PROCEDURE SingolaIscrizione(IN id INT)
BEGIN    
    SELECT 
        a.id, 
        a.cognome, 
        a.nome, 
        DATE_FORMAT(a.data_nascita, '%d/%m/%Y') AS "data_nascita", 
        Eta(a.data_nascita) AS "eta", 
        a.telefono, 
        a.email, 
        a.codice_fiscale AS cf, 
        SessoDaCF(a.codice_fiscale) AS "sesso",
        a.documento, 
        a.codice_documento, t.label, 
        p.nome AS "parrocchia", 
        p.id AS "id_parrocchia",
        e.anno,
        e.id AS "id_edizione",
        LPAD(HEX(i.id), 8, '0') AS "codice_iscrizione",
        NULL AS "iscrivi",
        i.id AS id_iscrizione,
        IF (a.self_generated, 'Persona stessa', 'Staffista') AS "creatore_dati",
        i.certificato_medico,
        i.taglia_maglietta AS "maglia",
        CONCAT (a2.cognome, ' ', a2.nome) AS "tutore",
        i.tutore AS "id_tutore"
    FROM anagrafiche AS a
        INNER JOIN (tipi_documento AS t) ON a.tipo_documento = t.id
        INNER JOIN (iscritti AS i) ON a.id = i.dati_anagrafici
        INNER JOIN (edizioni AS e) ON i.edizione = e.id
        INNER JOIN (parrocchie AS p) ON i.parrocchia = p.id
        LEFT OUTER JOIN (anagrafiche AS a2) ON i.tutore = a2.id
    WHERE i.id = id;
END; //

DROP PROCEDURE IF EXISTS NonIscrittiNonStaff //
CREATE PROCEDURE NonIscrittiNonStaff(IN anno YEAR)
BEGIN
    -- anno IS NULL -> tutte le anagrafiche di chi non e' mai stato iscritto 
    -- anno = X -> tutti i non iscritti e non staff dell'anno X
    
    SELECT 
        a.id, 
        a.cognome, 
        a.nome, 
        DATE_FORMAT(a.data_nascita, '%d/%m/%Y') AS "data_nascita", 
        FLOOR(DATEDIFF(CURRENT_DATE, a.data_nascita) / 365.25) AS "eta", 
        a.telefono, 
        a.email, 
        a.codice_fiscale AS cf, 
        a.documento, 
        a.codice_documento, t.label, 
        SessoDaCF(a.codice_fiscale) AS "sesso",
        IF (a.self_generated, 'Persona stessa', 'Staffista') AS "creatore_dati"
    FROM anagrafiche AS a
        INNER JOIN (tipi_documento AS t) ON a.tipo_documento = t.id
    WHERE 
        NOT EXISTS (
            SELECT * 
            FROM iscritti i
                INNER JOIN edizioni e ON e.id = i.edizione
            WHERE i.dati_anagrafici = a.id AND (
                e.anno = anno OR anno IS NULL
            )) 
        AND
        NOT EXISTS (
            SELECT * 
            FROM staffisti s
                INNER JOIN partecipaz_staff_ediz p ON p.staff = s.id
                INNER JOIN edizioni e ON e.id = p.edizione
            WHERE s.dati_anagrafici = a.id AND (
                e.anno = anno OR anno IS NULL
            )) 
    ORDER BY YEAR(a.data_nascita) ASC, a.cognome ASC, a.nome ASC;
END; //

DROP PROCEDURE IF EXISTS NomeDaAnagrafica //
CREATE PROCEDURE NomeDaAnagrafica(IN anagrafica INT)
BEGIN
    SELECT CONCAT(nome, ' ', cognome) AS nome_completo 
    FROM anagrafiche 
    WHERE id = anagrafica;
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
    IN doc_expires DATE,
    IN doc_addr VARCHAR(4096),
    IN abort_if_existing BOOLEAN)
crea_anagrafica_body:BEGIN
    DECLARE id INT DEFAULT 0;

    SELECT a.id INTO id FROM anagrafiche AS a
    WHERE LOWER(a.codice_fiscale) = TRIM(LOWER(cf));

    IF id <> 0 AND abort_if_existing = 1 THEN
        SELECT 0 AS 'id';
        LEAVE crea_anagrafica_body;
    END IF;

    IF id <> 0 THEN
        /*User already exists, we just update info*/
        UPDATE anagrafiche SET
        anagrafiche.nome = CASE 
            WHEN nome IS NOT NULL AND nome != '' THEN TRIM(nome)
            ELSE anagrafiche.nome END,
        anagrafiche.cognome = CASE 
            WHEN cognome IS NOT NULL AND cognome != '' THEN TRIM(cognome)
            ELSE anagrafiche.cognome END,
        anagrafiche.data_nascita = CASE 
            WHEN STR_TO_DATE(compleanno, '%d,%m,%Y') IS NOT NULL THEN compleanno
            ELSE anagrafiche.data_nascita END,
        anagrafiche.luogo_nascita = CASE 
            WHEN provenienza IS NOT NULL AND provenienza != '' THEN TRIM(provenienza)
            ELSE anagrafiche.luogo_nascita END,
        anagrafiche.telefono = CASE 
            WHEN telefono IS NOT NULL AND telefono != '' THEN REPLACE(telefono, ' ', '')
            ELSE anagrafiche.telefono END,
        anagrafiche.email = CASE 
            WHEN email IS NOT NULL AND email != '' THEN REPLACE(email, ' ', '')
            ELSE anagrafiche.email END,
        anagrafiche.tipo_documento = CASE 
            WHEN doc_type IS NOT NULL THEN doc_type
            ELSE anagrafiche.tipo_documento END,
        anagrafiche.codice_documento = CASE 
            WHEN doc_code IS NOT NULL AND doc_code != '' THEN REPLACE(UPPER(doc_code), ' ', '')
            ELSE anagrafiche.codice_documento END,
        anagrafiche.scadenza = CASE 
            WHEN doc_expires IS NOT NULL AND doc_expires != '' THEN doc_expires
            ELSE anagrafiche.scadenza END,
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
            codice_documento, scadenza, documento, 
            self_generated)
        VALUES (
            nome, cognome,
            compleanno, 
            TRIM(provenienza),
            REPLACE(telefono, ' ', ''), 
            REPLACE(email, ' ', ''),
            REPLACE(UPPER(cf), ' ', ''), doc_type,
            REPLACE(UPPER(doc_code), ' ', ''), doc_expires, doc_addr,
            abort_if_existing);
        SET id = LAST_INSERT_ID();
    END IF;

    SELECT id;

END; //

DROP PROCEDURE IF EXISTS ContaMaglie //
CREATE PROCEDURE ContaMaglie(IN anno YEAR)
BEGIN
    SET @query = NULL;
    SELECT
        GROUP_CONCAT(
            DISTINCT
            CONCAT(
                'SUM(IF(p.taglia_maglietta = "',
                m.taglia,
                '", 1, 0)) AS ',
                CONCAT('`', m.taglia, '`')
            )
            SEPARATOR ", "
        ) INTO @query
    FROM (
        SELECT taglia_maglietta AS taglia 
        FROM iscritti 
        UNION ALL 
        SELECT maglia AS taglia 
        FROM partecipaz_staff_ediz) m;

    SET @query = CONCAT('SELECT p.nome AS Parrocchia, ', @query, 
        ' FROM (
            SELECT i.taglia_maglietta, i.edizione, parrocchie.nome
                FROM parrocchie
                    INNER JOIN iscritti i ON parrocchie.id = i.parrocchia
            UNION ALL 
            SELECT pse.maglia AS taglia_maglietta, pse.edizione, CONCAT("Staffisti ", YEAR(CURRENT_DATE)) AS nome
                FROM staffisti s
                    INNER JOIN partecipaz_staff_ediz pse ON pse.staff = s.id) p
        INNER JOIN edizioni e ON p.edizione = e.id
        WHERE e.anno = ', anno, ' 
        GROUP BY Parrocchia');

    PREPARE stmt FROM @query;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END ; //

DROP PROCEDURE IF EXISTS ListaMaglie //
CREATE PROCEDURE ListaMaglie(IN anno YEAR, IN group_names BOOLEAN)
lista_body:BEGIN 
    SET @_anno = YEAR(CURRENT_DATE);

    IF anno IS NOT NULL THEN
        SET @_anno = anno;
    END IF;

    IF group_names THEN
        CALL ContaMaglie(@_anno);
        LEAVE lista_body;
    END IF;

    SELECT a.cognome AS Cognome, a.nome AS Nome, i.nome AS Parrocchia, i.taglia_maglietta AS Taglia
        FROM (
            SELECT p.nome, i.taglia_maglietta, i.dati_anagrafici, i.edizione
                FROM iscritti i
                INNER JOIN parrocchie p ON i.parrocchia = p.id
            UNION ALL
            SELECT CONCAT("Staffisti ", YEAR(CURRENT_DATE)) AS nome, pse.maglia AS taglia_maglietta, s.dati_anagrafici, pse.edizione
                FROM staffisti s
                INNER JOIN partecipaz_staff_ediz pse ON pse.staff = s.id
            ) i
        INNER JOIN anagrafiche a ON i.dati_anagrafici = a.id
        INNER JOIN edizioni e ON i.edizione = e.id
    WHERE e.anno = @_anno
    ORDER BY Parrocchia, Taglia, Cognome, Nome;
END ; //

DROP FUNCTION IF EXISTS SessoDaCF //
CREATE FUNCTION SessoDaCF(cf VARCHAR(16))
RETURNS CHAR(1) 
DETERMINISTIC
BEGIN
    DECLARE giorno INT DEFAULT NULL;

    IF cf IS NULL OR NOT LENGTH(cf) = 16 THEN
        RETURN NULL;
    END IF;

    SET giorno = CAST(SUBSTRING(cf, 10, 2) AS SIGNED);
    
    IF giorno > 40 THEN
        RETURN 'F';
    ELSE
        RETURN 'M';
    END IF;

END ; //

DROP FUNCTION IF EXISTS Eta //
CREATE FUNCTION Eta(data_nascita DATE)
RETURNS INT 
DETERMINISTIC
BEGIN
    IF data_nascita IS NULL THEN
        RETURN 0;
    END IF;

    RETURN FLOOR(DATEDIFF(CURRENT_DATE, data_nascita) / 365.25);
END ; //

DELIMITER ;

CREATE OR REPLACE VIEW compleanni_oggi AS
SELECT DISTINCT 
    DATE_FORMAT(a.data_nascita, "%d/%m") AS compleanno,
    a.nome,
    a.cognome,
    (YEAR(CURRENT_DATE) - YEAR(a.data_nascita)) AS eta,
    a.email
FROM anagrafiche AS a
WHERE DATE_FORMAT(a.data_nascita, "%d/%m") = DATE_FORMAT(CURRENT_DATE, "%d/%m");

CREATE OR REPLACE VIEW iscrizioni_per_csi AS
SELECT 
	a.cognome, a.nome, 
    SessoDaCF(a.codice_fiscale) AS "sesso", 
    a.luogo_nascita, 
    DATE_FORMAT(a.data_nascita, "%d/%m/%Y") AS "data_nascita",
    IF (a.telefono IS NULL, '', a.telefono) AS "telefono",
    IF (a.email IS NULL, '', a.email) AS "email"
FROM iscritti i
	INNER JOIN anagrafiche a ON a.id = i.dati_anagrafici
    INNER JOIN edizioni e ON e.id = i.edizione
WHERE e.anno = YEAR(CURRENT_DATE)
ORDER BY i.parrocchia;