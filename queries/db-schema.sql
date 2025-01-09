SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


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
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `label` varchar(64) DEFAULT NULL,
  `regex` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS  `anagrafiche`;
CREATE TABLE IF NOT EXISTS `anagrafiche` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(128) NOT NULL,
  `cognome` varchar(256) NOT NULL,
  `data_nascita` date NOT NULL,
  `luogo_nascita` varchar(256) NOT NULL,
  `telefono` varchar(16) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `codice_fiscale` varchar(64) NOT NULL,
  `tipo_documento` int(11) NOT NULL,
  `codice_documento` varchar(128) NOT NULL,
  `scadenza` date DEFAULT NULL,
  `documento` varchar(4096) NOT NULL,
  `self_generated` boolean NOT NULL DEFAULT '0',
  `creation_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_ts` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `codice_fiscale` (`codice_fiscale`) USING BTREE,
  FOREIGN KEY (`tipo_documento`) REFERENCES `tipi_documento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Area STAFF
--

DROP TABLE IF EXISTS `commissioni`;
CREATE TABLE IF NOT EXISTS `commissioni` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `parrocchie`;
CREATE TABLE IF NOT EXISTS `parrocchie` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(256) NOT NULL,
  `indirizzo` varchar(256) DEFAULT NULL,
  `website` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `staffisti`;
CREATE TABLE IF NOT EXISTS `staffisti` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dati_anagrafici` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `parrocchia` int(11) NOT NULL,

  FOREIGN KEY (`dati_anagrafici`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ruoli_staff`;
CREATE TABLE IF NOT EXISTS `ruoli_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commissione` int(11) NOT NULL,
  `staffista` int(11) NOT NULL,
  `edizione` int(11) NOT NULL,

  PRIMARY KEY (`id`),
  FOREIGN KEY (`commissione`) REFERENCES `commissioni` (`id`),
  FOREIGN KEY (`staffista`) REFERENCES `staffisti` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Area PARTITE
--

DROP TABLE IF EXISTS `sport`;
CREATE TABLE IF NOT EXISTS `sport` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(256) NOT NULL,
  `area` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `squadre`;
CREATE TABLE IF NOT EXISTS `squadre` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(128) NOT NULL,
  `parrocchia` int(11) NOT NULL,
  `sport` int(11) NOT NULL,
  `edizione` int(11) NOT NULL,

  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`sport`) REFERENCES `sport` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tipi_torneo`;
CREATE TABLE IF NOT EXISTS `tipi_torneo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tornei`;
CREATE TABLE IF NOT EXISTS `tornei` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `edizione` int(11) NOT NULL,
  `nome` varchar(128) NOT NULL,
  `sport` int(11) NOT NULL,
  `tipo_torneo` int(11) NOT NULL,
  
  FOREIGN KEY (`sport`) REFERENCES `sport` (`id`),
  FOREIGN KEY (`tipo_torneo`) REFERENCES `tipi_torneo` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `squadre_iscritti`;
CREATE TABLE IF NOT EXISTS `squadre_iscritti` (
  `squadra` int(11) NOT NULL,
  `iscritto` int(11) NOT NULL,

  PRIMARY KEY (`squadra`,`iscritto`),
  FOREIGN KEY (`squadra`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`iscritto`) REFERENCES `iscritti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `campi`;
CREATE TABLE IF NOT EXISTS `campi` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nome` varchar(128) NOT NULL,
  `indirizzo` varchar(256) NOT NULL,
  `posizione` point DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `partite`;
CREATE TABLE IF NOT EXISTS `partite` (
  `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `torneo` int(11) NOT NULL,
  `data` date DEFAULT NULL,
  `orario` time DEFAULT NULL,
  `campo` int(11) DEFAULT NULL,
  `squadra_casa` int(11) NOT NULL,
  `squadra_ospite` int(11) NOT NULL,
  `a_tavolino` int(11) DEFAULT NULL,
  
  FOREIGN KEY (`campo`) REFERENCES `campi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`squadra_casa`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`squadra_ospite`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`torneo`) REFERENCES `tornei` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`a_tavolino`) REFERENCES `squadre` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `punteggi`;
CREATE TABLE IF NOT EXISTS `punteggi` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `partita` int(11) NOT NULL,
  `home` varchar(8) NOT NULL,
  `guest` varchar(8) NOT NULL,

  FOREIGN KEY (`partita`) REFERENCES `partite` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `partecipaz_squad_torneo`;
CREATE TABLE IF NOT EXISTS `partecipaz_squad_torneo` (
  `torneo` int(11) NOT NULL,
  `squadra` int(11) NOT NULL,

  PRIMARY KEY (`torneo`,`squadra`),
  FOREIGN KEY (`squadra`) REFERENCES `squadre` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`torneo`) REFERENCES `tornei` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Area Sistema
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE IF NOT EXISTS `utenti` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_name` varchar(256) NOT NULL,
  `password` varchar(256) NOT NULL,
  `is_admin` boolean NOT NULL DEFAULT '0',
  `is_blocked` boolean NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessioni`;
CREATE TABLE IF NOT EXISTS `sessioni` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_flag` varchar(64) NOT NULL,
  `device_ip` varchar(32) DEFAULT NULL,
  `time_log` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `email`;
CREATE TABLE IF NOT EXISTS `email` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dest` varchar(64) NOT NULL,
  `subject` varchar(128) DEFAULT NULL,
  `body` text,
  `sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opened` timestamp NULL DEFAULT NULL,
  `ricevuta` BOOLEAN NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `messaggi`;
CREATE TABLE IF NOT EXISTS `messaggi` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `testo` text NOT NULL,
  `autore` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `autore` (`autore`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `token`;
CREATE TABLE IF NOT EXISTS `token` (
  `val` varchar(128) PRIMARY KEY NOT NULL,
  `secret` varchar(128) NOT NULL,
  `edizione` int(11) NOT NULL,
  `anagrafica` int(11) NOT NULL,
  `expire` datetime NOT NULL,
  `used_date` datetime DEFAULT NULL,
  `generated_ts` datetime DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`anagrafica`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Area Iscrizioni
--

DROP TABLE IF EXISTS `edizioni`;
CREATE TABLE IF NOT EXISTS `edizioni` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `anno` year(4) NOT NULL,
  `inizio_iscrizioni` date DEFAULT NULL,
  `fine_iscrizioni` date DEFAULT NULL,
  `motto` varchar(256) DEFAULT NULL,
  `path_immagine` varchar(256) NOT NULL,
  `autore_logo` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `punteggio_parrocchia`;
CREATE TABLE IF NOT EXISTS `punteggio_parrocchia` (
  `parrocchia` int(11) NOT NULL,
  `edizione` int(11) NOT NULL,
  `punteggio` varchar(8) NOT NULL DEFAULT '0',

  PRIMARY KEY (`parrocchia`,`edizione`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `iscritti`;
CREATE TABLE IF NOT EXISTS `iscritti` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `dati_anagrafici` int(11) NOT NULL,
  `edizione` int(11) NOT NULL,
  `tutore` int(11) DEFAULT NULL,
  `certificato_medico` varchar(2048) DEFAULT NULL,
  `parrocchia` int(11) NOT NULL,
  `taglia_maglietta` varchar(8) NOT NULL,
  
  FOREIGN KEY (`dati_anagrafici`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`tutore`) REFERENCES `anagrafiche` (`id`),
  FOREIGN KEY (`parrocchia`) REFERENCES `parrocchie` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `partecipaz_staff_ediz`;
CREATE TABLE IF NOT EXISTS `partecipaz_staff_ediz` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `staff` int(11) NOT NULL,
  `edizione` int(11) NOT NULL,
  `maglia` varchar(3) DEFAULT NULL,
  `is_referente` boolean NOT NULL DEFAULT '0',

  FOREIGN KEY (`staff`) REFERENCES `staffisti` (`id`),
  FOREIGN KEY (`edizione`) REFERENCES `edizioni` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


COMMIT;

/*!40101 SET foreign_key_checks = 1 */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
