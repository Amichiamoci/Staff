DELIMITER //

DROP PROCEDURE IF EXISTS GetAssociatedMail //
CREATE PROCEDURE GetAssociatedMail(IN u_id INT, OUT email_out VARCHAR(64))
BEGIN
    SET email_out = NULL;

    SELECT a.email INTO email_out 
    FROM utenti AS u
        INNER JOIN staffisti s ON s.id_utente = u.id
        INNER JOIN anagrafiche a ON s.dati_anagrafici = a.id
    WHERE u.id = u_id;
END; //

DROP PROCEDURE IF EXISTS SelectAssociatedMail //
CREATE PROCEDURE SelectAssociatedMail(IN u_id INT)
BEGIN
    CALL GetAssociatedMail(u_id, @email);
    SELECT @email AS "email";
END; //

DROP PROCEDURE IF EXISTS GetAssociatedMailByUserName //
CREATE PROCEDURE GetAssociatedMailByUserName(IN user_name VARCHAR(256))
BEGIN
    DECLARE id INT DEFAULT 0;

    SELECT utenti.id INTO id
    FROM utenti
    WHERE LOWER(utenti.user_name) = LOWER(user_name);

    IF id <> 0 THEN
        CALL GetAssociatedMail(id, @email_out);
        SELECT id, @email_out AS email;
    END IF;
END; //

DROP PROCEDURE IF EXISTS CreateEmail //
CREATE PROCEDURE CreateEmail(
    IN e_dest VARCHAR(64),
    IN e_subj VARCHAR(128),
    IN e_body TEXT)
BEGIN
    INSERT INTO email(dest, subject, body, opened) VALUES (e_dest, e_subj, e_body, NULL);
    SELECT LAST_INSERT_ID() AS 'id';
END; //

DROP PROCEDURE IF EXISTS OpenedEmail //
CREATE PROCEDURE OpenedEmail(IN id INT)
BEGIN    
    UPDATE `email`
    SET `email`.`opened` = CURRENT_TIMESTAMP
    WHERE `email`.`id` = id AND `email`.`opened` IS NULL;
END; //

DROP PROCEDURE IF EXISTS `ViewEmail` //
CREATE PROCEDURE `ViewEmail`(IN id INT)
BEGIN
    SELECT e.*
    FROM email_extended e
    WHERE e.id = id;
END; //

DELIMITER ;