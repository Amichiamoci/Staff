DELIMITER //




DROP PROCEDURE IF EXISTS RawStaffList //
CREATE PROCEDURE RawStaffList()
BEGIN
END; //


DROP PROCEDURE IF EXISTS `StaffByParrocchia` //
CREATE PROCEDURE `StaffByParrocchia`(IN parrocchia_id INT, IN anno YEAR)
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


DELIMITER ;