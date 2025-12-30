-- 1. Ștergem tabelul vechi dacă există (ca să începem de la zero)
DROP TABLE IF EXISTS `personal`;

-- 2. Creăm tabelul nou cu structura cerută
CREATE TABLE `personal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_old` int(11) DEFAULT NULL,
  `nume` varchar(100) NOT NULL,
  `prenume` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) DEFAULT NULL COMMENT 'Utilizator pentru logare LDAP',
  `telefon` varchar(20) DEFAULT NULL,
  
  -- Date geografice (Numele lor, text)
  `judet` varchar(100) DEFAULT NULL,
  `uat` varchar(100) DEFAULT NULL,
  `localitate` varchar(100) DEFAULT NULL,
  
  `tip_serviciu` varchar(50) DEFAULT NULL,
  
  -- ID-uri numerice (Chei externe)
  `id_grad` int(11) DEFAULT NULL,
  `id_struct` int(11) DEFAULT NULL,
  `id_substr` int(11) DEFAULT 0 COMMENT '0 = Fara substructura',
  
  -- Alte campuri numerice
  `clasa` int(11) DEFAULT NULL,
  `activ` int(1) NOT NULL DEFAULT 1 COMMENT '1=Activ, 0=Inactiv',
  `rol` int(11) NOT NULL DEFAULT 4 COMMENT 'Default 4',
  `curs_smurd` int(11) DEFAULT 0,
  
  -- Timestamps
  `data_add` timestamp NOT NULL DEFAULT current_timestamp(),
  `editat` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),

  -- Setari chei si indexi
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`), -- Index pentru viteza la login LDAP
  KEY `idx_email` (`email`),
  KEY `idx_struct` (`id_struct`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;