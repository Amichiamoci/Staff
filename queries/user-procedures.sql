DELIMITER //

DROP PROCEDURE IF EXISTS IsUserLogged //
CREATE PROCEDURE IsUserLogged(IN id INT, IN flag VARCHAR(64))
BEGIN
    SELECT u.id, u.user_name, u.is_admin, s.user_flag, s.id AS session_id
    FROM utenti AS u
    INNER JOIN (sessioni AS s) ON s.user_id = u.id
    WHERE s.user_flag = flag AND u.id = id AND TIMESTAMPDIFF(MINUTE, s.time_log, CURRENT_TIMESTAMP) < 31 AND u.is_blocked = 0;
END; //

DROP PROCEDURE IF EXISTS UpdateSession //
CREATE PROCEDURE UpdateSession(IN sess_id INT)
BEGIN
    IF sess_id <> 0 THEN
        UPDATE sessioni 
        SET time_log = CURRENT_TIMESTAMP
        WHERE id = sess_id;
    END IF;
END; //

DROP PROCEDURE IF EXISTS StartSession //
CREATE PROCEDURE StartSession(IN u_id INT, IN flag VARCHAR(64), IN dev_ip VARCHAR(32))
BEGIN
    DECLARE session_id INT DEFAULT 0;
    IF u_id <> 0 THEN
        INSERT INTO sessioni (user_id, user_flag, device_ip) VALUES (u_id, flag, dev_ip);
        SET session_id = LAST_INSERT_ID();
    END IF;
    SELECT session_id;
END; //

DROP PROCEDURE IF EXISTS GetUserPassword //
CREATE PROCEDURE GetUserPassword(IN user_name VARCHAR(256))
BEGIN
    SELECT utenti.id, utenti.password
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

DROP FUNCTION IF EXISTS PrintOnlineTime //
CREATE FUNCTION PrintOnlineTime(time_log TIMESTAMP)
RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
    DECLARE time_diff INT DEFAULT 0;

    IF time_log IS NULL THEN
        RETURN 'Mai collegato';
    END IF;

    SET time_diff = TIMESTAMPDIFF(MINUTE, time_log, CURRENT_TIMESTAMP);

    RETURN (CASE 
        WHEN time_diff < 5 THEN 'Online adesso'
        WHEN time_diff < 181 THEN CONCAT("Online ", time_diff, " minuti fa")
        ELSE DATE_FORMAT(
            time_log, 
            IF (
                TIMESTAMPDIFF(DAY, time_log, CURRENT_TIMESTAMP) = 0 AND 
                DAY(time_log) = DAY(CURRENT_TIMESTAMP), 
                    'Online oggi alle %H:%i',
                    'Online il %d/%m/%Y alle %H:%i'
                )
            ) END
        );
END //

DROP PROCEDURE IF EXISTS AllUsers //
CREATE PROCEDURE AllUsers()
BEGIN
    SELECT u.id, u.user_name, u.is_admin, u.is_blocked, MAX(s.time_log) AS last_seen_time,
        PrintOnlineTime(MAX(s.time_log)) AS last_seen
    FROM utenti AS u
        LEFT OUTER JOIN (sessioni AS s) ON s.user_id = u.id
    GROUP BY u.id
    ORDER BY last_seen_time DESC, u.user_name DESC;
END; //

DROP PROCEDURE IF EXISTS UsersActivity //
CREATE PROCEDURE UsersActivity(IN lim INT)
BEGIN
    IF lim IS NOT NULL AND lim > 0 THEN
        SELECT DISTINCT u.user_name, s.time_log, s.time_start, s.user_flag, s.device_ip
        FROM sessioni AS s
        LEFT JOIN (utenti AS u) ON u.id = s.user_id
        WHERE s.time_log > s.time_start
        ORDER BY s.time_start DESC, s.time_log DESC, u.user_name
        LIMIT lim;
    ELSE
        SELECT DISTINCT u.user_name, s.time_log, s.time_start, s.user_flag, s.device_ip
        FROM sessioni AS s
        LEFT JOIN (utenti AS u) ON u.id = s.user_id
        WHERE s.time_log > s.time_start
        ORDER BY s.time_start DESC, s.time_log DESC, u.user_name;
    END IF;
