DELIMITER //

DROP FUNCTION IF EXISTS `PunteggioInTorneo` //
CREATE FUNCTION `PunteggioInTorneo`(`torneo` INT, `tipo_torneo` INT, `team` INT)
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
        RETURN "0 0 0";
    END IF;
    
    IF tipo_torneo = 2 THEN
        RETURN `PunteggioInEliminazioneDiretta`(`torneo`, `team`);
    END IF;

    IF tipo_torneo <> 1 THEN
        -- Tipologia non gestita
        RETURN "0 0 0";
    END IF;

    SELECT 
        SUM(`EsitoPartita`(p.`id`, `team`)),
        COUNT(DISTINCT p.`id`),
        SUM(IF (EXISTS(SELECT * FROM `punteggi` r WHERE r.`partita` = p.`id`), 0, 1))
        INTO _punteggio, _previste, _da_giocare
    FROM `partite` AS p
    WHERE p.`torneo` = `torneo` AND (p.`squadra_casa` = `team` OR p.`squadra_ospite` = `team`);

    IF _punteggio IS NULL THEN
        SET _punteggio = 0;
    END IF;
    IF _da_giocare IS NULL THEN
        SET _da_giocare = 0;
    END IF;

    RETURN CONCAT(_previste, ' ', _da_giocare, ' ', _punteggio);
END ; //

DROP FUNCTION IF EXISTS `PunteggioInEliminazioneDiretta` //
CREATE FUNCTION `PunteggioInEliminazioneDiretta`(torneo INT, team INT)
RETURNS VARCHAR (64)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE _previste INT DEFAULT 0;
    DECLARE _da_giocare INT DEFAULT 0;

    DECLARE _squadre_partecipanti INT DEFAULT 0;
    DECLARE _partite_vinte INT DEFAULT 0;
    DECLARE _esito_prima_partita INT DEFAULT 0;

    IF torneo IS NULL OR team IS NULL THEN
        RETURN NULL;
    END IF;

    SELECT COUNT(*) INTO _squadre_partecipanti
    FROM `partecipaz_squad_torneo` 
    WHERE `partecipaz_squad_torneo`.`torneo` = torneo;

    IF MOD(_squadre_partecipanti, 2) <> 0 OR _squadre_partecipanti = 0 THEN
        RETURN NULL;
    END IF;
    
    SELECT 
        SUM(FLOOR(`EsitoPartita`(p.`id`, team) / 2)),
        COUNT(DISTINCT p.`id`),
        SUM(IF (EXISTS(SELECT * FROM `punteggi` r WHERE r.`partita` = p.`id`), 0, 1))
        INTO _partite_vinte, _previste, _da_giocare
    FROM `partite` AS p
    WHERE p.`torneo` = torneo AND (p.`squadra_casa` = team OR p.`squadra_ospite` = team);

    IF _da_giocare IS NULL THEN
        SET _da_giocare = 0;
    END IF;

    IF _partite_vinte = _squadre_partecipanti / 2 THEN
        -- Ha battuto tutte le altre squadre
        RETURN CONCAT(_previste, ' ', _da_giocare, ' ', _squadre_partecipanti);
    END IF; 

    IF _partite_vinte = 0 THEN
        -- Ha perso tutte le partite
        RETURN CONCAT(_previste, ' ', _da_giocare, ' ', 0);
    END IF;

    --
    -- D'ora in poi funziona solo se ci sono 4 partecipanti
    --
    
    -- Ha vinto la sua prima partita giocata?
    SELECT FLOOR(`EsitoPartita`(p.`id`, team) / 2) INTO _esito_prima_partita
    FROM `partite` AS p
    WHERE p.`torneo` = torneo AND (p.`squadra_casa` = team OR p.`squadra_ospite` = team)
    ORDER BY p.`data` ASC
    LIMIT 1;

    IF _esito_prima_partita = 1 THEN
        RETURN CONCAT(_previste, ' ', _da_giocare, ' ', _squadre_partecipanti - 1);
    END IF;

    RETURN CONCAT(_previste, ' ', _da_giocare, ' ', _squadre_partecipanti - 2);
END ; //


DELIMITER ;