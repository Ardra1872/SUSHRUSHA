-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 06, 2026 at 10:47 AM
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
(7, 'Added Cetrizine to the Catalogue', 37, '2026-01-29 16:30:02'),
(8, 'SCDC', 37, '2026-02-01 13:52:19');

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
(27, 10, 45, 'Sister', 1),
(29, 48, 49, 'Daughter', 1);

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

--
-- Dumping data for table `caretaker_notes`
--

INSERT INTO `caretaker_notes` (`id`, `patient_id`, `caretaker_id`, `medicine_id`, `note_type`, `message`, `created_at`, `updated_at`) VALUES
(6, 10, 45, NULL, 'medicine', 'this is dolo note', '2026-02-01 09:55:46', '2026-02-01 09:55:46'),
(7, 10, 45, NULL, 'general', 'this is general', '2026-02-01 09:56:25', '2026-02-01 09:56:25'),
(8, 48, 49, 57, 'medicine', 'missed morning dose', '2026-02-01 14:42:23', '2026-02-01 14:42:23');

-- --------------------------------------------------------

--
-- Table structure for table `doses`
--

CREATE TABLE `doses` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `manual_medicine_id` int(11) DEFAULT NULL,
  `prescription_medicine_id` int(11) DEFAULT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `status` enum('upcoming','taken','missed') DEFAULT 'upcoming',
  `taken_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doses`
--

