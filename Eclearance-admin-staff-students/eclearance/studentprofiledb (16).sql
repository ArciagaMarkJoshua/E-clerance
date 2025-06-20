-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2025 at 02:34 PM
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
-- Database: `studentprofiledb`
--

-- --------------------------------------------------------

--
-- Table structure for table `academicyears`
--

CREATE TABLE `academicyears` (
  `Code` varchar(50) NOT NULL,
  `AcademicYear` varchar(50) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academicyears`
--

INSERT INTO `academicyears` (`Code`, `AcademicYear`, `StartDate`, `EndDate`) VALUES
('AY2025', '2024-2025', '2025-01-01', '2025-05-31');

-- --------------------------------------------------------

--
-- Table structure for table `accounttypes`
--

CREATE TABLE `accounttypes` (
  `Code` varchar(50) NOT NULL,
  `AccountName` enum('Student','Staff') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounttypes`
--

INSERT INTO `accounttypes` (`Code`, `AccountName`) VALUES
('ADMIN', 'Staff'),
('STAFF', 'Staff'),
('STU', 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `user_type` enum('Student','Staff') NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_type`, `action`, `details`, `ip_address`, `created_at`) VALUES
(63, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 09:02:20'),
(64, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 09:03:01'),
(65, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:03:19'),
(66, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:03:46'),
(67, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:10:01'),
(68, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0003, requirement Dean\'s Approval', NULL, '2025-06-13 09:10:17'),
(69, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0003, requirement Dean\'s Approval', NULL, '2025-06-13 09:13:04'),
(70, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0003, requirement Dean\'s Approval', NULL, '2025-06-13 09:14:05'),
(71, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 09:14:11'),
(72, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0003, requirement Dean\'s Approval', NULL, '2025-06-13 09:14:31'),
(73, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:14:57'),
(74, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0003, requirement Dean\'s Approval', NULL, '2025-06-13 09:15:16'),
(75, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:15:24'),
(76, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:18:34'),
(77, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 09:19:25'),
(78, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:13:17'),
(79, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 12:13:26'),
(80, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 12:14:25'),
(81, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 12:18:27'),
(82, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 12:19:51'),
(83, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0002, requirement Dean\'s Approval', NULL, '2025-06-13 12:22:01'),
(84, '321', 'Staff', 'Bulk Clearance: Pending Approve', 'Bulk updated clearance for student 2023-0002, requirement Dean\'s Approval to Pending Approve', NULL, '2025-06-13 12:27:37'),
(85, '321', 'Staff', 'Bulk Clearance: Pending', 'Bulk updated clearance for student 2023-0003, requirement Dean\'s Approval to Pending', NULL, '2025-06-13 12:27:37'),
(86, '321', 'Staff', 'Bulk Clearance: Pending', 'Bulk updated clearance for student 2023-0001, requirement Dean\'s Approval to Pending', NULL, '2025-06-13 12:30:45'),
(87, '321', 'Staff', 'Bulk Clearance: Pending Approve', 'Bulk updated clearance for student 2023-0002, requirement Dean\'s Approval to Pending Approve', NULL, '2025-06-13 12:30:45'),
(88, '321', 'Staff', 'Bulk Clearance: Approved', 'Bulk updated clearance for student 2023-0003, requirement Dean\'s Approval to Approved', NULL, '2025-06-13 12:30:45'),
(89, '321', 'Staff', 'Bulk Clearance: Pending', 'Bulk updated clearance for student 2023-0001, requirement Dean\'s Approval to Pending', NULL, '2025-06-13 12:32:15'),
(90, '321', 'Staff', 'Bulk Clearance: Pending', 'Bulk updated clearance for student 2023-0002, requirement Dean\'s Approval to Pending', NULL, '2025-06-13 12:32:15'),
(91, '321', 'Staff', 'Bulk Clearance: Approved', 'Bulk updated clearance for student 2023-0003, requirement Dean\'s Approval to Approved', NULL, '2025-06-13 12:32:15'),
(92, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:35:03'),
(93, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:35:16'),
(94, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:35:25'),
(95, '321', 'Staff', 'Updated Clearance Status', 'Pending Approve clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:35:33'),
(96, '321', 'Staff', 'Updated Clearance Status', 'Pending Approve clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:35:59'),
(97, '321', 'Staff', 'Updated Clearance Status', 'Pending Approve clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:36:05'),
(98, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:36:11'),
(99, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-13 12:38:23'),
(100, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 2023-0004, requirement Dean\'s Approval', NULL, '2025-06-13 12:38:30'),
(101, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0004, requirement Dean\'s Approval', NULL, '2025-06-13 12:42:18'),
(102, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2023-0004, requirement Dean\'s Approval', NULL, '2025-06-13 12:42:48'),
(103, '321', 'Staff', 'Bulk Clearance: Approved', 'Bulk updated clearance for student 2023-0001, requirement Dean\'s Approval to Approved', NULL, '2025-06-13 12:43:05'),
(104, '321', 'Staff', 'Bulk Clearance: Approved', 'Bulk updated clearance for student 2023-0002, requirement Dean\'s Approval to Approved', NULL, '2025-06-13 12:43:05'),
(105, '321', 'Staff', 'Bulk Clearance: Approved', 'Bulk updated clearance for student 2023-0003, requirement Dean\'s Approval to Approved', NULL, '2025-06-13 12:43:05'),
(106, '321', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-17 12:12:48'),
(107, '321', 'Staff', 'Approved Clearance', 'Approved clearance for student 123, requirement Dean\'s Approval', NULL, '2025-06-17 12:13:03');

-- --------------------------------------------------------

--
-- Table structure for table `clearance`
--

CREATE TABLE `clearance` (
  `clearance_id` int(11) NOT NULL,
  `studentNo` varchar(20) NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clearance_requirements`
--

CREATE TABLE `clearance_requirements` (
  `requirement_id` int(11) NOT NULL,
  `requirement_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearance_requirements`
--

INSERT INTO `clearance_requirements` (`requirement_id`, `requirement_name`, `description`, `is_required`, `department_id`) VALUES
(1, 'Library Clearance', 'Screenshot of the library page', 1, 1),
(2, 'Guidance Interview', 'photo id', 1, 2),
(3, 'Dean\'s Approval', 'Obtain clearance signature from the Dean', 1, 3),
(4, 'Financial Clearance', 'Settle all financial obligations', 1, 4),
(5, 'Registrar Clearance', 'Submit all required documents to registrar', 1, 5),
(6, 'Property Clearance', 'Return all school property', 1, 6),
(7, 'Student Council Clearance', 'Complete student organization obligations', 1, 7),
(8, 'Medical Clearance', 'Complete medical examination and health check', 1, 8),
(9, 'MSO Clearance', 'Complete MSO requirements and documentation', 1, 9);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL,
  `DepartmentName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`DepartmentID`, `DepartmentName`) VALUES
(1, 'College Library'),
(2, 'Guidance Office'),
(3, 'Office of the Dean'),
(4, 'Office of the Finance Director'),
(5, 'Office of the Registrar'),
(6, 'Property Custodian'),
(7, 'Student Council'),
(8, 'Clinic'),
(9, 'MSO');

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `LevelID` int(11) NOT NULL,
  `LevelName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`LevelID`, `LevelName`) VALUES
(1, '1st Year'),
(2, '2nd Year'),
(3, '3rd Year'),
(4, '4th Year'),
(5, '5th Year');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `user_type` enum('Student','Staff') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `OfficeID` int(11) NOT NULL,
  `OfficeName` varchar(255) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`OfficeID`, `OfficeName`, `DepartmentID`, `Description`) VALUES
(1, 'CCS Office', 3, 'Office of the Dean - College of Computer Studies'),
(2, 'CBA Office', 3, 'Office of the Dean - College of Business Administration'),
(3, 'CCS Property Custodian', 6, 'Property Custodian for College of Computer Studies'),
(4, 'CBA Property Custodian', 6, 'Property Custodian for College of Business Administration'),
(5, 'CCS Student Council', 7, 'Student Council for College of Computer Studies'),
(6, 'CBA Student Council', 7, 'Student Council for College of Business Administration'),
(7, 'BBA', 6, 'Property - BBA'),
(8, 'Main Clinic', 8, 'Main Medical Clinic for student health services'),
(9, 'MSO Office', 9, 'Main MSO Office for student organization services'),
(10, 'Health Services Unit', 8, 'Health Services Unit for medical clearance');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `ProgramCode` varchar(50) NOT NULL,
  `ProgramTitle` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`ProgramCode`, `ProgramTitle`) VALUES
