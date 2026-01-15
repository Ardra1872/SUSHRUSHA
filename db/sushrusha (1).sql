-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 08:54 AM
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
-- Database: `sushrusha`
--

-- --------------------------------------------------------

--
-- Table structure for table `broadcasts`
--

CREATE TABLE `broadcasts` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `broadcasts`
--

INSERT INTO `broadcasts` (`id`, `message`, `created_by`, `created_at`) VALUES
(1, 'Added BLH BLH medicine', 37, '2026-01-11 10:19:17'),
(2, 'hehe', 37, '2026-01-11 11:03:31'),
(3, 'Added New Medicine', 37, '2026-01-12 08:38:25'),
(5, 'Meesage', 37, '2026-01-13 04:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `broadcast_reads`
--

CREATE TABLE `broadcast_reads` (
  `id` int(11) NOT NULL,
  `broadcast_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `caregivers`
--

CREATE TABLE `caregivers` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `caregiver_id` int(11) NOT NULL,
  `relation` varchar(50) DEFAULT NULL,
  `notifications_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `caregivers`
--

INSERT INTO `caregivers` (`id`, `patient_id`, `caregiver_id`, `relation`, `notifications_enabled`) VALUES
(24, 10, 34, 'Friend', 1);

-- --------------------------------------------------------

--
-- Table structure for table `dose_logs`
--

CREATE TABLE `dose_logs` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `intake_datetime` datetime NOT NULL,
  `status` enum('Taken','Missed') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_details`
--

CREATE TABLE `medical_details` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `doctor_name` varchar(100) DEFAULT NULL,
  `hospital_name` varchar(100) DEFAULT NULL,
  `prescription_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `medicine_type` enum('pill','liquid','injection') NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `dosage_value` varchar(20) DEFAULT NULL,
  `dosage_unit` varchar(20) DEFAULT NULL,
  `compartment_number` int(11) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `form` enum('Pill','Liquid','Injection') NOT NULL DEFAULT 'Pill',
  `frequency` enum('Daily','Weekly','Specific','As Needed') NOT NULL DEFAULT 'Daily',
  `specific_days` set('Mon','Tue','Wed','Thu','Fri','Sat','Sun') DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `schedule_type` enum('daily','weekly','custom') NOT NULL DEFAULT 'daily',
  `days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`days`)),
  `reminder_type` enum('fixed','interval') NOT NULL DEFAULT 'fixed',
  `interval_hours` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `patient_id`, `name`, `medicine_type`, `dosage`, `dosage_value`, `dosage_unit`, `compartment_number`, `start_date`, `end_date`, `form`, `frequency`, `specific_days`, `instructions`, `schedule_type`, `days`, `reminder_type`, `interval_hours`) VALUES
(18, 10, 'Paracetamol', 'pill', NULL, '500mg', '', 2, '2026-01-09', '2026-01-09', 'Pill', 'Daily', NULL, NULL, 'daily', NULL, 'interval', 8),
(23, 10, 'Cough Syrup', 'liquid', NULL, '10ml', '', 3, '2026-01-09', '2026-01-11', 'Pill', 'Daily', NULL, NULL, 'daily', '[\"W\",\"T\"]', 'fixed', 8);

-- --------------------------------------------------------

--
-- Table structure for table `medicine_catalog`
--

CREATE TABLE `medicine_catalog` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `form` enum('Pill','Liquid','Injection') DEFAULT 'Pill'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_catalog`
--

INSERT INTO `medicine_catalog` (`id`, `name`, `dosage`, `form`) VALUES
(1, 'Paracetamol', '500mg', 'Pill'),
(2, 'Paracetamol', '650mg', 'Pill'),
(3, 'Ibuprofen', '200mg', 'Pill'),
(4, 'Ibuprofen', '400mg', 'Pill'),
(5, 'Amoxicillin', '250mg', 'Pill'),
(6, 'Amoxicillin', '500mg', 'Pill'),
(7, 'Vitamin C', '1000mg', 'Pill'),
(8, 'Cough Syrup', '10ml', 'Liquid'),
(9, 'Insulin', '1 units', 'Injection'),
(10, 'BLH BLH', '500mg', 'Liquid'),
(11, 'Dolo', '500mg', 'Pill'),
(12, 'bruhh', '10mg', 'Injection');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_requests`
--

CREATE TABLE `medicine_requests` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `form` enum('Pill','Liquid','Injection') NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_requests`
--

INSERT INTO `medicine_requests` (`id`, `name`, `dosage`, `form`, `requested_by`, `status`, `created_at`) VALUES
(1, 'Citrizine', '10mg', 'Pill', 10, 'approved', '2026-01-10 15:33:24'),
(8, 'Dolo', '500mg', 'Pill', 10, 'approved', '2026-01-12 14:34:43');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_schedule`
--

CREATE TABLE `medicine_schedule` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `intake_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_schedule`
--

INSERT INTO `medicine_schedule` (`id`, `medicine_id`, `intake_time`) VALUES
(28, 23, '08:00:00'),
(29, 23, '20:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `patient_profile`
--

CREATE TABLE `patient_profile` (
  `patient_id` int(11) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `height_cm` int(11) DEFAULT NULL,
  `weight_kg` int(11) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_profile`
--

INSERT INTO `patient_profile` (`patient_id`, `dob`, `gender`, `blood_group`, `height_cm`, `weight_kg`, `profile_photo`) VALUES
(10, '2003-01-11', 'Female', 'O+', 169, 40, 'uploads/profile/profile_10.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('patient','caretaker','admin') NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `emergency_contact` varchar(15) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `first_login` tinyint(1) DEFAULT 0,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `role`, `email`, `password`, `contact_number`, `emergency_contact`, `patient_id`, `reset_code`, `reset_expiry`, `first_login`, `profile_photo`) VALUES
(10, 'Ardra S Nair', 'patient', 'ardrasnair2028@mca.ajce.in', '$2y$10$A7QM3Spx2koMniPFnCMY.eIqbSgUD/Lu/junI7oyiaUB4ckzOs2wC', NULL, '9656221258', NULL, NULL, NULL, 0, NULL),
(34, 'Ardra S Nair', 'caretaker', 'ardrasnaircpdy@gmail.com', '$2y$10$mOKViYczwdtODjO0.Ap0O.qdxgpdJyO.brihhFvqcV6iKsqYzeZTC', NULL, NULL, 10, NULL, NULL, 1, NULL),
(37, 'Meenu', 'admin', 'meenucpdy020@gmail.com', '$2y$10$Gt5aTC/22UsQ4JfaKc3t0..55CnJfQnKRfJd0db1LyUQoaVqy1Y2C', NULL, NULL, NULL, NULL, NULL, 0, 'profile_37_1768131509.jpg'),
(39, 'Divinia', 'patient', 'diviniamarioantony2028@mca.ajce.in', '$2y$10$dYG/tpygRpPYGPNIuUEDWugZzbMEHFIvL2R/iTTS.wMuGdg.18rOe', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(40, 'Anupriya', 'patient', 'anupriyaa2028@mca.ajce.in', '$2y$10$VhS5279F4.2OEFA7B61N.OIO/SCwoLZ4urQjoYU5wSm6TF2.zMM9G', NULL, NULL, NULL, NULL, NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `broadcasts`
--
ALTER TABLE `broadcasts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `broadcast_reads`
--
ALTER TABLE `broadcast_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`broadcast_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `caregivers`
--
ALTER TABLE `caregivers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `caregiver_id` (`caregiver_id`);

--
-- Indexes for table `dose_logs`
--
ALTER TABLE `dose_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medical_details`
--
ALTER TABLE `medical_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medicine_catalog`
--
ALTER TABLE `medicine_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`,`dosage`);

--
-- Indexes for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `medicine_schedule`
--
ALTER TABLE `medicine_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `patient_profile`
--
ALTER TABLE `patient_profile`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `patient_id` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `broadcasts`
--
ALTER TABLE `broadcasts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `broadcast_reads`
--
ALTER TABLE `broadcast_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caregivers`
--
ALTER TABLE `caregivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `dose_logs`
--
ALTER TABLE `dose_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_details`
--
ALTER TABLE `medical_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `medicine_catalog`
--
ALTER TABLE `medicine_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medicine_schedule`
--
ALTER TABLE `medicine_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `broadcasts`
--
ALTER TABLE `broadcasts`
  ADD CONSTRAINT `broadcasts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `broadcast_reads`
--
ALTER TABLE `broadcast_reads`
  ADD CONSTRAINT `broadcast_reads_ibfk_1` FOREIGN KEY (`broadcast_id`) REFERENCES `broadcasts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `broadcast_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `caregivers`
--
ALTER TABLE `caregivers`
  ADD CONSTRAINT `caregivers_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `caregivers_ibfk_2` FOREIGN KEY (`caregiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dose_logs`
--
ALTER TABLE `dose_logs`
  ADD CONSTRAINT `dose_logs_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dose_logs_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_details`
--
ALTER TABLE `medical_details`
  ADD CONSTRAINT `medical_details_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  ADD CONSTRAINT `medicine_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_schedule`
--
ALTER TABLE `medicine_schedule`
  ADD CONSTRAINT `medicine_schedule_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_profile`
--
ALTER TABLE `patient_profile`
  ADD CONSTRAINT `patient_profile_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

-- Create notes table for caretaker notes
CREATE TABLE IF NOT EXISTS `caretaker_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `caretaker_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `note_type` enum('general','medicine') NOT NULL DEFAULT 'general',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `caretaker_id` (`caretaker_id`),
  KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `caretaker_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caretaker_notes_ibfk_2` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caretaker_notes_ibfk_3` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
