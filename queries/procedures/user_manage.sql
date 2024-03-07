DELIMITER //

DROP PROCEDURE IF EXISTS GetUserPassword //
CREATE PROCEDURE GetUserPassword(IN user_name VARCHAR(256))
BEGIN
    SELECT utenti.id, utenti.password, utenti.is_admin
    FROM utenti
    WHERE LOWER(utenti.user_name) = LOWER(user_name) AND utenti.is_blocked = 0;
END; //

DROP PROCEDURE IF EXISTS GetUserPasswordFromId //
CREATE PROCEDURE GetUserPasswordFromId(IN user_id INT)
BEGIN
    SELECT utenti.password
    FROM utenti
    WHERE utenti.id = user_id;
END; //

DROP PROCEDURE IF EXISTS SetUserPassword //
CREATE PROCEDURE SetUserPassword(IN id INT, IN new_hashed_password VARCHAR(256))
BEGIN
    UPDATE utenti 
    SET utenti.password = new_hashed_password
    WHERE utenti.id = id;
END; //

DROP PROCEDURE IF EXISTS SetUserName //
CREATE PROCEDURE SetUserName(IN id INT, IN new_username VARCHAR(256))
BEGIN
    DECLARE taken INT DEFAULT 0;

    SELECT utenti.id INTO taken
    FROM utenti
    WHERE LOWER(utenti.user_name) = LOWER(new_username);

    IF taken <> 0 THEN
        SET taken = 0;
    ELSE
        UPDATE utenti 
        SET utenti.user_name = new_username
        WHERE utenti.id = id;
        SET taken = 1;
    END IF;
    SELECT taken AS result;
END; //

DROP PROCEDURE IF EXISTS DeleteUser //
CREATE PROCEDURE DeleteUser(IN user_id INT)
BEGIN
    DELETE FROM utenti
    WHERE id = user_id;
END; //

DROP PROCEDURE IF EXISTS BanUser //
CREATE PROCEDURE BanUser(IN user_id INT)
BEGIN
    UPDATE utenti
    SET utenti.is_blocked = 1
    WHERE utenti.id = user_id;
END; //

DROP PROCEDURE IF EXISTS RestoreUser //
CREATE PROCEDURE RestoreUser(IN user_id INT)
BEGIN
    UPDATE utenti
    SET utenti.is_blocked = 0
    WHERE utenti.id = user_id;
END; //


DELIMITER ;