('BSCS', 'Bachelor of Science in Computer Science'),
('BSIT', 'Bachelor of Science in Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `registration_requests`
--

CREATE TABLE `registration_requests` (
  `request_id` int(11) NOT NULL,
  `studentNo` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `programCode` varchar(10) NOT NULL,
  `level` int(11) NOT NULL,
  `sectionCode` varchar(10) NOT NULL,
  `academicYear` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `request_date` datetime NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_requests`
--

INSERT INTO `registration_requests` (`request_id`, `studentNo`, `email`, `lastName`, `firstName`, `middleName`, `programCode`, `level`, `sectionCode`, `academicYear`, `semester`, `password_hash`, `status`, `request_date`, `processed_date`, `processed_by`, `notes`) VALUES
(9, '12312', 'arciagamarkjoshua2@gmail.com', 'mark', 'arcy', 'dean', 'BSCS', 2, '2c', '2024-2025', 'Second Semester', '$2y$10$Dn76XjcjIUL0f.y.yLJnxeHaLTogdk37FaA7D1/qZroijKYjvs9AC', 'Rejected', '2025-06-11 17:55:48', '2025-06-12 21:00:57', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `SectionCode` varchar(50) NOT NULL,
  `ProgramCode` varchar(50) NOT NULL,
  `SectionTitle` varchar(255) NOT NULL,
  `YearLevel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`SectionCode`, `ProgramCode`, `SectionTitle`, `YearLevel`) VALUES
('BSCS1A', 'BSCS', 'BSCS1A', 1),
('BSCS1B', 'BSCS', 'BSCS1B', 1),
('BSCS1C', 'BSCS', 'BSCS1C', 1),
('BSCS1D', 'BSCS', 'BSCS1D', 1),
('BSCS2A', 'BSCS', 'BSCS2A', 2),
('BSCS2B', 'BSCS', 'BSCS2B', 2),
('BSCS2C', 'BSCS', 'BSCS2C', 2),
('BSCS2D', 'BSCS', 'BSCS2D', 2),
('BSCS3A', 'BSCS', 'BSCS3A', 3),
('BSCS3B', 'BSCS', 'BSCS3B', 3),
('BSCS3C', 'BSCS', 'BSCS3C', 3),
('BSCS3D', 'BSCS', 'BSCS3D', 3),
('BSCS4A', 'BSCS', 'BSCS4A', 4),
('BSCS4B', 'BSCS', 'BSCS4B', 4),
('BSCS4C', 'BSCS', 'BSCS4C', 4),
('BSCS4D', 'BSCS', 'BSCS4D', 4),
('BSCS5A', 'BSCS', 'BSCS5A', 5),
('BSCS5B', 'BSCS', 'BSCS5B', 5),
('BSCS5C', 'BSCS', 'BSCS5C', 5),
('BSCS5D', 'BSCS', 'BSCS5D', 5),
('BSIT1A', 'BSIT', 'BSIT1A', 1),
('BSIT1B', 'BSIT', 'BSIT1B', 1),
('BSIT1C', 'BSIT', 'BSIT1C', 1),
('BSIT1D', 'BSIT', 'BSIT1D', 1),
('BSIT2A', 'BSIT', 'BSIT2A', 2),
('BSIT2B', 'BSIT', 'BSIT2B', 2),
('BSIT2C', 'BSIT', 'BSIT2C', 2),
('BSIT2D', 'BSIT', 'BSIT2D', 2),
('BSIT3A', 'BSIT', 'BSIT3A', 3),
('BSIT3B', 'BSIT', 'BSIT3B', 3),
('BSIT3C', 'BSIT', 'BSIT3C', 3),
('BSIT3D', 'BSIT', 'BSIT3D', 3),
('BSIT4A', 'BSIT', 'BSIT4A', 4),
('BSIT4B', 'BSIT', 'BSIT4B', 4),
('BSIT4C', 'BSIT', 'BSIT4C', 4),
('BSIT4D', 'BSIT', 'BSIT4D', 4),
('BSIT5A', 'BSIT', 'BSIT5A', 5),
('BSIT5B', 'BSIT', 'BSIT5B', 5),
('BSIT5C', 'BSIT', 'BSIT5C', 5),
('BSIT5D', 'BSIT', 'BSIT5D', 5);

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `Code` varchar(50) NOT NULL,
  `Semester` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`Code`, `Semester`) VALUES
