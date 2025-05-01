-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 05:45 PM
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
('AY2023', '2023-2024', '2023-06-01', '2024-03-31'),
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
('ADMIN', ''),
('STAFF', 'Staff'),
('STU', 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `clearance`
--

CREATE TABLE `clearance` (
  `clearance_id` int(11) NOT NULL,
  `studentNo` varchar(20) NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearance`
--

INSERT INTO `clearance` (`clearance_id`, `studentNo`, `status`, `updated_at`) VALUES
(9, '2022-02243', 'Completed', '2025-04-30 12:35:42'),
(10, '2022-02249', 'Pending', '2025-04-30 05:54:49'),
(11, '2022-02248', 'Pending', '2025-04-30 05:55:56'),
(12, '2022-02251', 'Pending', '2025-04-30 05:56:05'),
(13, '2022-02250', 'Pending', '2025-04-30 05:56:16'),
(14, '2022-02253', 'Pending', '2025-04-30 05:56:24'),
(15, '2022-02252', 'Pending', '2025-04-30 05:56:47'),
(16, '2022-02247', 'Pending', '2025-04-30 05:56:56'),
(17, '2022-02245', 'Pending', '2025-04-30 05:57:07'),
(18, '2022-02244', 'Completed', '2025-04-30 05:57:23'),
(19, '2022-02246', 'Pending', '2025-04-30 05:57:27'),
(20, '2022-02254', 'Pending', '2025-04-30 05:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `clearance_requirements`
--

CREATE TABLE `clearance_requirements` (
  `requirement_id` int(11) NOT NULL,
  `requirement_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `is_required` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clearance_requirements`
--

INSERT INTO `clearance_requirements` (`requirement_id`, `requirement_name`, `description`, `is_required`) VALUES
(1, 'Library Clearance', 'Return all borrowed books and settle fines', 1),
(2, 'Guidance Interview', 'Complete exit interview with guidance counselor', 1),
(3, 'Dean\'s Approval', 'Obtain clearance signature from the Dean', 1),
(4, 'Financial Clearance', 'Settle all financial obligations', 1),
(5, 'Registrar Clearance', 'Submit all required documents to registrar', 1),
(6, 'Property Clearance', 'Return all school property', 1),
(7, 'Student Council Clearance', 'Complete student organization obligations', 1);

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
(5, 'College Library'),
(1, 'Guidance Office'),
(2, 'Office of the Dean'),
(7, 'Office of the Finance Director'),
(6, 'Office of the Registrar'),
(4, 'Property Custodian'),
(3, 'Student Council');

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
('BSIS', 'Bachelor of Science in Information Systems'),
('BSIT', 'Bachelor of Science in Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `SectionCode` varchar(50) NOT NULL,
  `SectionTitle` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`SectionCode`, `SectionTitle`) VALUES
('1a', '1A'),
('1b', '1B'),
('2a', '2A'),
('2b', '2B'),
('3c', '3C'),
('4d', '4D');

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
  `CtrlNo` int(11) NOT NULL,
  `StaffID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Mname` varchar(50) DEFAULT NULL,
  `Department` varchar(100) NOT NULL,
  `AccountType` enum('Admin','Staff') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`CtrlNo`, `StaffID`, `Username`, `Email`, `PasswordHash`, `LastName`, `FirstName`, `Mname`, `Department`, `AccountType`) VALUES
(4, 1, 'admin', 'admin@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'ADMIN', 'Admin', 'Guidance Office', 'Admin'),
(5, 123, 'Mark', 'arciagamarkjoshua@gmail.com', '$2y$10$5VJsUzIrFPJJ4oPEmtqGVemYEqOqkOkd7GjbywkRN9jSWORsNKH4.', 'Arciaga', 'Mark Joshua', 'Perico', 'College Library', 'Staff'),
(6, 2, 'reanne', 'reannematias0909@gmail.com', '$2y$10$qkssuNUpR3GsYhLOfHO0KOwt985fS3pzNzesJAL5f7iSMgAGPJ4xq', 'Matias', 'Reeanne', 'Sta.rosa', 'Office of the Dean', 'Staff');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `CtrlNo` int(11) NOT NULL,
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
  `AccountType` varchar(20) NOT NULL DEFAULT 'Student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`CtrlNo`, `studentNo`, `Username`, `Email`, `PasswordHash`, `LastName`, `FirstName`, `Mname`, `ProgramCode`, `Level`, `SectionCode`, `AcademicYear`, `Semester`, `AccountType`) VALUES
(1, '2022-02243', 'Mark', 'arciagamarkjoshua@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN.VpdXvwBki', 'Arciaga', 'Mark Joshua', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(2, '2022-02244', 'Reanne', 'reannematias@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Matias', 'Reanne Ashley', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(3, '2022-02245', 'Andrei', 'andreimartinez@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Martinez', 'Andrei', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(4, '2022-02246', 'Richmond', 'richmondpascual@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Pascual', 'Richmond', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(5, '2022-02247', 'Thirdy', 'thirdydoldol@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'DolDol', 'Thirdy', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(6, '2022-02248', 'Patrick', 'patrickbaltaraz@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Baltaraz', 'Patrick', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(7, '2022-02249', 'Caroll', 'carollvillarreal@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Villarreal', 'Caroll', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(8, '2022-02250', 'Erica', 'ericacruz@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Cruz', 'Erica', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(9, '2022-02251', 'RC', 'rcchua@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Chua', 'RC', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(10, '2022-02252', 'Charles', 'charlesdelosantos@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'Delo Santos', 'Charles', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(11, '2022-02253', 'Ram', 'ramdejesus@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'De Jesus', 'Ram Michael', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU'),
(12, '2022-02254', 'Robinx', 'robinxsanpedro@gmail.com', '$2y$10$pr5iEB45vyetpVyfEKnMD.78hzkX2tcwglyEhpSm4yN...', 'San Pedro', 'Robinx', 'Perico', 'BSCS', 3, '2b', '2024-2025', 'Second Semester', 'STU');

-- --------------------------------------------------------

--
-- Table structure for table `student_clearance_status`
--

CREATE TABLE `student_clearance_status` (
  `studentNo` varchar(20) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `StaffID` int(11) NOT NULL,
  `status` enum('Pending','Approved') DEFAULT 'Pending',
  `comments` text DEFAULT NULL,
  `date_approved` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_clearance_status`
--

INSERT INTO `student_clearance_status` (`studentNo`, `requirement_id`, `StaffID`, `status`, `comments`, `date_approved`, `updated_at`) VALUES
('2022-02243', 1, 123, 'Approved', NULL, NULL, '2025-04-30 12:29:55'),
('2022-02243', 2, 1, 'Approved', NULL, NULL, '2025-04-30 05:54:59'),
('2022-02243', 3, 2, 'Approved', NULL, NULL, '2025-04-30 12:35:42'),
('2022-02243', 4, 1, 'Approved', NULL, NULL, '2025-04-30 05:55:13'),
('2022-02243', 5, 1, 'Approved', NULL, NULL, '2025-04-16 13:24:45'),
('2022-02243', 6, 1, 'Approved', NULL, NULL, '2025-04-16 13:24:46'),
('2022-02243', 7, 1, 'Approved', NULL, NULL, '2025-04-16 13:24:47'),
('2022-02244', 1, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:23'),
('2022-02244', 2, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:19'),
('2022-02244', 3, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:20'),
('2022-02244', 4, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:20'),
('2022-02244', 5, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:22'),
('2022-02244', 6, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:21'),
('2022-02244', 7, 1, 'Approved', NULL, NULL, '2025-04-30 05:57:22'),
('2022-02245', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:07'),
('2022-02245', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:07'),
('2022-02245', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:08'),
('2022-02245', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:08'),
('2022-02245', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:09'),
('2022-02245', 6, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:59'),
('2022-02245', 7, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:59'),
('2022-02246', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:27'),
('2022-02246', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:27'),
('2022-02246', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:28'),
('2022-02246', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:28'),
('2022-02246', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:29'),
('2022-02246', 6, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:30'),
('2022-02246', 7, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:31'),
('2022-02247', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:56'),
('2022-02247', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:57'),
('2022-02247', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:57'),
('2022-02247', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:58'),
('2022-02247', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:59'),
('2022-02247', 6, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:50'),
('2022-02247', 7, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:51'),
('2022-02248', 1, 1, 'Approved', NULL, NULL, '2025-04-30 06:40:27'),
('2022-02248', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:57'),
('2022-02248', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:57'),
('2022-02248', 4, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:25'),
('2022-02248', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:59'),
('2022-02248', 6, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:59'),
('2022-02248', 7, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:00'),
('2022-02249', 1, 1, 'Pending', NULL, NULL, '2025-04-30 06:10:15'),
('2022-02249', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:04'),
('2022-02249', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:05'),
('2022-02249', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:05'),
('2022-02249', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:06'),
('2022-02249', 6, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:06'),
('2022-02249', 7, 1, 'Pending', NULL, NULL, '2025-04-30 05:55:07'),
('2022-02250', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:16'),
('2022-02250', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:16'),
('2022-02250', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:16'),
('2022-02250', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:18'),
('2022-02250', 5, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:36'),
('2022-02250', 6, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:36'),
('2022-02250', 7, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:37'),
('2022-02251', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:05'),
('2022-02251', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:06'),
('2022-02251', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:06'),
('2022-02251', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:07'),
('2022-02251', 5, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:32'),
('2022-02251', 6, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:08'),
('2022-02251', 7, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:08'),
('2022-02252', 1, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:47'),
('2022-02252', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:48'),
('2022-02252', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:48'),
('2022-02252', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:49'),
('2022-02252', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:50'),
('2022-02252', 6, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:50'),
('2022-02252', 7, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:50'),
('2022-02253', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:24'),
('2022-02253', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:24'),
('2022-02253', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:25'),
('2022-02253', 4, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:26'),
('2022-02253', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:56:26'),
('2022-02253', 6, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:40'),
('2022-02253', 7, 1, 'Pending', NULL, NULL, '2025-04-30 06:09:41'),
('2022-02254', 1, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:36'),
('2022-02254', 2, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:37'),
('2022-02254', 3, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:38'),
('2022-02254', 4, 1, 'Pending', NULL, NULL, '2025-04-30 06:10:11'),
('2022-02254', 5, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:39'),
('2022-02254', 6, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:39'),
('2022-02254', 7, 1, 'Pending', NULL, NULL, '2025-04-30 05:57:40');

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
-- Indexes for table `clearance`
--
ALTER TABLE `clearance`
  ADD PRIMARY KEY (`clearance_id`),
  ADD KEY `clearance_ibfk_1` (`studentNo`);

--
-- Indexes for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  ADD PRIMARY KEY (`requirement_id`);

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
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`ProgramCode`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`SectionCode`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`Code`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`CtrlNo`),
  ADD UNIQUE KEY `StaffID` (`StaffID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`CtrlNo`),
  ADD UNIQUE KEY `studentNo` (`studentNo`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `student_clearance_status`
--
ALTER TABLE `student_clearance_status`
  ADD PRIMARY KEY (`studentNo`,`requirement_id`),
  ADD KEY `requirement_id` (`requirement_id`),
  ADD KEY `StaffID` (`StaffID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clearance`
--
ALTER TABLE `clearance`
  MODIFY `clearance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `LevelID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `CtrlNo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `CtrlNo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clearance`
--
ALTER TABLE `clearance`
  ADD CONSTRAINT `clearance_ibfk_1` FOREIGN KEY (`studentNo`) REFERENCES `students` (`studentNo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_clearance_status`
--
ALTER TABLE `student_clearance_status`
  ADD CONSTRAINT `student_clearance_status_ibfk_1` FOREIGN KEY (`studentNo`) REFERENCES `students` (`studentNo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_clearance_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `clearance_requirements` (`requirement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_clearance_status_ibfk_3` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`StaffID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
