SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET TIME_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40101 SET foreign_key_checks = 0 */;

--
-- Area DOCUMENTI
--

DROP TABLE IF EXISTS `tipi_documento`;
CREATE TABLE IF NOT EXISTS `tipi_documento` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `label` VARCHAR(64) DEFAULT NULL,
  `regex` VARCHAR(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `tipi_documento` (`label`, `regex`) VALUES 
("Carta d'Identità", '(C[A-Z][0-9]{5}[A-Z]{2})|([A-Z]{2}[0-9]{7})'),
('Patente di guida', '[A-Z]{2}[0-9]{7}[A-Z]'),
('Passaporto', '[A-Z]{2}[0-9]{7}'),
('Patente nautica', NULL),
('Tessera universitaria', '[0-9]{1,}'),
('Documento generico', NULL)
;

DROP TABLE IF EXISTS  `anagrafiche`;
CREATE TABLE IF NOT EXISTS `anagrafiche` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(128) NOT NULL,
  `cognome` VARCHAR(256) NOT NULL,
  `data_nascita` DATE NOT NULL,
  `luogo_nascita` VARCHAR(256) NOT NULL,
  `telefono` VARCHAR(16) DEFAULT NULL,
  `email` VARCHAR(64) DEFAULT NULL,
  `codice_fiscale` VARCHAR(64) NOT NULL,
  `tipo_documento` INT NOT NULL,
  `codice_documento` VARCHAR(128) DEFAULT NULL,
  `scadenza` DATE DEFAULT NULL,
  `documento` VARCHAR(4096) NOT NULL,
  `self_generated` BOOLEAN NOT NULL DEFAULT '0',
  `creation_ts` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `update_ts` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `codice_fiscale` (`codice_fiscale`) USING BTREE,
  FOREIGN KEY (`tipo_documento`) REFERENCES `tipi_documento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


--
-- Area STAFF
--

DROP TABLE IF EXISTS `commissioni`;
CREATE TABLE IF NOT EXISTS `commissioni` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `parrocchie`;
CREATE TABLE IF NOT EXISTS `parrocchie` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(256) NOT NULL,
  `indirizzo` VARCHAR(256) DEFAULT NULL,
  `website` VARCHAR(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `parrocchie` (`nome`) VALUES ('Diocesi (parrocchia generica)');

DROP TABLE IF EXISTS `staffisti`;
CREATE TABLE IF NOT EXISTS `staffisti` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dati_anagrafici` INT NOT NULL,
  `id_utente` INT NOT NULL,
  `parrocchia` INT NOT NULL,

  FOREIGN KEY (`dati_anagrafici`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `ruoli_staff`;
CREATE TABLE IF NOT EXISTS `ruoli_staff` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `commissione` INT NOT NULL,
  `staffista` INT NOT NULL,
  `edizione` INT NOT NULL,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`commissione`) REFERENCES `commissioni` (`id`),
  FOREIGN KEY (`staffista`) REFERENCES `staffisti` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


--
-- Area PARTITE
--

DROP TABLE IF EXISTS `sport`;
CREATE TABLE IF NOT EXISTS `sport` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(256) NOT NULL,
  `area` VARCHAR(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `squadre`;
CREATE TABLE IF NOT EXISTS `squadre` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(128) NOT NULL,
  `parrocchia` INT NOT NULL,
  `sport` INT NOT NULL,
  `edizione` INT NOT NULL,
  `referenti` VARCHAR(2048) DEFAULT NULL,

  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`sport`) REFERENCES `sport` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `tipi_torneo`;
CREATE TABLE IF NOT EXISTS `tipi_torneo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `tipi_torneo` (`id`, `nome`) VALUES 
(1, 'Italiana (girone)'),
(2, 'Eliminazione Diretta');

DROP TABLE IF EXISTS `tornei`;
CREATE TABLE IF NOT EXISTS `tornei` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `edizione` INT NOT NULL,
  `nome` VARCHAR(128) NOT NULL,
  `sport` INT NOT NULL,
  `tipo_torneo` INT NOT NULL,
  `successore` INT DEFAULT NULL,
  
  FOREIGN KEY (`sport`) REFERENCES `sport` (`id`),
  FOREIGN KEY (`tipo_torneo`) REFERENCES `tipi_torneo` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`),
  FOREIGN KEY (`successore`) REFERENCES `tornei`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `squadre_iscritti`;
CREATE TABLE IF NOT EXISTS `squadre_iscritti` (
  `squadra` INT NOT NULL,
  `iscritto` INT NOT NULL,

  PRIMARY KEY (`squadra`, `iscritto`),
  FOREIGN KEY (`squadra`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`iscritto`) REFERENCES `iscritti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `campi`;
CREATE TABLE IF NOT EXISTS `campi` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` VARCHAR(128) NOT NULL,
  `indirizzo` VARCHAR(256) NOT NULL,
  `posizione` POINT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `partite`;
CREATE TABLE IF NOT EXISTS `partite` (
  `id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `torneo` INT NOT NULL,
  `data` DATE DEFAULT NULL,
  `orario` TIME DEFAULT NULL,
  `campo` INT DEFAULT NULL,
  `squadra_casa` INT NOT NULL,
  `squadra_ospite` INT NOT NULL,
  `a_tavolino` INT DEFAULT NULL,
  
  FOREIGN KEY (`campo`) REFERENCES `campi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`squadra_casa`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`squadra_ospite`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`torneo`) REFERENCES `tornei` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`a_tavolino`) REFERENCES `squadre` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `punteggi`;