('S1', 'First Semester'),
('S2', 'Second Semester'),
('SU', 'Summer Semester');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `RegistrationNo` varchar(20) DEFAULT NULL,
  `StaffID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `Department` varchar(100) NOT NULL,
  `AccountType` enum('Admin','Staff') NOT NULL,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`RegistrationNo`, `StaffID`, `Username`, `Email`, `PasswordHash`, `LastName`, `FirstName`, `Mname`, `Department`, `AccountType`, `LastLogin`, `IsActive`) VALUES
('2025-000', 1, 'admin1', 'admin@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'ADMIN', 'Admin', 'CCS Property Custodian', 'Admin', NULL, 1),
('2025-001', 321, '123213', 'arciagamarkjoshua@gmail.com', '$2y$10$0TQS2RdMQHxdRoxaKPKzhOSlF247mP198sLeeYvfDN3FFXRukqQ4W', 'mark', 'arcy', 'dean', 'CBA Office', 'Staff', NULL, 1),
('2025-002', 322, 'clinic_staff', 'clinic@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Santos', 'Maria', 'Cruz', 'Main Clinic', 'Staff', NULL, 1),
('2025-003', 323, 'mso_staff', 'mso@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Garcia', 'Juan', 'Delos', 'MSO Office', 'Staff', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `RegistrationNo` varchar(20) NOT NULL,
  `studentNo` varchar(20) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `ProgramCode` varchar(10) NOT NULL,
  `Level` int(11) NOT NULL,
  `SectionCode` varchar(10) NOT NULL,
  `AcademicYear` varchar(20) DEFAULT NULL,
  `Semester` varchar(20) DEFAULT NULL,
  `AccountType` varchar(20) NOT NULL DEFAULT 'Student',
  `LastLogin` timestamp NULL DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`RegistrationNo`, `studentNo`, `Username`, `Email`, `PasswordHash`, `LastName`, `FirstName`, `Mname`, `ProgramCode`, `Level`, `SectionCode`, `AcademicYear`, `Semester`, `AccountType`, `LastLogin`, `IsActive`) VALUES
('2025-0001', '123', 'mark', 'arciagamarkjoshua@gmail.com', '$2y$10$ZWWcrE0QMWOEEDpLO4/2d..fKNYXtbeLxanm1GTUZV50BBre5Bku2', 'arciaga', 'mark', 'perico', 'BSCS', 2, 'BSCS2A', '2024-2025', 'Second Semester', 'Student', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_clearance_status`
--

