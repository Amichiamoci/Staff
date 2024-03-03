DELIMITER //

DROP PROCEDURE IF EXISTS EditionStaffList //
CREATE PROCEDURE EditionStaffList(IN anno YEAR)
BEGIN
    DECLARE query_anno YEAR DEFAULT YEAR(CURRENT_DATE);
    IF anno IS NOT NULL THEN
        SET query_anno = anno;
    ELSE
        SET query_anno = YEAR(CURRENT_DATE);
    END IF;

    SELECT a.*, parrocchie.nome AS parrocchia, 
        s.id AS id_staffista, 
        p.is_referente AS referente, 
        GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') AS lista_commissioni
    FROM staffisti AS s
    INNER JOIN (partecipaz_staff_ediz AS p) ON p.staff = s.id
    INNER JOIN (edizioni AS e) ON p.edizione = e.id
    INNER JOIN (anagrafiche AS a) ON s.dati_anagrafici = a.id
    LEFT OUTER JOIN parrocchie ON s.parrocchia = parrocchie.id
    LEFT OUTER JOIN (ruoli_staff AS r) ON s.id = r.staffista AND r.edizione = e.id
    LEFT OUTER JOIN (commissioni AS c) ON r.commissione = c.id
    WHERE e.anno = query_anno
    GROUP BY s.id
    ORDER BY parrocchie.nome, a.cognome, a.nome;
END; //

DROP PROCEDURE IF EXISTS StaffList //
CREATE PROCEDURE StaffList(IN anno YEAR, IN all_years BOOLEAN)
body:BEGIN
    IF NOT all_years = 1 THEN
        CALL EditionStaffList(anno);
        LEAVE body;
    END IF;

    SELECT a.*, parrocchie.nome AS parrocchia, 
        s.id AS id_staffista, 
        GROUP_CONCAT(DISTINCT e.anno SEPARATOR ', ') AS partecipazioni
    FROM staffisti AS s
    INNER JOIN (anagrafiche AS a) ON s.dati_anagrafici = a.id
    LEFT OUTER JOIN parrocchie ON s.parrocchia = parrocchie.id
    LEFT OUTER JOIN (partecipaz_staff_ediz AS p) ON s.id = p.staff
    LEFT OUTER JOIN (edizioni AS e) ON p.edizione = e.id
    GROUP BY s.id
    ORDER BY parrocchie.nome, a.cognome, a.nome;
END; //

DROP PROCEDURE IF EXISTS RawStaffList //
CREATE PROCEDURE RawStaffList()
BEGIN
    SELECT CONCAT(a.nome, ' ', a.cognome) AS nome_completo, s.id AS staff 
    FROM staffisti AS s 
    LEFT JOIN (anagrafiche AS a) ON s.dati_anagrafici = a.id;
END; //


DROP PROCEDURE IF EXISTS PartecipaStaff //
CREATE PROCEDURE PartecipaStaff(IN staff_id INT, IN edizione_id INT, IN taglia VARCHAR(2), IN commissioni VARCHAR(64), IN referente BOOLEAN)
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

    INSERT INTO partecipaz_staff_ediz (staff, edizione, maglia, is_referente) VALUES (staff_id, edizione_id, taglia, referente);
    
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

DROP PROCEDURE IF EXISTS StaffData //
CREATE PROCEDURE StaffData(IN staff_id INT, IN anno YEAR)
BEGIN
    
    SELECT 
        IF (parr.nome IS NOT NULL, parr.nome, 'Non specificata') AS parrocchia,
        IF (parr.id IS NOT NULL, parr.id, 0) AS id_parrocchia,
        IF (r.comm IS NULL, 'Nessuna commissione', r.comm) AS commissioni,
        IF (r.tot_comm IS NULL, 0, r.tot_comm) AS totale_commissioni,
        IF (r.maglia IS NULL, 'Non scelta', r.maglia) AS maglia,
        IF (r.is_referente IS NULL, 0, r.is_referente) AS referente,
        IF (a.codice_fiscale IS NULL, '', a.codice_fiscale) AS cf,
        CONCAT(a.nome, ' ', a.cognome) AS nome
    FROM staffisti AS s
        INNER JOIN anagrafiche a ON a.id = s.dati_anagrafici
        LEFT OUTER JOIN parrocchie parr ON parr.id = s.parrocchia
        LEFT OUTER JOIN (
            SELECT p.maglia, p.staff, p.is_referente,
                GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') AS comm,
                COUNT(DISTINCT c.nome) AS tot_comm
            FROM edizioni AS e
                LEFT OUTER JOIN partecipaz_staff_ediz p ON e.id = p.edizione
                LEFT OUTER JOIN ruoli_staff r ON r.staffista = p.staff AND r.edizione = e.id
                LEFT OUTER JOIN commissioni c ON r.commissione = c.id
            WHERE e.anno = anno
            GROUP BY p.staff
        ) r ON r.staff = s.id
    WHERE s.id = staff_id;
