-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2026 at 04:21 PM
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
(5, 'Meesage', 37, '2026-01-13 04:15:05'),
(7, 'Added Cetrizine to the Catalogue', 37, '2026-01-29 16:30:02');

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
(27, 10, 45, 'Sister', 1);

-- --------------------------------------------------------

--
-- Table structure for table `caretaker_notes`
--

CREATE TABLE `caretaker_notes` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `caretaker_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `note_type` enum('general','medicine') NOT NULL DEFAULT 'general',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dose_logs`
--

CREATE TABLE `dose_logs` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `status` enum('TAKEN','MISSED') NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `simulated_action_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dose_logs`
--

INSERT INTO `dose_logs` (`id`, `schedule_id`, `status`, `log_time`, `simulated_action_time`) VALUES
(1, 57, 'TAKEN', '2026-01-25 15:11:54', '2026-01-25 20:41:54'),
(2, 58, 'TAKEN', '2026-01-25 15:14:08', '2026-01-25 20:44:08'),
(3, 59, 'TAKEN', '2026-01-28 14:08:34', '2026-01-28 19:38:34'),
(4, 61, 'TAKEN', '2026-01-28 14:23:38', '2026-01-28 19:53:38'),
(5, 62, 'TAKEN', '2026-01-28 14:23:43', '2026-01-28 19:53:43'),
(6, 63, 'TAKEN', '2026-01-29 08:00:26', '2026-01-29 13:30:26'),
(7, 66, 'TAKEN', '2026-01-29 14:30:16', '2026-01-29 20:00:16'),
(8, 68, 'TAKEN', '2026-01-29 14:30:34', '2026-01-29 20:00:34'),
(9, 70, 'MISSED', '2026-01-29 14:35:01', '2026-01-29 20:05:01'),
(10, 72, 'TAKEN', '2026-01-29 15:08:06', NULL),
(11, 74, 'TAKEN', '2026-01-29 15:10:06', '2026-01-29 20:40:06'),
(12, 76, 'MISSED', '2026-01-29 15:14:12', NULL),
(13, 83, 'TAKEN', '2026-01-29 16:57:51', NULL),
(14, 85, 'TAKEN', '2026-01-29 17:01:16', '2026-01-29 22:31:16'),
(15, 86, 'MISSED', '2026-01-29 17:08:01', '2026-01-29 22:38:01'),
(16, 87, 'TAKEN', '2026-01-30 06:49:58', '2026-01-30 12:19:58'),
(17, 95, 'MISSED', '2026-01-30 07:01:16', NULL),
(18, 94, 'TAKEN', '2026-01-30 07:01:56', '2026-01-30 12:31:56'),
(19, 95, 'MISSED', '2026-01-31 05:38:44', NULL),
(20, 93, 'MISSED', '2026-01-31 05:44:43', NULL),
(21, 92, 'MISSED', '2026-01-31 05:48:14', NULL),
(22, 94, 'TAKEN', '2026-01-31 05:53:24', NULL),
(23, 96, 'MISSED', '2026-01-31 06:38:14', '2026-01-31 12:08:14'),
(24, 97, 'TAKEN', '2026-01-31 06:38:22', '2026-01-31 12:08:22');

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
(30, 42, 'Dolo', 'pill', NULL, '500mg', '', 2, '2026-01-24', '2026-01-25', 'Pill', 'Daily', NULL, NULL, 'daily', '[\"S\",\"S\"]', 'fixed', 8),
(31, 42, 'Insulin', 'injection', NULL, '1 units', '', 3, '2026-01-24', '2026-01-25', 'Pill', 'Daily', NULL, NULL, 'daily', '[\"M\",\"T\"]', 'fixed', 8),
(32, 42, 'Paracetamol', 'pill', NULL, '650mg', '', 4, '2026-01-24', '2026-01-24', 'Pill', 'Daily', NULL, NULL, 'weekly', '[\"W\"]', 'fixed', 8),
(52, 10, 'Dolo', 'pill', NULL, '500mg', '', 3, '2026-01-31', '0000-00-00', 'Pill', 'Daily', NULL, NULL, 'weekly', NULL, 'fixed', 8),
(54, 10, 'Ibuprofen', 'pill', NULL, '200mg', '', 2, '2026-01-31', '2026-02-01', 'Pill', 'Daily', NULL, NULL, 'daily', NULL, 'fixed', 8);

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
(12, 'bruhh', '10mg', 'Injection'),
(13, 'Cetrizine', '10mg', 'Pill');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(44, 30, '08:00:00'),
(45, 30, '20:00:00'),
(46, 31, '08:00:00'),
(47, 31, '12:10:00'),
(48, 32, '08:00:00'),
(49, 32, '12:18:00'),
(50, 32, '12:18:00'),
(92, 52, '12:31:00'),
(93, 52, '20:00:00'),
(96, 54, '12:03:00'),
(97, 54, '12:08:00');

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
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('success','failed') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `prescription_date` date NOT NULL,
  `disease_name` varchar(255) NOT NULL,
  `disease_description` text DEFAULT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `hospital_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_medicines`
