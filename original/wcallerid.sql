-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 24, 2024 at 10:28 AM
-- Server version: 5.5.68-MariaDB
-- PHP Version: 7.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `freecall`
--

-- --------------------------------------------------------

--
-- Table structure for table `wcallerid`
--

CREATE TABLE `wcallerid` (
  `id` int(11) NOT NULL,
  `callerid` varchar(20) NOT NULL,
  `SYear` varchar(50) NOT NULL,
  `SMonth` varchar(50) NOT NULL,
  `SDate` varchar(50) NOT NULL,
  `creationdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wcallerid`
--

INSERT INTO `wcallerid` (`id`, `callerid`, `SYear`, `SMonth`, `SDate`, `creationdate`) VALUES
(3, '7189547926', '2024', '06', '24', '2024-06-24 10:17:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wcallerid`
--
ALTER TABLE `wcallerid`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wcallerid`
--
ALTER TABLE `wcallerid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