END; //

DROP PROCEDURE IF EXISTS StaffByParrocchia //
CREATE PROCEDURE StaffByParrocchia(IN parrocchia_id INT, IN anno YEAR)
BEGIN
    IF anno IS NULL THEN
        SELECT 
            a.nome, a.cognome, 
            a.telefono, a.email, 
            FLOOR(DATEDIFF(CURRENT_DATE, a.data_nascita) / 365.24) AS "eta",
            IF (
                p.is_referente IS NULL OR p.is_referente = 0, 
                'Staffista', 
                CONCAT('Referente per il ', e.anno)) AS "ruolo"
        FROM staffisti s
            INNER JOIN anagrafiche a ON s.dati_anagrafici = a.id
            LEFT OUTER JOIN partecipaz_staff_ediz p ON p.staff = s.id
            LEFT OUTER JOIN edizioni e ON e.id = p.edizione
        WHERE s.parrocchia = parrocchia_id AND (e.anno IS NULL OR e.anno = YEAR(CURRENT_DATE));
    ELSE
        SELECT 
            a.nome, a.cognome, 
            a.telefono, a.email, 
            FLOOR(DATEDIFF(CURRENT_DATE, a.data_nascita) / 365.24) AS "eta",
            IF (
                p.is_referente IS NULL OR p.is_referente = 0, 
                'Staffista', 
                CONCAT('Referente per il ', e.anno)) AS "ruolo"
        FROM staffisti s
            INNER JOIN anagrafiche a ON s.dati_anagrafici = a.id
            INNER JOIN partecipaz_staff_ediz p ON p.staff = s.id
            INNER JOIN edizioni e ON e.id = p.edizione
        WHERE s.parrocchia = parrocchia_id AND e.anno = anno;
    END IF;
END; //

DROP FUNCTION IF EXISTS CodiceDocumentOk //
CREATE FUNCTION CodiceDocumentOk(codice VARCHAR(128), tipo INT)
RETURNS VARCHAR(128)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE doc_regex VARCHAR(64) DEFAULT NULL;
    DECLARE doc_label VARCHAR(64) DEFAULT NULL;

    IF codice IS NULL THEN
        RETURN 'Codice mancante!';
    END IF;

    SELECT t.regex, t.label INTO doc_regex, doc_label
    FROM tipi_documento AS t
    WHERE t.id = tipo;

    IF doc_regex IS NULL THEN
        RETURN NULL;
    END IF;

    IF TRIM(UPPER(codice)) REGEXP doc_regex THEN
        RETURN NULL;
    END IF;

    RETURN CONCAT(codice, ' non è codice di ', doc_label);
END ; //

DROP FUNCTION IF EXISTS ScadeInGiorni //
CREATE FUNCTION ScadeInGiorni(scadenza DATE, giorni INT)
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

DROP FUNCTION IF EXISTS ProblemaEta //
CREATE FUNCTION ProblemaEta(data_nascita DATE)
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN
    DECLARE eta INT DEFAULT 0;

    SET eta = Eta(data_nascita);

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

DROP FUNCTION IF EXISTS ProblemaTaglia //
CREATE FUNCTION ProblemaTaglia(taglia VARCHAR(8))
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

DROP FUNCTION IF EXISTS ProblemaEmail //
CREATE FUNCTION ProblemaEmail(email VARCHAR(64))
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN

    IF email IS NULL THEN
        RETURN 'Mancante!';
    END IF;

    SET @email_regex = '[a-z0-9]{1,}[\.\-\_a-z0-9]{0,}[a-z0-9]@[a-z]{1}[\.\-\_a-z0-9]{0,}\.[a-z]{1,10}';

    IF NOT TRIM(LOWER(email)) REGEXP @email_regex THEN
        RETURN CONCAT('Email "', email, '" non valida!');
    END IF;

    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS ProblemaTelefono //