--

CREATE TABLE `prescription_medicines` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_tests`
--

CREATE TABLE `prescription_tests` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `test_type` enum('Blood','X-Ray','Ultrasound','CT Scan','MRI','ECG','EEG','Pathology','Other') NOT NULL,
  `test_description` text DEFAULT NULL,
  `recommended_date` date DEFAULT NULL,
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `simulation_config`
--

CREATE TABLE `simulation_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `simulation_config`
--

INSERT INTO `simulation_config` (`id`, `config_key`, `config_value`) VALUES
(1, 'grace_period_minutes', '5');

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
  `profile_photo` varchar(255) DEFAULT NULL,
  `subscription_status` enum('free','paid') DEFAULT 'free',
  `subscription_activated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `role`, `email`, `password`, `contact_number`, `emergency_contact`, `patient_id`, `reset_code`, `reset_expiry`, `first_login`, `profile_photo`, `subscription_status`, `subscription_activated_at`) VALUES
(10, 'Ardra S Nair', 'patient', 'ardrasnair2028@mca.ajce.in', '$2y$10$fVN3f11d/BtTTAex8s1ARe19QzyOe4bti.J4dILubrzumTn.e1b6C', NULL, '9656221258', NULL, NULL, NULL, 0, NULL, 'free', NULL),
(37, 'Meenuuu', 'admin', 'meenucpdy020@gmail.com', '$2y$10$Gt5aTC/22UsQ4JfaKc3t0..55CnJfQnKRfJd0db1LyUQoaVqy1Y2C', NULL, NULL, NULL, NULL, NULL, 0, 'profile_37_1769704613.jpeg', 'free', NULL),
(42, 'Smitha', 'patient', 'smithamgcpdy@gmail.com', '$2y$10$SYD0e417t1N4gM59ucp9FuplV.hqjF69kvPcNt/I5yDCTzr.YWwQq', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'free', NULL),
(45, 'Ardra', 'caretaker', 'ardrasnaircpdy@gmail.com', '$2y$10$FEC8k3RUcw4FcHjLOIMT3.2VQDstrjPHWuRg9yx6v7BDCZm7D.Qge', NULL, NULL, 10, NULL, NULL, 1, NULL, 'free', NULL);

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
-- Indexes for table `caretaker_notes`
--
ALTER TABLE `caretaker_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `caretaker_id` (`caretaker_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `dose_logs`
--
ALTER TABLE `dose_logs`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_date` (`patient_id`,`prescription_date`);

--
-- Indexes for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `prescription_tests`
--
ALTER TABLE `prescription_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `simulation_config`
--
ALTER TABLE `simulation_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `broadcast_reads`
--
ALTER TABLE `broadcast_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caregivers`
--
ALTER TABLE `caregivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `caretaker_notes`
--
ALTER TABLE `caretaker_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dose_logs`
--
ALTER TABLE `dose_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `medical_details`
--
ALTER TABLE `medical_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `medicine_catalog`
--
ALTER TABLE `medicine_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `medicine_schedule`
--
ALTER TABLE `medicine_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescription_tests`
--
ALTER TABLE `prescription_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `simulation_config`
--
ALTER TABLE `simulation_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
-- Constraints for table `caretaker_notes`
--
ALTER TABLE `caretaker_notes`
  ADD CONSTRAINT `caretaker_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `caretaker_notes_ibfk_2` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `caretaker_notes_ibfk_3` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  ADD CONSTRAINT `prescription_medicines_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_tests`
--
ALTER TABLE `prescription_tests`
  ADD CONSTRAINT `prescription_tests_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
