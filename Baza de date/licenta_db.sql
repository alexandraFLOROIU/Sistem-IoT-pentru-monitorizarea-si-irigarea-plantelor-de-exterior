-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 08:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `licenta_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `automatic_settings`
--

CREATE TABLE `automatic_settings` (
  `id` int(11) NOT NULL,
  `watering_id` int(11) NOT NULL,
  `morning_time` char(5) DEFAULT NULL,
  `evening_time` char(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `date_senzori`
--

CREATE TABLE `date_senzori` (
  `id` int(11) NOT NULL,
  `id_sensor` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `value` float NOT NULL,
  `plant_name` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device`
--

CREATE TABLE `device` (
  `id` int(11) NOT NULL,
  `id_ESP` varchar(17) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id_feedback` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `periodic_settings`
--

CREATE TABLE `periodic_settings` (
  `id` int(11) NOT NULL,
  `watering_id` int(11) NOT NULL,
  `start_time` char(11) DEFAULT NULL,
  `stop_time` char(11) DEFAULT NULL,
  `time_hour` char(5) NOT NULL,
  `days_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pump_history`
--

CREATE TABLE `pump_history` (
  `id` int(11) NOT NULL,
  `watering_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensors`
--

CREATE TABLE `sensors` (
  `id_sensor` int(11) NOT NULL,
  `type_sensor` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_token` varchar(50) NOT NULL,
  `token_was_verified` tinyint(1) NOT NULL,
  `active_plant` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `watering_control`
--

CREATE TABLE `watering_control` (
  `id` int(11) NOT NULL,
  `watering_type` varchar(25) NOT NULL,
  `status_pump` tinyint(1) NOT NULL DEFAULT 0,
  `duration` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `plant_name` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `automatic_settings`
--
ALTER TABLE `automatic_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `watering_id` (`watering_id`);

--
-- Indexes for table `date_senzori`
--
ALTER TABLE `date_senzori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sensor` (`id_sensor`);

--
-- Indexes for table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_ESP` (`id_ESP`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id_feedback`);

--
-- Indexes for table `periodic_settings`
--
ALTER TABLE `periodic_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `watering_id` (`watering_id`);

--
-- Indexes for table `pump_history`
--
ALTER TABLE `pump_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `watering_id` (`watering_id`);

--
-- Indexes for table `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`id_sensor`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `watering_control`
--
ALTER TABLE `watering_control`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `automatic_settings`
--
ALTER TABLE `automatic_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `date_senzori`
--
ALTER TABLE `date_senzori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `device`
--
ALTER TABLE `device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id_feedback` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `periodic_settings`
--
ALTER TABLE `periodic_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pump_history`
--
ALTER TABLE `pump_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensors`
--
ALTER TABLE `sensors`
  MODIFY `id_sensor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `watering_control`
--
ALTER TABLE `watering_control`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `automatic_settings`
--
ALTER TABLE `automatic_settings`
  ADD CONSTRAINT `automatic_settings_ibfk_1` FOREIGN KEY (`watering_id`) REFERENCES `watering_control` (`id`);

--
-- Constraints for table `date_senzori`
--
ALTER TABLE `date_senzori`
  ADD CONSTRAINT `date_senzori_ibfk_1` FOREIGN KEY (`id_sensor`) REFERENCES `sensors` (`id_sensor`) ON DELETE CASCADE;

--
-- Constraints for table `device`
--
ALTER TABLE `device`
  ADD CONSTRAINT `device_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `periodic_settings`
--
ALTER TABLE `periodic_settings`
  ADD CONSTRAINT `periodic_settings_ibfk_1` FOREIGN KEY (`watering_id`) REFERENCES `watering_control` (`id`);

--
-- Constraints for table `pump_history`
--
ALTER TABLE `pump_history`
  ADD CONSTRAINT `pump_history_ibfk_1` FOREIGN KEY (`watering_id`) REFERENCES `watering_control` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