INSERT INTO `doses` (`id`, `patient_id`, `manual_medicine_id`, `prescription_medicine_id`, `scheduled_datetime`, `status`, `taken_at`) VALUES
(133, 10, 99, NULL, '2026-03-05 21:04:00', 'taken', '2026-03-05 21:08:40'),
(134, 10, 99, NULL, '2026-03-06 21:04:00', 'upcoming', NULL),
(135, 10, 99, NULL, '2026-03-05 20:00:00', 'upcoming', NULL),
(136, 10, 99, NULL, '2026-03-06 20:00:00', 'upcoming', NULL),
(137, 10, 100, NULL, '2026-03-05 21:11:00', 'taken', '2026-03-05 21:11:31'),
(138, 10, 100, NULL, '2026-03-06 21:11:00', 'upcoming', NULL),
(139, 10, 100, NULL, '2026-03-05 20:00:00', 'upcoming', NULL),
(140, 10, 100, NULL, '2026-03-06 20:00:00', 'upcoming', NULL);

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
(24, 97, 'TAKEN', '2026-01-31 06:38:22', '2026-01-31 12:08:22'),
(25, 96, 'TAKEN', '2026-02-01 09:28:06', NULL),
(26, 97, 'TAKEN', '2026-02-01 09:28:10', NULL),
(27, 92, 'MISSED', '2026-02-01 09:44:10', NULL),
(28, 93, 'MISSED', '2026-02-01 09:44:40', NULL),
(29, 100, 'TAKEN', '2026-02-01 13:37:07', '2026-02-01 19:07:07'),
(30, 101, 'TAKEN', '2026-02-01 13:37:12', '2026-02-01 19:07:12'),
(31, 103, 'TAKEN', '2026-02-01 14:40:23', '2026-02-01 20:10:23'),
(32, 105, 'MISSED', '2026-02-01 14:47:28', NULL),
(33, 93, 'MISSED', '2026-02-02 05:11:34', NULL),
(34, 106, 'MISSED', '2026-02-02 05:13:28', NULL),
(35, 107, 'MISSED', '2026-02-02 05:24:41', '2026-02-02 10:54:41'),
(36, 108, 'TAKEN', '2026-02-02 06:35:11', '2026-02-02 12:05:11'),
(37, 110, 'MISSED', '2026-02-06 06:49:07', '2026-02-06 12:19:07'),
(38, 112, 'TAKEN', '2026-02-06 06:59:59', '2026-02-06 12:29:59'),
(39, 114, 'TAKEN', '2026-02-15 11:14:35', '2026-02-15 16:44:35'),
(40, 116, 'TAKEN', '2026-02-15 11:28:11', '2026-02-15 16:58:11'),
(41, 118, 'TAKEN', '2026-02-15 11:30:46', '2026-02-15 17:00:46'),
(42, 120, 'TAKEN', '2026-02-15 11:33:35', '2026-02-15 17:03:35'),
(43, 121, 'MISSED', '2026-02-15 11:40:06', '2026-02-15 17:10:06'),
(44, 122, 'TAKEN', '2026-02-15 12:47:39', '2026-02-15 18:17:39'),
(45, 124, 'TAKEN', '2026-02-15 12:47:41', '2026-02-15 18:17:41'),
(46, 126, 'MISSED', '2026-02-15 12:57:33', '2026-02-15 18:27:33'),
(47, 128, 'MISSED', '2026-02-15 12:58:33', '2026-02-15 18:28:33'),
(48, 130, 'MISSED', '2026-02-15 13:09:02', '2026-02-15 18:39:02'),
(49, 132, 'TAKEN', '2026-02-15 13:09:11', '2026-02-15 18:39:11'),
(50, 134, 'TAKEN', '2026-02-15 13:13:32', '2026-02-15 18:43:32'),
(51, 131, 'TAKEN', '2026-02-15 14:31:07', '2026-02-15 20:01:07'),
(52, 135, 'TAKEN', '2026-02-15 14:32:00', '2026-02-15 20:02:00'),
(53, 138, 'TAKEN', '2026-02-15 14:40:03', '2026-02-15 20:10:03'),
(54, 141, 'TAKEN', '2026-02-15 15:03:38', '2026-02-15 20:33:38'),
(55, 142, 'TAKEN', '2026-02-18 05:14:34', '2026-02-18 10:44:34'),
(56, 144, 'TAKEN', '2026-02-18 05:17:43', '2026-02-18 10:47:43'),
(57, 146, 'TAKEN', '2026-02-18 05:25:51', '2026-02-18 10:55:51'),
(58, 148, 'TAKEN', '2026-02-18 05:30:26', '2026-02-18 11:00:26'),
(59, 150, 'TAKEN', '2026-02-18 05:52:31', '2026-02-18 11:22:31'),
(60, 152, 'MISSED', '2026-03-05 03:37:34', NULL),
(61, 153, 'TAKEN', '2026-03-05 03:39:58', NULL),
(62, 154, 'TAKEN', '2026-03-05 03:42:33', '2026-03-05 09:12:33'),
(63, 155, 'MISSED', '2026-03-05 03:44:33', NULL),
(64, 156, 'TAKEN', '2026-03-05 03:48:36', '2026-03-05 09:18:36'),
(65, 157, 'TAKEN', '2026-03-05 03:49:13', NULL),
(66, 158, 'TAKEN', '2026-03-05 03:51:18', '2026-03-05 09:21:18'),
(67, 159, 'MISSED', '2026-03-05 03:51:40', NULL),
(68, 160, 'TAKEN', '2026-03-05 09:34:07', '2026-03-05 15:04:07'),
(69, 162, 'TAKEN', '2026-03-05 09:37:23', NULL),
(70, 166, 'TAKEN', '2026-03-05 09:43:13', '2026-03-05 15:13:13'),
(71, 168, 'MISSED', '2026-03-05 09:49:06', '2026-03-05 15:19:06'),
(72, 170, 'TAKEN', '2026-03-05 09:52:09', '2026-03-05 15:22:09'),
(73, 172, 'MISSED', '2026-03-05 10:19:19', '2026-03-05 15:49:19'),
(74, 173, 'TAKEN', '2026-03-05 14:31:14', '2026-03-05 20:01:14'),
(75, 176, 'TAKEN', '2026-03-05 14:48:25', '2026-03-05 20:18:25'),
(76, 178, 'TAKEN', '2026-03-05 15:11:09', '2026-03-05 20:41:09'),
(77, 180, 'TAKEN', '2026-03-05 15:18:36', '2026-03-05 20:48:36'),
(78, 182, 'MISSED', '2026-03-05 15:25:23', NULL),
(79, 183, 'TAKEN', '2026-03-05 15:25:38', NULL),
(80, 184, 'MISSED', '2026-03-05 15:32:18', '2026-03-05 21:02:18'),
(81, 186, 'TAKEN', '2026-03-05 15:38:40', '2026-03-05 21:08:40'),
(82, 188, 'TAKEN', '2026-03-05 15:41:31', '2026-03-05 21:11:31');

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
(57, 48, 'Metformin', 'pill', NULL, '500mg', '', 1, '2026-02-01', '2026-02-02', 'Pill', 'Daily', NULL, NULL, 'daily', NULL, 'fixed', 8),
(58, 48, 'Amlodphin', 'pill', NULL, '10mg', '', 2, '2026-02-01', '2026-02-02', 'Pill', 'Daily', NULL, NULL, 'daily', NULL, 'fixed', 8),
(99, 10, 'Ibuprofen', 'pill', NULL, '200mg', '', 1, '2026-03-05', '2026-03-06', 'Pill', 'Daily', NULL, NULL, 'daily', NULL, 'fixed', 8),
(100, 10, 'Cetrizine', 'pill', NULL, '10mg', '', 2, '2026-03-05', '2026-03-06', 'Pill', 'Daily', NULL, NULL, 'daily', NULL, 'fixed', 8);

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
(13, 'Cetrizine', '10mg', 'Pill'),
(14, 'Theophyline', '10mg', 'Pill'),
(15, 'Amlodphin', '10mg', 'Pill'),
(16, 'Metformin', '500mg', 'Pill');

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

--
-- Dumping data for table `medicine_requests`
--

