-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2025 at 06:09 PM
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
('AY2023', '2023-2024', '2025-06-04', '2025-06-14'),
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
(1, '123', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 3', NULL, '2025-05-31 09:53:45'),
(2, '123', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 3', NULL, '2025-05-31 09:53:46'),
(3, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 3', NULL, '2025-05-31 09:54:25'),
(4, '123', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 3', NULL, '2025-05-31 09:55:43'),
(5, '123', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 3', NULL, '2025-05-31 09:56:42'),
(6, '123', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 3', NULL, '2025-05-31 09:56:47'),
(7, '123', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 3', NULL, '2025-05-31 09:57:16'),
(8, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 1', NULL, '2025-05-31 09:58:10'),
(9, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 3', NULL, '2025-05-31 09:58:11'),
(10, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 2', NULL, '2025-06-04 10:59:07'),
(11, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 1', NULL, '2025-06-04 11:56:15'),
(12, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 4', NULL, '2025-06-04 11:56:17'),
(13, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 5', NULL, '2025-06-04 11:56:18'),
(14, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 6', NULL, '2025-06-04 11:56:19'),
(15, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 7', NULL, '2025-06-04 11:56:20'),
(16, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 1', NULL, '2025-06-04 11:56:46'),
(17, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 2', NULL, '2025-06-04 11:56:49'),
(18, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 3', NULL, '2025-06-04 11:56:50'),
(19, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 1', NULL, '2025-06-04 13:32:07'),
(20, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 4', NULL, '2025-06-04 13:32:09'),
(21, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 1111, requirement 5', NULL, '2025-06-04 13:32:10'),
(22, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02243, requirement 4', NULL, '2025-06-05 16:22:19'),
(23, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02243, requirement 5', NULL, '2025-06-05 16:22:28'),
(24, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02243, requirement 6', NULL, '2025-06-05 16:22:29'),
(25, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02243, requirement 7', NULL, '2025-06-05 16:22:30'),
(26, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02248, requirement 7', NULL, '2025-06-05 16:23:09'),
(27, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02243, requirement 7', NULL, '2025-06-05 16:24:09'),
(28, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 222222, requirement 7', NULL, '2025-06-05 16:24:35'),
(29, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 7', NULL, '2025-06-05 16:24:45'),
(30, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02243, requirement 7', NULL, '2025-06-05 16:25:54'),
(31, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02248, requirement 1', NULL, '2025-06-05 16:26:17'),
(32, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02248, requirement 2', NULL, '2025-06-05 16:26:18'),
(33, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02248, requirement 3', NULL, '2025-06-05 16:26:18'),
(34, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02248, requirement 4', NULL, '2025-06-05 16:26:19'),
(35, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02248, requirement 5', NULL, '2025-06-05 16:26:20'),
(36, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02248, requirement 6', NULL, '2025-06-05 16:26:20'),
(37, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 1', NULL, '2025-06-05 16:26:33'),
(38, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 2', NULL, '2025-06-05 16:26:34'),
(39, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 3', NULL, '2025-06-05 16:26:34'),
(40, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 4', NULL, '2025-06-05 16:26:35'),
(41, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 5', NULL, '2025-06-05 16:26:35'),
(42, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 6', NULL, '2025-06-05 16:26:36'),
(43, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02247, requirement 7', NULL, '2025-06-05 16:26:36'),
(44, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 1', NULL, '2025-06-05 16:26:50'),
(45, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 2', NULL, '2025-06-05 16:26:51'),
(46, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 3', NULL, '2025-06-05 16:26:51'),
(47, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 4', NULL, '2025-06-05 16:26:52'),
(48, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 5', NULL, '2025-06-05 16:26:52'),
(49, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 222222, requirement 6', NULL, '2025-06-05 16:26:53'),
(50, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 2022-02243, requirement 1', NULL, '2025-06-05 16:33:29'),
(51, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 1', NULL, '2025-06-06 08:58:59'),
(52, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 2', NULL, '2025-06-06 08:59:18'),
(53, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 3', NULL, '2025-06-06 08:59:19'),
(54, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 4', NULL, '2025-06-06 08:59:19'),
(55, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 5', NULL, '2025-06-06 08:59:20'),
(56, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 6', NULL, '2025-06-06 08:59:20'),
(57, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 0133, requirement 7', NULL, '2025-06-06 08:59:21'),
(58, '1', 'Staff', 'Updated Clearance Status', 'Pending clearance for student 0133, requirement 1', NULL, '2025-06-06 08:59:29'),
(59, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 1', NULL, '2025-06-06 09:03:30'),
(60, '123', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02241, requirement 3', NULL, '2025-06-07 05:54:53'),
(61, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 2022-02241, requirement 1', NULL, '2025-06-07 06:09:43'),
(62, '1', 'Staff', 'Approved Clearance', 'Approved clearance for student 1111, requirement 2', NULL, '2025-06-09 07:30:53');

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
(7, 'Student Council Clearance', 'Complete student organization obligations', 1, 7);

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
(7, 'Student Council');

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
(9, '12312', 'arciagamarkjoshua2@gmail.com', 'mark', 'arcy', 'dean', 'BSCS', 2, '2c', '2024-2025', 'Second Semester', '$2y$10$Dn76XjcjIUL0f.y.yLJnxeHaLTogdk37FaA7D1/qZroijKYjvs9AC', 'Pending', '2025-06-11 17:55:48', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `SectionCode` varchar(50) NOT NULL,
  `SectionTitle` varchar(255) NOT NULL,
  `YearLevel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`SectionCode`, `SectionTitle`, `YearLevel`) VALUES
('1a', '1A', 1),
('1b', '1B', 1),
('1c', '1C', 1),
('1d', '1D', 1),
('2a', '2A', 2),
('2b', '2B', 2),
('2c', '2C', 2),
('2d', '2D', 2),
('3a', '3A', 3),
('3b', '3B', 3),
('3c', '3C', 3),
('3d', '3D', 3),
('4a', '4A', 4),
('4b', '4B', 4),
('4c', '4C', 4),
('4d', '4D', 4),
('5a', '5A', 5),
('5b', '5B', 5),
('5c', '5C', 5),
('5d', '5D', 5);

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
('2025-001', 1, 'admin', 'admin@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'ADMIN', 'Admin', 'Guidance Office', 'Admin', NULL, 1);

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
('2025-0001', '123213', '123213', 'arciagamarkjoshua@gmail.com', 'password', 'mark', 'arcy', 'dean', 'BSCS', 1, '1c', '2024-2025', 'Second Semester', 'Student', NULL, 1);

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
(6, 'CBA Student Council', 7, 'Student Council for College of Business Administration');

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`OfficeID`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `OfficeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD KEY `YearLevel` (`YearLevel`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `clearance`
--
ALTER TABLE `clearance`
  MODIFY `clearance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `clearance_requirements`
--
ALTER TABLE `clearance_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`--
ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT for table `registration_requests`
--
ALTER TABLE `registration_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_clearance_status`
--
ALTER TABLE `student_clearance_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

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
-- Constraints for table `registration_requests`
--
ALTER TABLE `registration_requests`
  ADD CONSTRAINT `registration_requests_ibfk_1` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`StaffID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`YearLevel`) REFERENCES `levels` (`LevelID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`ProgramCode`) REFERENCES `programs` (`ProgramCode`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`Level`) REFERENCES `levels` (`LevelID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`SectionCode`) REFERENCES `sections` (`SectionCode`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_clearance_status`
--
ALTER TABLE `student_clearance_status`
  ADD CONSTRAINT `student_clearance_status_ibfk_1` FOREIGN KEY (`studentNo`) REFERENCES `students` (`studentNo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_clearance_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `clearance_requirements` (`requirement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_clearance_status_ibfk_3` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`StaffID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_requirement_descriptions`
--
ALTER TABLE `student_requirement_descriptions`
  ADD CONSTRAINT `student_requirement_descriptions_ibfk_1` FOREIGN KEY (`studentNo`) REFERENCES `students` (`studentNo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_requirement_descriptions_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `clearance_requirements` (`requirement_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