CREATE TABLE IF NOT EXISTS `punteggi` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `partita` INT NOT NULL,
  `home` VARCHAR(8) NOT NULL,
  `guest` VARCHAR(8) NOT NULL,

  FOREIGN KEY (`partita`) REFERENCES `partite` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `partecipaz_squad_torneo`;
CREATE TABLE IF NOT EXISTS `partecipaz_squad_torneo` (
  `torneo` INT NOT NULL,
  `squadra` INT NOT NULL,

  PRIMARY KEY (`torneo`,`squadra`),
  FOREIGN KEY (`squadra`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`torneo`) REFERENCES `tornei` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


--
-- Area Sistema
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE IF NOT EXISTS `utenti` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_name` VARCHAR(256) NOT NULL UNIQUE,
  `password` VARCHAR(256) NOT NULL,
  `is_admin` BOOLEAN NOT NULL DEFAULT FALSE,
  `is_blocked` BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `sessioni`;
CREATE TABLE IF NOT EXISTS `sessioni` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `user_flag` VARCHAR(64) NOT NULL,
  `device_ip` VARCHAR(32) DEFAULT NULL,
  `time_log` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `email`;
CREATE TABLE IF NOT EXISTS `email` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dest` VARCHAR(64) NOT NULL,
  `subject` VARCHAR(128) DEFAULT NULL,
  `body` TEXT,
  `sent` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opened` TIMESTAMP NULL DEFAULT NULL,
  `ricevuta` BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `token`;
CREATE TABLE IF NOT EXISTS `token` (
  `value` VARCHAR(64) PRIMARY KEY NOT NULL,
  `secret` VARCHAR(8) NOT NULL,
  `generation_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiration_date` DATETIME NOT NULL,
  `usage_date` DATETIME DEFAULT NULL,
  `user_id` INT NOT NULL,
  `email` VARCHAR(64) NOT NULL,
  `requesting_ip` VARCHAR(15) DEFAULT NULL,
  `requesting_browser` VARCHAR(128) DEFAULT NULL,
  
  FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Area Iscrizioni
--

DROP TABLE IF EXISTS `edizioni`;
CREATE TABLE IF NOT EXISTS `edizioni` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `anno` YEAR(4) NOT NULL UNIQUE,
  `motto` VARCHAR(256) DEFAULT NULL,
  `path_immagine` VARCHAR(256) DEFAULT NULL,
  `autore_logo` VARCHAR(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `edizioni` (`anno`) VALUES (YEAR(CURRENT_DATE));

DROP TABLE IF EXISTS `punteggio_parrocchia`;
CREATE TABLE IF NOT EXISTS `punteggio_parrocchia` (
  `parrocchia` INT NOT NULL,
  `edizione` INT NOT NULL,
  `punteggio` VARCHAR(8) NOT NULL DEFAULT '0',

  PRIMARY KEY (`parrocchia`, `edizione`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `iscritti`;
CREATE TABLE IF NOT EXISTS `iscritti` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dati_anagrafici` INT NOT NULL,
  `edizione` INT NOT NULL,
  `tutore` INT DEFAULT NULL,
  `certificato_medico` VARCHAR(2048) DEFAULT NULL,
  `parrocchia` INT NOT NULL,
  `taglia_maglietta` VARCHAR(8) NOT NULL,
  
  FOREIGN KEY (`dati_anagrafici`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`tutore`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `partecipaz_staff_ediz`;
CREATE TABLE IF NOT EXISTS `partecipaz_staff_ediz` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `staff` INT NOT NULL,
  `edizione` INT NOT NULL,
  `maglia` VARCHAR(3) DEFAULT NULL,
  `is_referente` BOOLEAN NOT NULL DEFAULT FALSE,

  FOREIGN KEY (`staff`) REFERENCES `staffisti` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `api_token`;
CREATE TABLE IF NOT EXISTS `api_token` (
  `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(256) NOT NULL,
  `key` VARCHAR(128) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `api_usage`;
CREATE TABLE IF NOT EXISTS `api_usage` (
  `token_id` INT NOT NULL,
  `ip_address` VARCHAR(15) NOT NULL,
  `last_usage` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hits` INT NOT NULL DEFAULT 0,

  PRIMARY KEY (`token_id`, `ip_address`),
  FOREIGN KEY (`token_id`) REFERENCES `api_token`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

DROP TABLE IF EXISTS `cron`;
CREATE TABLE IF NOT EXISTS `cron` (
  `name` VARCHAR(256) NOT NULL PRIMARY KEY,
  `function_name` VARCHAR(256) NOT NULL,
  `last_run` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `interval_hours` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

COMMIT;

/*!40101 SET foreign_key_checks = 1 */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