INSERT INTO `medicine_requests` (`id`, `name`, `dosage`, `form`, `requested_by`, `status`, `created_at`, `reason`) VALUES
(13, 'Theophyline', '10mg', 'Pill', 10, 'approved', '2026-02-01 10:23:00', 'sbvk'),
(14, 'Metformin', '500mg', 'Pill', 48, 'approved', '2026-02-01 14:34:50', NULL),
(15, 'Amlodphin', '10mg', 'Pill', 48, 'approved', '2026-02-01 14:35:07', NULL);

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
(102, 57, '08:00:00'),
(103, 57, '20:10:00'),
(104, 58, '08:00:00'),
(105, 58, '20:00:00'),
(186, 99, '21:04:00'),
(187, 99, '20:00:00'),
(188, 100, '21:11:00'),
(189, 100, '20:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `caretaker_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `patient_id`, `caretaker_id`, `message`, `status`, `created_at`) VALUES
(1, 10, 45, 'vbxv', 'read', '2026-02-01 10:16:23'),
(2, 10, 45, 'helo', 'read', '2026-02-01 10:16:47'),
(3, 48, 49, 'bvnxfng', 'unread', '2026-02-01 14:42:47');

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
(10, '2003-01-11', 'Female', 'O+', 169, 40, 'uploads/profile/profile_10.jpg'),
(48, '2016-02-09', 'Male', 'O+', 170, 70, 'uploads/profile/profile_48.jpeg');

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

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `patient_id`, `prescription_date`, `disease_name`, `disease_description`, `doctor_name`, `hospital_name`, `notes`, `created_at`, `updated_at`) VALUES
(5, 48, '2026-01-30', 'Diabetes', 'diabetes', 'Dr Rajesh', 'City Hospital', 'dsdaa', '2026-02-01 14:33:11', '2026-02-01 14:33:11'),
(6, 48, '2026-01-10', 'Hypertension', 'dvsvf', 'Dr Arun', 'Mary Queens', 'SVfvdf', '2026-02-01 14:33:33', '2026-02-01 14:33:33');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `frequency_type` varchar(50) DEFAULT 'once',
  `time_slots` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `before_after_food` enum('Before Food','After Food') DEFAULT 'After Food',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription_medicines`
--

INSERT INTO `prescription_medicines` (`id`, `prescription_id`, `medicine_name`, `dosage`, `frequency`, `duration`, `instructions`, `created_at`, `frequency_type`, `time_slots`, `start_date`, `end_date`, `before_after_food`, `notes`) VALUES
(6, 5, 'Insulin', '', '3 times daily', '30 days', 'sdfs', '2026-02-01 14:33:11', 'once', NULL, NULL, NULL, 'After Food', NULL),
(7, 6, 'Diuretics ', '50mg', '3 times daily', '30 days', 'czvzdv', '2026-02-01 14:33:33', 'once', NULL, NULL, NULL, 'After Food', NULL);

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
  `emergency_contact` varchar(255) DEFAULT NULL,
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
(45, 'Ardra', 'caretaker', 'ardrasnaircpdy@gmail.com', '$2y$10$x8hUj.plem3ys1q4ROk2LuLLBOfl9cTKblmcedL6IgZ/pHVVb5ud.', NULL, NULL, 10, '348937', '2026-02-01 16:00:06', 1, NULL, 'free', NULL),
(48, 'Ravi', 'patient', 'ravi@gmail.com', '$2y$10$R820/ZFIKw5CLgSqbmX1qOhfOqz4L.SmQZTLWnr6hwr31xuFPdq0i', NULL, 'Anjali - 9656221258', NULL, NULL, NULL, 0, NULL, 'free', NULL),
(49, 'Anjali', 'caretaker', 'smithamgcpdy@gmail.com', '$2y$10$9q29WYLSqSAziNNjFPdGnOSgKEgP6Po0pvxctHynWljXPoAe7QYs2', NULL, NULL, 48, NULL, NULL, 1, NULL, 'free', NULL);

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
-- Indexes for table `doses`
--
ALTER TABLE `doses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_medicine_id` (`prescription_medicine_id`),
  ADD KEY `idx_patient_status` (`patient_id`,`status`);

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
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `caretaker_id` (`caretaker_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `broadcast_reads`
--
ALTER TABLE `broadcast_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caregivers`
--
ALTER TABLE `caregivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `caretaker_notes`
--
ALTER TABLE `caretaker_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `doses`
--
ALTER TABLE `doses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `dose_logs`
--
ALTER TABLE `dose_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `medical_details`
--
ALTER TABLE `medical_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `medicine_catalog`
--
ALTER TABLE `medicine_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `medicine_requests`
--
ALTER TABLE `medicine_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `medicine_schedule`
--
ALTER TABLE `medicine_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `prescription_medicines`
--
ALTER TABLE `prescription_medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `prescription_tests`
--
ALTER TABLE `prescription_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `simulation_config`
--
ALTER TABLE `simulation_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

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
-- Constraints for table `doses`
--
ALTER TABLE `doses`
  ADD CONSTRAINT `doses_ibfk_1` FOREIGN KEY (`prescription_medicine_id`) REFERENCES `prescription_medicines` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
