DELIMITER //

DROP PROCEDURE IF EXISTS `ProblemiParrocchia` //
CREATE PROCEDURE `ProblemiParrocchia` (IN parrocchia_id INT, IN anno YEAR)
BEGIN
    SELECT 
        a.`id`,
        i.`id` AS "iscrizione",
        UPPER(a.`codice_fiscale`) AS "cf",
        CONCAT(a.`nome`, ' ', a.`cognome`) AS "chi",
        `SessoDaCF`(a.`codice_fiscale`) AS "sesso",

        `CodiceDocumentOk`(a.`codice_documento`, a.`tipo_documento`) AS "doc_code",
        IF (a.`documento` IS NOT NULL, NULL, 'Mancante') AS "doc",
        `ScadeInGiorni`(a.scadenza, 62) AS "scadenza",

        IF (i.`certificato_medico` IS NOT NULL, NULL, 'Mancante') AS "certificato",
        `ProblemaTutore` (a.`data_nascita`, i.`tutore`) AS "tutore",

        `ProblemaEta` (a.`data_nascita`) AS "eta",
        `ProblemaTaglia` (i.`taglia_maglietta`) AS "maglia",

        `ProblemaEmail` (a.`email`) AS "email",
        -- `EmailVerified` (a.`email`) AS "email_verify",
        `ProblemaTelefono` (a.`telefono`) AS "telefono"
    FROM `iscritti` i
        INNER JOIN `anagrafiche` a ON i.`dati_anagrafici` = a.`id`
        INNER JOIN `edizioni` e ON i.`edizione` = e.`id`
    WHERE i.`parrocchia` = parrocchia_id AND e.`anno` = anno
    HAVING 
        -- We show the row only if there are important errors
        doc IS NOT NULL OR 
        scadenza IS NOT NULL OR
        certificato IS NOT NULL OR
        tutore IS NOT NULL OR
        eta IS NOT NULL OR
        maglia IS NOT NULL OR 
        email IS NOT NULL
    ;
END ; //

DELIMITER ;