CREATE TABLE `student_clearance_status` (
  `id` int(11) NOT NULL,
  `studentNo` varchar(20) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `StaffID` int(11) NOT NULL,
  `status` enum('Pending','Approved') DEFAULT 'Pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `date_approved` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_clearance_status`
--

INSERT INTO `student_clearance_status` (`id`, `studentNo`, `requirement_id`, `StaffID`, `status`, `approved_by`, `comments`, `date_approved`, `created_at`, `updated_at`) VALUES
(59, '123', 3, 321, 'Approved', 'arcy mark', '', '2025-06-17 20:13:03', '2025-06-13 09:02:20', '2025-06-17 12:13:03');

--
-- Triggers `student_clearance_status`
--
DELIMITER $$
CREATE TRIGGER `after_clearance_status_update` AFTER UPDATE ON `student_clearance_status` FOR EACH ROW BEGIN
    DECLARE total_requirements INT;
    DECLARE approved_requirements INT;
    
    -- Count total requirements
    SELECT COUNT(*) INTO total_requirements FROM clearance_requirements;
    
    -- Count approved requirements for this student
    SELECT COUNT(*) INTO approved_requirements 
    FROM student_clearance_status 
    WHERE studentNo = NEW.studentNo AND status = 'Approved';
    
    -- Update overall clearance status
    IF approved_requirements = total_requirements THEN
        UPDATE clearance SET status = 'Completed' WHERE studentNo = NEW.studentNo;
    ELSE
        UPDATE clearance SET status = 'Pending' WHERE studentNo = NEW.studentNo;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_requirement_descriptions`
--

CREATE TABLE `student_requirement_descriptions` (
  `id` int(11) NOT NULL,
  `studentNo` varchar(20) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academicyears`
--
ALTER TABLE `academicyears`
  ADD PRIMARY KEY (`Code`);

--
-- Indexes for table `accounttypes`
--
ALTER TABLE `accounttypes`
  ADD PRIMARY KEY (`Code`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `clearance`
--
ALTER TABLE `clearance`
  ADD PRIMARY KEY (`clearance_id`),
  ADD UNIQUE KEY `studentNo` (`studentNo`);

--
-- Indexes for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`DepartmentID`),
  ADD UNIQUE KEY `DepartmentName` (`DepartmentName`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`LevelID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`OfficeID`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`ProgramCode`);

--
-- Indexes for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `studentNo` (`studentNo`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`SectionCode`),
  ADD KEY `YearLevel` (`YearLevel`),
  ADD KEY `fk_sections_program` (`ProgramCode`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`Code`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD UNIQUE KEY `StaffID` (`StaffID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `RegistrationNo` (`RegistrationNo`),
  ADD KEY `Department` (`Department`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD UNIQUE KEY `studentNo` (`studentNo`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `RegistrationNo` (`RegistrationNo`),
  ADD KEY `ProgramCode` (`ProgramCode`),
  ADD KEY `Level` (`Level`),
  ADD KEY `SectionCode` (`SectionCode`);

--
-- Indexes for table `student_clearance_status`
--
ALTER TABLE `student_clearance_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_requirement` (`studentNo`,`requirement_id`),
  ADD KEY `requirement_id` (`requirement_id`),
  ADD KEY `StaffID` (`StaffID`),
  ADD KEY `idx_approved_by` (`approved_by`);

--
-- Indexes for table `student_requirement_descriptions`
--
ALTER TABLE `student_requirement_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_requirement` (`studentNo`,`requirement_id`),
  ADD KEY `requirement_id` (`requirement_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `clearance`
--
ALTER TABLE `clearance`
  MODIFY `clearance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `LevelID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `OfficeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `registration_requests`
--
ALTER TABLE `registration_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_clearance_status`
--
ALTER TABLE `student_clearance_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `student_requirement_descriptions`
--
ALTER TABLE `student_requirement_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clearance`
--
ALTER TABLE `clearance`
  ADD CONSTRAINT `clearance_ibfk_1` FOREIGN KEY (`studentNo`) REFERENCES `students` (`studentNo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  ADD CONSTRAINT `clearance_requirements_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD CONSTRAINT `registration_requests_ibfk_1` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`StaffID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_program` FOREIGN KEY (`ProgramCode`) REFERENCES `programs` (`ProgramCode`),
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`YearLevel`) REFERENCES `levels` (`LevelID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `