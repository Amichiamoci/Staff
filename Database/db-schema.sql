SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- Stored routines inherit the database defaults when they are created.
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci;
START TRANSACTION;
SET TIME_zone = "+00:00";


/*!40101 SET foreign_key_checks = 0 */;

--
-- Area DOCUMENTI
--

DROP TABLE IF EXISTS `tipi_documento`;
CREATE TABLE `tipi_documento` (
  `id`    INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `label` VARCHAR(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `tipi_documento` (`label`)
VALUES
('Carta d''Identità'),
('Patente di guida'),
('Passaporto'),
('Patente nautica'),
('Tessera universitaria'),
('Documento generico');

DROP TABLE IF EXISTS  `anagrafiche`;
CREATE TABLE `anagrafiche` (
  `id`               INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome`             VARCHAR(128) NOT NULL,
  `cognome`          VARCHAR(256) NOT NULL,
  `data_nascita`     DATE NOT NULL,
  `luogo_nascita`    VARCHAR(256) NOT NULL,
  `telefono`         VARCHAR(16) DEFAULT NULL,
  `email`            VARCHAR(64) DEFAULT NULL,
  `codice_fiscale`   VARCHAR(64) NOT NULL,
  `tipo_documento`   INT NOT NULL,
  `scadenza`         DATE DEFAULT NULL,
  `documento`        VARCHAR(4096) NOT NULL,
  `creation_ts`      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `update_ts`        TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `codice_fiscale` (`codice_fiscale`) USING BTREE,
  KEY `anagrafiche_email` (`email`),
  FOREIGN KEY (`tipo_documento`) REFERENCES `tipi_documento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


--
-- Area STAFF
--

DROP TABLE IF EXISTS `commissioni`;
CREATE TABLE `commissioni` (
  `id`   INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `parrocchie`;
CREATE TABLE `parrocchie` (
  `id`        INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome`      VARCHAR(256) NOT NULL,
  `indirizzo` VARCHAR(256) DEFAULT NULL,
  `website`   VARCHAR(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `parrocchie` (`nome`)
VALUES ('Diocesi (parrocchia generica)');

DROP TABLE IF EXISTS `staffisti`;
CREATE TABLE `staffisti` (
  `id`              INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dati_anagrafici` INT NOT NULL,
  `id_utente`       INT NOT NULL,
  `parrocchia`      INT NOT NULL,

  FOREIGN KEY (`dati_anagrafici`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`parrocchia`)      REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`id_utente`)       REFERENCES `utenti` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `ruoli_staff`;
CREATE TABLE `ruoli_staff` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `commissione` INT NOT NULL,
  `staffista`   INT NOT NULL,
  `edizione`    INT NOT NULL,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`commissione`) REFERENCES `commissioni` (`id`),
  FOREIGN KEY (`staffista`)   REFERENCES `staffisti` (`id`),
  FOREIGN KEY (`edizione`)    REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


--
-- Area PARTITE
--

DROP TABLE IF EXISTS `sport`;
CREATE TABLE `sport` (
  `id`   INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(256) NOT NULL,
  `area` VARCHAR(16) NOT NULL,

  CONSTRAINT `sport_area_allowed` CHECK (CAST(`area` AS BINARY) IN ('Calcio', 'Pallavolo', 'Basket'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `squadre`;
CREATE TABLE `squadre` (
  `id`         INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome`       VARCHAR(128) NOT NULL,
  `parrocchia` INT NOT NULL,
  `sport`      INT NOT NULL,
  `edizione`   INT NOT NULL,
  `referenti`  VARCHAR(2048) DEFAULT NULL,

  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`sport`)      REFERENCES `sport` (`id`),
  FOREIGN KEY (`edizione`)   REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `tipi_torneo`;
CREATE TABLE `tipi_torneo` (
  `id`   INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `tipi_torneo` (`id`, `nome`)
VALUES
(1, 'Italiana (girone)'),
(2, 'Eliminazione Diretta');

DROP TABLE IF EXISTS `tornei`;
CREATE TABLE `tornei` (
  `id`          INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `edizione`    INT NOT NULL,
  `nome`        VARCHAR(128) NOT NULL,
  `sport`       INT NOT NULL,
  `tipo_torneo` INT NOT NULL,

  FOREIGN KEY (`sport`)       REFERENCES `sport` (`id`),
  FOREIGN KEY (`tipo_torneo`) REFERENCES `tipi_torneo` (`id`),
  FOREIGN KEY (`edizione`)    REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `squadre_iscritti`;
CREATE TABLE `squadre_iscritti` (
  `squadra`  INT NOT NULL,
  `iscritto` INT NOT NULL,

  PRIMARY KEY (`squadra`, `iscritto`),
  FOREIGN KEY (`squadra`)  REFERENCES `squadre` (`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`iscritto`) REFERENCES `iscritti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `campi`;
CREATE TABLE `campi` (
  `id`        INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome`      VARCHAR(128) NOT NULL,
  `indirizzo` VARCHAR(256) NOT NULL,
  `posizione` POINT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `partite`;
CREATE TABLE `partite` (
  `id`             INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `torneo`         INT NOT NULL,
  `data`           DATE DEFAULT NULL,
  `orario`         TIME DEFAULT NULL,
  `campo`          INT DEFAULT NULL,
  `squadra_casa`   INT NOT NULL,
  `squadra_ospite` INT NOT NULL,
  `a_tavolino`     INT DEFAULT NULL,

  FOREIGN KEY (`campo`)          REFERENCES `campi` (`id`)   ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`squadra_casa`)   REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`squadra_ospite`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`torneo`)         REFERENCES `tornei` (`id`)  ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`a_tavolino`)     REFERENCES `squadre` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `punteggi`;
CREATE TABLE `punteggi` (
  `id`      INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `partita` INT NOT NULL,
  `home`    VARCHAR(8) NOT NULL,
  `guest`   VARCHAR(8) NOT NULL,

  FOREIGN KEY (`partita`) REFERENCES `partite` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `partecipaz_squad_torneo`;
CREATE TABLE `partecipaz_squad_torneo` (
  `torneo`  INT NOT NULL,
  `squadra` INT NOT NULL,

  PRIMARY KEY (`torneo`,`squadra`),
  FOREIGN KEY (`squadra`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`torneo`)  REFERENCES `tornei` (`id`)  ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


--
-- Area Sistema
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE `utenti` (
  `id`         INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_name`  VARCHAR(256) NOT NULL UNIQUE,
  `password`   VARCHAR(256) NOT NULL,
  `is_admin`   BOOLEAN NOT NULL DEFAULT FALSE,
  `is_blocked` BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `sessioni`;
CREATE TABLE `sessioni` (
  `id`         INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id`    INT NOT NULL,
  `user_flag`  VARCHAR(64) NOT NULL,
  `device_ip`  VARCHAR(32) DEFAULT NULL,
  `time_log`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `email`;
CREATE TABLE `email` (
  `id`       INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dest`     VARCHAR(64) NOT NULL,
  `subject`  VARCHAR(128) DEFAULT NULL,
  `body`     TEXT,
  `sent`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opened`   TIMESTAMP NULL DEFAULT NULL,
  `ricevuta` BOOLEAN NOT NULL DEFAULT TRUE,

  KEY `email_dest` (`dest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `token`;
CREATE TABLE `token` (
  `value`              VARCHAR(64) PRIMARY KEY NOT NULL,
  `secret`             VARCHAR(8) NOT NULL,
  `generation_date`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiration_date`    DATETIME NOT NULL,
  `usage_date`         DATETIME DEFAULT NULL,
  `user_id`            INT NOT NULL,
  `email`              VARCHAR(64) NOT NULL,
  `requesting_ip`      VARCHAR(15) DEFAULT NULL,
  `requesting_browser` VARCHAR(128) DEFAULT NULL,

  FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Area Iscrizioni
--

DROP TABLE IF EXISTS `edizioni`;
CREATE TABLE `edizioni` (
  `id`    INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `anno`  YEAR(4) NOT NULL UNIQUE,
  `motto` VARCHAR(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `edizioni` (`anno`)
VALUES (YEAR(CURRENT_DATE));

DROP TABLE IF EXISTS `punteggio_parrocchia`;
CREATE TABLE `punteggio_parrocchia` (
  `parrocchia` INT NOT NULL,
  `edizione`   INT NOT NULL,
  `punteggio`  VARCHAR(8) NOT NULL DEFAULT '0',

  PRIMARY KEY (`parrocchia`, `edizione`),
  FOREIGN KEY (`edizione`)   REFERENCES `edizioni` (`id`)   ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `iscritti`;
CREATE TABLE `iscritti` (
  `id`                 INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dati_anagrafici`    INT NOT NULL,
  `edizione`           INT NOT NULL,
  `tutore`             INT DEFAULT NULL,
  `certificato_medico` VARCHAR(2048) DEFAULT NULL,
  `parrocchia`         INT NOT NULL,
  `taglia_maglietta`   VARCHAR(8) NOT NULL,

  FOREIGN KEY (`dati_anagrafici`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`tutore`)          REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`parrocchia`)      REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`edizione`)        REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `partecipaz_staff_ediz`;
CREATE TABLE `partecipaz_staff_ediz` (
  `id`       INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `staff`    INT NOT NULL,
  `edizione` INT NOT NULL,
  `maglia`   VARCHAR(3) DEFAULT NULL,

  FOREIGN KEY (`staff`)    REFERENCES `staffisti` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `api_token`;
CREATE TABLE `api_token` (
  `id`   INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(256) NOT NULL,
  `key`  VARCHAR(128) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `api_usage`;
CREATE TABLE `api_usage` (
  `token_id`   INT NOT NULL,
  `ip_address` VARCHAR(15) NOT NULL,
  `last_usage` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hits`       INT NOT NULL DEFAULT 0,

  PRIMARY KEY (`token_id`, `ip_address`),
  FOREIGN KEY (`token_id`) REFERENCES `api_token`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE IF NOT EXISTS `web_app_logins` (
    `logged_at`  TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `email`      VARCHAR(64) NOT NULL,
    `token_id`   INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,

    PRIMARY KEY (`email`, `ip_address`, `logged_at`),
    KEY `web_app_logins_logged_at` (`logged_at`),
    KEY `web_app_logins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `cron`;
CREATE TABLE `cron` (
  `name`           VARCHAR(256) NOT NULL PRIMARY KEY,
  `function_name`  VARCHAR(256) NOT NULL,
  `last_run`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `interval_hours` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- System configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `name`    VARCHAR(64) NOT NULL PRIMARY KEY,
  `enabled` BOOLEAN NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `settings` (`name`, `enabled`)
VALUES
  ('subscriptions_enabled', TRUE),
  ('teams_enabled', TRUE),
  ('tournaments_enabled', FALSE),
  ('api_enabled', FALSE)
ON DUPLICATE KEY UPDATE `name` =
VALUES(`name`);

COMMIT;

/*!40101 SET foreign_key_checks = 1 */;
