CREATE TABLE `tehnica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `denumire` varchar(150) NOT NULL,
  `nr_inmatriculare` varchar(50) DEFAULT NULL,
  `codif_teh` varchar(50) DEFAULT NULL, -- Câmp nou (Text)
  `tip_autosp` int(11) DEFAULT NULL,    -- Câmp nou (Număr)
  `detalii` text DEFAULT NULL,          -- Câmp nou (Text)
  `id_struct` int(11) DEFAULT NULL,
  `id_substr` int(11) DEFAULT 0,
  `stare` varchar(50) DEFAULT 'operativ',
  `data_add` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;