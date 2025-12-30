-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gazdă: 127.0.0.1
-- Timp de generare: dec. 28, 2025 la 09:51 PM
-- Versiune server: 10.4.32-MariaDB
-- Versiune PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Bază de date: `db_webapp`
--

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `grade`
--

CREATE TABLE `grade` (
  `id_grad` int(11) NOT NULL,
  `nume_grad` varchar(100) NOT NULL,
  `prescurt` varchar(20) NOT NULL,
  `categorie` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `grade`
--

INSERT INTO `grade` (`id_grad`, `nume_grad`, `prescurt`, `categorie`) VALUES
(1, 'General', 'Gl.', 'general'),
(2, 'General-locotenent', 'Gl.Lt.', 'general'),
(3, 'General-maior', 'Gl.Mr.', 'general'),
(4, 'General de brigada', 'Gl.bg.', 'general'),
(5, 'Colonel', 'Col.', 'ofiter'),
(6, 'Locotenent-colonel', 'Lt.col.', 'ofiter'),
(7, 'Maior', 'Mr.', 'ofiter'),
(8, 'Capitan', 'Cpt.', 'ofiter'),
(9, 'Locotenent', 'Lt.', 'ofiter'),
(10, 'Sublocotenent', 'Slt.', 'ofiter'),
(11, 'Maistru militar principal', 'M.m.p', 'maistru'),
(12, 'Maistru militar clasa I', 'M.m.I', 'maistru'),
(13, 'Maistru militar clasa II', 'M.m.II', 'maistru'),
(14, 'Maistru militar clasa III', 'M.m.III', 'maistru'),
(15, 'Maistru militar clasa IV', 'M.m.IV', 'maistru'),
(16, 'Maistru militar clasa V', 'M.m.V', 'maistru'),
(17, 'Plutonier adjutant sef', 'Plt.adj.sef', 'subofiter'),
(18, 'Plutonier adjutant', 'Plt.adj.', 'subofiter'),
(19, 'Plutonier major', 'Plt.maj.', 'subofiter'),
(20, 'Plutonier', 'Plt.', 'subofiter'),
(21, 'Sergent major', 'Sg.maj.', 'subofiter'),
(22, 'Sergent', 'Srg.', 'gradat'),
(23, 'Caporal', 'Cap.', 'gradat'),
(24, 'Fruntas', 'Frt.', 'gradat'),
(25, 'Soldat', 'Sold.', 'soldat'),
(26, 'Personal contractual', 'P.C.', 'civil'),
(27, 'Alta categorie', 'A.C.', 'alta');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `judete`
--

CREATE TABLE `judete` (
  `id` int(11) NOT NULL,
  `nume_judet` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `judete`
--

INSERT INTO `judete` (`id`, `nume_judet`) VALUES
(1, 'Alba'),
(2, 'Arad'),
(3, 'Argeș'),
(4, 'Bacău'),
(5, 'Bihor'),
(6, 'Bistrița-Năsăud'),
(7, 'Botoșani'),
(8, 'Brașov'),
(9, 'Brăila'),
(10, 'Buzău'),
(11, 'Caraș-Severin'),
(12, 'Călărași'),
(13, 'Cluj'),
(14, 'Constanța'),
(15, 'Covasna'),
(16, 'Dâmbovița'),
(17, 'Dolj'),
(18, 'Galați'),
(19, 'Giurgiu'),
(20, 'Gorj'),
(21, 'Harghita'),
(22, 'Hunedoara'),
(23, 'Ialomița'),
(24, 'Iași'),
(25, 'Ilfov'),
(26, 'Maramureș'),
(27, 'Mehedinți'),
(28, 'Mureș'),
(29, 'Neamț'),
(30, 'Olt'),
(31, 'Prahova'),
(32, 'Satu Mare'),
(33, 'Sălaj'),
(34, 'Sibiu'),
(35, 'Suceava'),
(36, 'Teleorman'),
(37, 'Timiș'),
(38, 'Tulcea'),
(39, 'Vaslui'),
(40, 'Vâlcea'),
(41, 'Vrancea'),
(42, 'București');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `localitati`
--

CREATE TABLE `localitati` (
  `id` int(11) NOT NULL,
  `id_uat` int(11) NOT NULL,
  `nume_localitate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `localitati`
--

INSERT INTO `localitati` (`id`, `id_uat`, `nume_localitate`) VALUES
(1, 1, 'Arad'),
(2, 2, 'Chișineu-Criș'),
(3, 3, 'Curtici'),
(4, 4, 'Ineu'),
(5, 5, 'Lipova'),
(6, 6, 'Nădlac'),
(7, 7, 'Pâncota'),
(8, 8, 'Pecica'),
(9, 9, 'Sântana'),
(10, 10, 'Sebiș'),
(11, 11, 'Almaș'),
(12, 11, 'Cil'),
(13, 11, 'Joia Mare'),
(14, 11, 'Rădești'),
(15, 12, 'Apateu'),
(16, 12, 'Berechiu'),
(17, 12, 'Moțiori'),
(18, 13, 'Archiș'),
(19, 13, 'Bârzești'),
(20, 13, 'Groșeni'),
(21, 13, 'Nermiș');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `personal`
--

CREATE TABLE `personal` (
  `id` int(11) NOT NULL,
  `id_old` int(11) DEFAULT NULL,
  `nume` varchar(100) NOT NULL,
  `prenume` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `judet` varchar(100) DEFAULT NULL,
  `uat` varchar(100) DEFAULT NULL,
  `localitate` varchar(100) DEFAULT NULL,
  `tip_serviciu` enum('8ore','tura1','tura2','tura3','paza') DEFAULT NULL,
  `id_grad` int(11) NOT NULL,
  `id_struct` int(11) NOT NULL,
  `id_substr` int(11) DEFAULT NULL,
  `clasa` enum('1','2','3') NOT NULL DEFAULT '1',
  `activ` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=Inactiv/Blocat, 1=Activ',
  `rol` tinyint(1) DEFAULT NULL COMMENT 'Roluri: 5=Neconfirmat, 4=Confirmat (Utilizator), ...'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `personal`
--

INSERT INTO `personal` (`id`, `id_old`, `nume`, `prenume`, `email`, `username`, `telefon`, `judet`, `uat`, `localitate`, `tip_serviciu`, `id_grad`, `id_struct`, `id_substr`, `clasa`, `activ`, `rol`) VALUES
(1, NULL, 'Peli', 'Stefan', 'stefan.peli@gmail.com', 'stefan.peli', '', 'Arad', 'Chișineu-Criș', 'Chișineu-Criș', '8ore', 7, 2, NULL, '2', 1, 1);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `structuri`
--

CREATE TABLE `structuri` (
  `id_struct` int(11) NOT NULL,
  `Structura` varchar(255) NOT NULL,
  `prescurt` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `structuri`
--

INSERT INTO `structuri` (`id_struct`, `Structura`, `prescurt`) VALUES
(1, 'Comanda', 'Cmd'),
(2, 'Centrul operational', 'COJ'),
(3, 'Inspectia de prevenire', 'IP'),
(4, 'Serviciul pregatire pentru interventie si rezilienta comunitatilor', 'SPIRC'),
(5, 'Compartiment management stari exceptionale', 'C-MSE'),
(6, 'Structura de securitate', 'SS'),
(7, 'Serviciul comunicatii si tehnologia informatiei', 'SCTI'),
(8, 'Serviciul logistic', 'SL'),
(9, 'Serviciul financiar', 'SF'),
(10, 'Serviciul resurse umane', 'SRU'),
(11, 'Compartiment control', 'C-C'),
(12, 'Compartiment juridic', 'C-J'),
(13, 'Compartiment secretariat, documente clasificate si arhiva', 'C-SDCA'),
(14, 'Compartiment informare si relatii publice', 'C-IRP'),
(15, 'Compartiment psihologie', 'C-P'),
(16, 'Detasamentul de pompieri Arad', 'GI Arad'),
(17, 'Detasamentul de pompieri Sebis - Garda 1 Sebis', 'GI 1 Sebis'),
(18, 'Detasamentul de pompieri Sebis - Garda 2 Gurahont', 'GI 2 Gurahont'),
(19, 'Detasamentul de pompieri Ineu - Garda 1 Ineu', 'GI 1 Ineu'),
(20, 'Detasamentul de pompieri Ineu - Garda 2 Chisineu-Cris', 'GI 2 Ch-Cris'),
(21, 'Sectia de pompieri Barzava - Garda 1 Barzava', 'GI 1 Barzava'),
(22, 'Sectia de pompieri Barzava - Garda 2 Birchis', 'GI 2 Birchis'),
(23, 'Achizitii publice', 'AP');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `substructuri`
--

CREATE TABLE `substructuri` (
  `id_substr` int(11) NOT NULL,
  `denumire` varchar(255) NOT NULL,
  `prescurt` varchar(50) DEFAULT NULL,
  `id_struct` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `uat`
--

CREATE TABLE `uat` (
  `id` int(11) NOT NULL,
  `id_judet` int(11) NOT NULL,
  `nume_uat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Eliminarea datelor din tabel `uat`
--

INSERT INTO `uat` (`id`, `id_judet`, `nume_uat`) VALUES
(1, 2, 'Arad'),
(2, 2, 'Chișineu-Criș'),
(3, 2, 'Curtici'),
(4, 2, 'Ineu'),
(5, 2, 'Lipova'),
(6, 2, 'Nădlac'),
(7, 2, 'Pâncota'),
(8, 2, 'Pecica'),
(9, 2, 'Sântana'),
(10, 2, 'Sebiș'),
(11, 2, 'Almaș'),
(12, 2, 'Apateu'),
(13, 2, 'Archiș'),
(14, 2, 'Bata'),
(15, 2, 'Bârsa'),
(16, 2, 'Bârzava'),
(17, 2, 'Beliu'),
(18, 2, 'Birchiș'),
(19, 2, 'Bocsig'),
(20, 2, 'Brazii'),
(21, 2, 'Buteni'),
(22, 2, 'Cărand'),
(23, 2, 'Cermei'),
(24, 2, 'Chisindia'),
(25, 2, 'Conop'),
(26, 2, 'Covăsânț'),
(27, 2, 'Craiva'),
(28, 2, 'Dezna'),
(29, 2, 'Dieci'),
(30, 2, 'Dorobanți'),
(31, 2, 'Fântânele'),
(32, 2, 'Felnac'),
(33, 2, 'Ghioroc'),
(34, 2, 'Grăniceri'),
(35, 2, 'Gurahonț'),
(36, 2, 'Hălmagiu'),
(37, 2, 'Hălmăgel'),
(38, 2, 'Hășmaș'),
(39, 2, 'Ignești'),
(40, 2, 'Iratoșu'),
(41, 2, 'Livada'),
(42, 2, 'Macea'),
(43, 2, 'Mișca'),
(44, 2, 'Moneasa'),
(45, 2, 'Olari'),
(46, 2, 'Păuliș'),
(47, 2, 'Peregu Mare'),
(48, 2, 'Petriș'),
(49, 2, 'Pil'),
(50, 2, 'Pleșcuța'),
(51, 2, 'Săvârșin'),
(52, 2, 'Secusigiu'),
(53, 2, 'Seleuș'),
(54, 2, 'Semlac'),
(55, 2, 'Sintea Mare'),
(56, 2, 'Socodor'),
(57, 2, 'Șagu'),
(58, 2, 'Șeitin'),
(59, 2, 'Șepreuș'),
(60, 2, 'Șicula'),
(61, 2, 'Șilindia'),
(62, 2, 'Șimand'),
(63, 2, 'Șiria'),
(64, 2, 'Tauț'),
(65, 2, 'Târnova'),
(66, 2, 'Ususău'),
(67, 2, 'Vărădia de Mureș'),
(68, 2, 'Vinga'),
(69, 2, 'Vladimirescu'),
(70, 2, 'Zăbrani'),
(71, 2, 'Zădăreni'),
(72, 2, 'Zărand'),
(73, 2, 'Zerind');

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `grade`
--
ALTER TABLE `grade`
  ADD PRIMARY KEY (`id_grad`);

--
-- Indexuri pentru tabele `judete`
--
ALTER TABLE `judete`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `localitati`
--
ALTER TABLE `localitati`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_uat` (`id_uat`);

--
-- Indexuri pentru tabele `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_email_unic` (`email`),
  ADD KEY `idx_id_old` (`id_old`);

--
-- Indexuri pentru tabele `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexuri pentru tabele `structuri`
--
ALTER TABLE `structuri`
  ADD PRIMARY KEY (`id_struct`);

--
-- Indexuri pentru tabele `substructuri`
--
ALTER TABLE `substructuri`
  ADD PRIMARY KEY (`id_substr`),
  ADD KEY `fk_substructuri_structuri` (`id_struct`);

--
-- Indexuri pentru tabele `uat`
--
ALTER TABLE `uat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_judet` (`id_judet`);

--
-- AUTO_INCREMENT pentru tabele eliminate
--

--
-- AUTO_INCREMENT pentru tabele `grade`
--
ALTER TABLE `grade`
  MODIFY `id_grad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pentru tabele `judete`
--
ALTER TABLE `judete`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pentru tabele `localitati`
--
ALTER TABLE `localitati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pentru tabele `personal`
--
ALTER TABLE `personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pentru tabele `structuri`
--
ALTER TABLE `structuri`
  MODIFY `id_struct` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pentru tabele `substructuri`
--
ALTER TABLE `substructuri`
  MODIFY `id_substr` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `uat`
--
ALTER TABLE `uat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- Constrângeri pentru tabele eliminate
--

--
-- Constrângeri pentru tabele `localitati`
--
ALTER TABLE `localitati`
  ADD CONSTRAINT `localitati_ibfk_1` FOREIGN KEY (`id_uat`) REFERENCES `uat` (`id`);

--
-- Constrângeri pentru tabele `uat`
--
ALTER TABLE `uat`
  ADD CONSTRAINT `uat_ibfk_1` FOREIGN KEY (`id_judet`) REFERENCES `judete` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
