-- 1. Crearea structurii tabelului (fără user_id)
CREATE TABLE `coduri_interventie` (
  `cod_id` int(11) NOT NULL AUTO_INCREMENT,
  `nume` varchar(255) NOT NULL,
  `activ` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`cod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Popularea tabelului cu datele din imagine (fără user_id)
INSERT INTO `coduri_interventie` (`cod_id`, `nume`, `activ`) VALUES
(1, 'ASISTENTA MEDICALA DE URGENTA', 1),
(2, 'DESCARCERARE', 1),
(3, 'INCENDII', 1),
(4, 'INCENDII DE VEGETATIE', 1),
(5, 'ALTE SITUATII DE URGENTA', 1),
(6, 'ASISTENTA PERSOANE', 1),
(7, 'PROTECTIA COMUNITATILOR', 1),
(8, 'EXERCITII CU FORTE SI MIJLOACE IN TEREN', 1),
(9, 'RECUNOASTERE SI INSTRUIRE', 1);