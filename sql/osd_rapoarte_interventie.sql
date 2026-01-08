CREATE TABLE IF NOT EXISTS `osd_rapoarte_interventie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `sectiune_id` int(11) NOT NULL, -- Nr. Secțiunii (2-10)
  `id_struct` int(11) NOT NULL,   -- Structura responsabilă (ex: Detașament 1)
  `json_date` JSON DEFAULT NULL,  -- Aici salvăm datele tuturor substructurilor
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_raport` (`data`, `sectiune_id`) -- O singură înregistrare per secțiune pe zi
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;