CREATE FUNCTION ProblemaTelefono(telefono VARCHAR(16))
RETURNS VARCHAR(128)
DETERMINISTIC
BEGIN
    IF telefono IS NULL THEN
        RETURN 'Mancante!';
    END IF;

    IF NOT TRIM(telefono) REGEXP '[\+]{0,1}[0-9]+' THEN
        RETURN CONCAT('Numero "', telefono, '" non valido!');
    END IF;

    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS ProblemaTutore //
CREATE FUNCTION ProblemaTutore(data_nascita DATE, tutore INT)
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
        FROM anagrafiche a
        WHERE a.id = tutore) THEN
        -- Non e' memorizzato nessun tutore
        RETURN 'Mancante';
    END IF;
    
    -- Esiste un tutore, pero' puo' essere minorenne

    IF NOT EXISTS(
        SELECT * 
        FROM anagrafiche a
        WHERE a.id = tutore AND Eta(a.data_nascita) >= 18) THEN
        
        -- Tutore e' un minore, non va bene!
        RETURN 'Il tutore è minorenne';
    END IF;

    -- Altrimenti ok: tutore maggiorenne
    RETURN NULL;
END ; //

DROP FUNCTION IF EXISTS EmailVerified //
CREATE FUNCTION EmailVerified(_email VARCHAR(64))
RETURNS VARCHAR(128)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    IF _email IS NULL THEN
        RETURN NULL;
    END IF;

    IF EXISTS(
        SELECT * 
        FROM email e
        WHERE e.dest = _email AND e.ricevuta AND e.opened IS NOT NULL) THEN
        -- Esiste una mail almeno aperta dalla persona, esco direttamente
        RETURN NULL;
    END IF;

    IF NOT EXISTS(SELECT *
        FROM email e
        WHERE e.dest = _email) THEN
        -- Non e' stata inviata nessuna email
        RETURN 'Email non ancora verificata';
    END IF;

    IF NOT EXISTS(SELECT *
        FROM email e
        WHERE e.dest = _email AND e.ricevuta) THEN
        -- Esiste una mail non ricevuta, ma ne esistono di inviate
        RETURN 'Sembra non sia MAI stata ricevuta nessuna email';
    END IF;
    
    RETURN 'Email di verifica ricevuta, ma non aperta';
END ; //

DROP PROCEDURE IF EXISTS ProblemiParrocchia //
CREATE PROCEDURE ProblemiParrocchia(IN parrocchia_id INT, IN anno YEAR)
BEGIN
    SELECT 
        a.id,
        i.id AS iscrizione,
        UPPER(a.codice_fiscale) AS cf,
        CONCAT(a.nome, ' ', a.cognome) AS chi,
        SessoDaCF(a.codice_fiscale) AS sesso,
        CodiceDocumentOk(a.codice_documento, a.tipo_documento) AS doc_code,
        IF (a.documento IS NOT NULL, NULL, 'Mancante') AS doc,
        ScadeInGiorni(a.scadenza, 62) AS scadenza,
        IF (i.certificato_medico IS NOT NULL, NULL, 'Mancante') AS certificato,
        ProblemaTutore(a.data_nascita, i.tutore) AS tutore,
        ProblemaEta(a.data_nascita) AS eta,
        ProblemaTaglia(i.taglia_maglietta) AS maglia,
        ProblemaEmail(a.email) AS email,
        EmailVerified(a.email) AS email_verify,
        ProblemaTelefono(a.telefono) AS telefono
    FROM iscritti i
        INNER JOIN anagrafiche a ON i.dati_anagrafici = a.id
        INNER JOIN edizioni e ON i.edizione = e.id
    WHERE i.parrocchia = parrocchia_id AND e.anno = anno
    HAVING 
        doc_code IS NOT NULL OR 
        doc IS NOT NULL OR 
        scadenza IS NOT NULL OR
        certificato IS NOT NULL OR
        tutore IS NOT NULL OR
        eta IS NOT NULL OR
        maglia IS NOT NULL OR 
        email IS NOT NULL OR
        -- email_verify IS NOT NULL OR 
        -- Email verification problem is a warning, is shown only if there are other errors
        telefono IS NOT NULL;
END ; //

DELIMITER ;