END; //

DROP PROCEDURE IF EXISTS GetAssociatedMail //
CREATE PROCEDURE GetAssociatedMail(IN u_id INT, OUT email_out VARCHAR(64))
BEGIN
    SET email_out = NULL;

    SELECT a.email INTO email_out 
    FROM utenti AS u
    INNER JOIN (staffisti AS s) ON s.id_utente = u.id
    INNER JOIN (anagrafiche AS a) ON s.dati_anagrafici = a.id
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
    UPDATE email
    SET email.opened = CURRENT_TIMESTAMP
    WHERE email.id = id AND email.opened IS NULL;
END; //

DROP PROCEDURE IF EXISTS ListEmail //
CREATE PROCEDURE ListEmail()
BEGIN
    SELECT 
        email.id, 
        email.ricevuta,
        email.dest AS "destinatario", 
        IF (email.subject IS NULL, "", email.subject) AS "oggetto", 
        DATE_FORMAT(email.sent, "Inviata il %d/%m/%Y alle %H:%i") AS "inviata", 
        IF (email.opened IS NULL, "Non ancora aperta", DATE_FORMAT(email.opened, "Aperta il %d/%m/%Y alle %H:%i")) AS "aperta"
    FROM email
    ORDER BY email.sent DESC;
END; //

DROP PROCEDURE IF EXISTS ViewEmail //
CREATE PROCEDURE ViewEmail(IN id INT)
BEGIN
    SELECT 
        email.id, 
        email.ricevuta,
        email.dest AS "destinatario", 
        IF (email.subject IS NULL, "", email.subject) AS "oggetto", 
        DATE_FORMAT(email.sent, "Inviata il %d/%m/%Y alle %H:%i") AS "inviata", 
        IF (email.opened IS NULL, "Non ancora aperta", DATE_FORMAT(email.opened, "Aperta il %d/%m/%Y alle %H:%i")) AS "aperta",
        IF (email.body IS NULL, "", email.body) AS testo
    FROM email
    WHERE email.id = id;
END; //

DROP PROCEDURE IF EXISTS StopMaintenance //
CREATE PROCEDURE StopMaintenance()
BEGIN
    UPDATE server_var
    SET server_var.value = 'ONLINE'
    WHERE LOWER(server_var.name) = 'status';
END; //

DROP PROCEDURE IF EXISTS StartMaintenance //
CREATE PROCEDURE StartMaintenance()
BEGIN
    UPDATE server_var
    SET server_var.value = 'IN MANUTENZIONE'
    WHERE LOWER(server_var.name) = 'status';
END; //

DROP PROCEDURE IF EXISTS BlockSystem //
CREATE PROCEDURE BlockSystem()
BEGIN
    UPDATE server_var
    SET server_var.value = 'OFFLINE'
    WHERE LOWER(server_var.name) = 'status';
END; //

DROP PROCEDURE IF EXISTS GetSystemStatus //
CREATE PROCEDURE GetSystemStatus(OUT ret VARCHAR(256))
BEGIN
    SELECT server_var.value INTO ret
    FROM server_var
    WHERE LOWER(server_var.name) = 'status';
END; //

DROP PROCEDURE IF EXISTS SelectSystemStatus //
CREATE PROCEDURE SelectSystemStatus()
BEGIN
    CALL GetSystemStatus(@status);
    SELECT @status AS "status";
END; //

DROP PROCEDURE IF EXISTS IsSystemAccessible //
CREATE PROCEDURE IsSystemAccessible()
BEGIN
    CALL GetSystemStatus(@status);

    IF @status IS NULL OR NOT LOWER(@status) = 'online' THEN
        SELECT 'No' AS 'result';
    ELSE
        SELECT 'Yes' AS 'result';
    END IF;
END; //

DELIMITER ;