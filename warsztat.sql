-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Wrz 11, 2026 at 10:31 AM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warsztat`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `czesci_naprawy`
--

CREATE TABLE `czesci_naprawy` (
  `id` int(11) NOT NULL,
  `zlecenie_id` int(11) NOT NULL,
  `nazwa` varchar(255) NOT NULL,
  `cena` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `historia_zmian`
--

CREATE TABLE `historia_zmian` (
  `id` int(11) NOT NULL,
  `zlecenie_id` int(11) NOT NULL,
  `uzytkownik` varchar(50) NOT NULL,
  `typ_zmiany` varchar(50) NOT NULL,
  `opis` text NOT NULL,
  `data_zmiany` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Dumping data for table `historia_zmian`
--

INSERT INTO `historia_zmian` (`id`, `zlecenie_id`, `uzytkownik`, `typ_zmiany`, `opis`, `data_zmiany`) VALUES
(1, 1, 'Alan', 'Utworzenie', 'Utworzono zlecenie ze statusem Przyjęto', '2026-09-11 09:34:29'),
(2, 2, 'Alan', 'Utworzenie', 'Utworzono zlecenie ze statusem Przyjęto', '2026-09-11 09:35:33');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `haslo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Dumping data for table `uzytkownicy`
--

INSERT INTO `uzytkownicy` (`id`, `login`, `haslo`) VALUES
(1, 'Alan', '$2y$10$JrhmkcGoaSBjDTjZtEV1i.4pCKcQnS9lrjEFx1B/vaFyBVEHp.Ife');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zlecenia`
--

CREATE TABLE `zlecenia` (
  `id` int(11) NOT NULL,
  `orderType` varchar(20) DEFAULT NULL,
  `clientName` varchar(100) DEFAULT NULL,
  `clientPhone` varchar(20) DEFAULT NULL,
  `clientEmail` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `purchasePrice` decimal(10,2) DEFAULT NULL,
  `additionalCost` decimal(10,2) DEFAULT NULL,
  `purchaseSource` varchar(100) DEFAULT NULL,
  `deviceCategory` varchar(100) DEFAULT NULL,
  `deviceBrandModel` varchar(255) DEFAULT NULL,
  `serialNumber` varchar(100) DEFAULT NULL,
  `accessories` varchar(255) DEFAULT NULL,
  `visualCondition` text DEFAULT NULL,
  `faultDescription` text DEFAULT NULL,
  `initialStatus` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admissionDate` date DEFAULT NULL,
  `repairNotes` text DEFAULT NULL,
  `lockCode` varchar(50) DEFAULT NULL,
  `salePrice` decimal(10,2) DEFAULT NULL,
  `saleDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Dumping data for table `zlecenia`
--

INSERT INTO `zlecenia` (`id`, `orderType`, `clientName`, `clientPhone`, `clientEmail`, `price`, `purchasePrice`, `additionalCost`, `purchaseSource`, `deviceCategory`, `deviceBrandModel`, `serialNumber`, `accessories`, `visualCondition`, `faultDescription`, `initialStatus`, `created_at`, `admissionDate`, `repairNotes`, `lockCode`, `salePrice`, `saleDate`) VALUES
(1, 'client', 'Jan Kowalski', '123456789', 'costam@interia.pl', 300.00, NULL, NULL, '', 'Laptop / Komputer', 'Macbook A1708', 'C02Mdsds', 'Zasilacz, etui', 'Brakuje jednej śrubki od dołu\r\n', 'Nie uruchamia się ', 'Przyjęto', '2026-09-11 07:34:29', '2026-09-11', NULL, 'Brak', NULL, NULL),
(2, 'own', '', '', '', NULL, 200.00, 20.99, 'OLX', 'Laptop / Komputer', 'Macbook A2179', 'C02Mdsds', 'Ładowarka, pudełko', 'Bardzo ładny', 'Nie wyświetla obrazu. Prawdopodobnie flexgate', 'Przyjęto', '2026-09-11 07:35:33', '2026-09-11', NULL, 'Brak', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zlecenie_testy`
--

CREATE TABLE `zlecenie_testy` (
  `id` int(11) NOT NULL,
  `zlecenie_id` int(11) NOT NULL,
  `klucz_testu` varchar(50) NOT NULL,
  `status` enum('nie_sprawdzono','ok','uszkodzone') DEFAULT 'nie_sprawdzono'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `czesci_naprawy`
--
ALTER TABLE `czesci_naprawy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zlecenie_id` (`zlecenie_id`);

--
-- Indeksy dla tabeli `historia_zmian`
--
ALTER TABLE `historia_zmian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zlecenie_id` (`zlecenie_id`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Indeksy dla tabeli `zlecenia`
--
ALTER TABLE `zlecenia`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `zlecenie_testy`
--
ALTER TABLE `zlecenie_testy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zlecenie_id` (`zlecenie_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `czesci_naprawy`
--
ALTER TABLE `czesci_naprawy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historia_zmian`
--
ALTER TABLE `historia_zmian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `zlecenia`
--
ALTER TABLE `zlecenia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `zlecenie_testy`
--
ALTER TABLE `zlecenie_testy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `czesci_naprawy`
--
ALTER TABLE `czesci_naprawy`
  ADD CONSTRAINT `czesci_naprawy_ibfk_1` FOREIGN KEY (`zlecenie_id`) REFERENCES `zlecenia` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `historia_zmian`
--
ALTER TABLE `historia_zmian`
  ADD CONSTRAINT `historia_zmian_ibfk_1` FOREIGN KEY (`zlecenie_id`) REFERENCES `zlecenia` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zlecenie_testy`
--
ALTER TABLE `zlecenie_testy`
  ADD CONSTRAINT `zlecenie_testy_ibfk_1` FOREIGN KEY (`zlecenie_id`) REFERENCES `zlecenia` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
