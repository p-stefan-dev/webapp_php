-- SQL Dump for db_webapp - Recreare
-- Generation Time: Nov 30, 2025

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `db_webapp`
--
CREATE DATABASE IF NOT EXISTS `db_webapp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_webapp`;

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--
CREATE TABLE `grade` (
  `id_grad` int(11) NOT NULL AUTO_INCREMENT,
  `nume_grad` varchar(100) NOT NULL,
  `prescurt` varchar(20) NOT NULL,
  `categorie` varchar(50) NOT NULL,
  PRIMARY KEY (`id_grad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `structuri`
--
CREATE TABLE `structuri` (
  `id_struct` int(11) NOT NULL AUTO_INCREMENT,
  `Structura` varchar(255) NOT NULL,
  `prescurt` varchar(50) NOT NULL,
  PRIMARY KEY (`id_struct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `substructuri`
--
CREATE TABLE `substructuri` (
  `id_substr` int(11) NOT NULL AUTO_INCREMENT,
  `denumire` varchar(255) NOT NULL,
  `prescurt` varchar(50) DEFAULT NULL,
  `id_struct` int(11) NOT NULL,
  PRIMARY KEY (`id_substr`),
  KEY `fk_substructuri_structuri` (`id_struct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `personal`
--
CREATE TABLE `personal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_old` int(11) DEFAULT NULL,
  `nume` varchar(100) NOT NULL,
  `prenume` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `judet` varchar(100) DEFAULT NULL,
  `uat` varchar(100) DEFAULT NULL,
  `localitate` varchar(100) DEFAULT NULL,
  `tip_serviciu` enum('8ore','tura1','tura2','tura3') DEFAULT NULL,
  `id_grad` int(11) NOT NULL,
  `id_struct` int(11) NOT NULL,
  `id_substr` int(11) DEFAULT NULL,
  `clasa` enum('1','2','3') NOT NULL DEFAULT '1',
  `activ` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=Inactiv/Blocat, 1=Activ',
  `rol` tinyint(1) DEFAULT NULL COMMENT 'Roluri: 5=Neconfirmat, 4=Confirmat (Utilizator), ...',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email_unic` (`email`),
  KEY `idx_id_old` (`id_old`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_personal` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activation_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email_utilizator_unic` (`email`),
  KEY `fk_users_personal` (`id_personal`),
  KEY `idx_activation_token` (`activation_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `settings`
--
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `password_resets`
--
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
