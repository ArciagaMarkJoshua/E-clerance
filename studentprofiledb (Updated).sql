-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2025 at 07:12 PM
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
('AY2023', '2023-2024', '2023-06-01', '2024-03-31');

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
(1, '123', 'Pending', '2025-03-24 16:02:18');

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
('S2', 'Second Semester');

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
(2, 222, 'Mark2', 'arciagamarkjoshua@gmail.com', '$2y$10$Dsi2u1NPGnkMuu/BEVIuyOFWdvnowdtOyK8cXg9DxZOxDw/cP/z7W', 'Arciaga2', 'Mark2', 'Perico2', 'College Library', 'Admin'),
(4, 1, 'admin', 'admin@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'DYCI', 'ssss', 'Guidance Office', 'Admin'),
(5, 22, 'Mark', 'arciagamarkjoshua@gmail.com2', '$2y$10$i0iMRCfrVsv2eXZBMPktru8PlihZ2ftYASOY/RvVBFTfb6Se8sIa6', 'Arciaga', 'Mark', 'Perico', 'College Library', 'Staff'),
(6, 101, 'admin1', 'admin1@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'DYCI', NULL, 'Administration', 'Admin'),
(7, 102, 'staff1', 'staff1@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Smith', 'John', 'D.', 'College Library', 'Staff'),
(8, 103, 'staff2', 'staff2@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doe', 'Jane', 'E.', 'Office of the Registrar', 'Staff');

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
  `AccountType` varchar(20) NOT NULL DEFAULT 'Student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`CtrlNo`, `studentNo`, `Username`, `Email`, `PasswordHash`, `LastName`, `FirstName`, `Mname`, `ProgramCode`, `Level`, `SectionCode`, `AccountType`) VALUES
(2, '1233', 'Mark31s', 'mark@gmail.coms', '$2y$10$PSlPgaChhOc2EkmvWfaNAe.HByhk32wlvZkh./xWiaDMR/mzC2I6C', 'Arciagas', 'Mark Joshuas', 'Pericos', 'BSCS', 0, '', 'Student'),
(3, '1231232', 'aass', 'mark@gmail.coms22', '$2y$10$HQg01vB8xTR4Vn/6rVENjO1mNlJTHghU8FVLWUhJYqDlCRBTgeWbO', 'aaasw', 'aaass', 'aaasw', 'BSIT', 1, '1a', 'Student'),
(4, '123', 'Mark31', 'mark@gmail.com', '$2y$10$ePf2usuASURDo0MMFM.gPOZihHaqcyUy8msa4odyZQAZ.4mFqa5pe', 'Arciaga', 'Mark Joshua', 'Perico', 'BSIT', 4, '4d', 'Student'),
(5, '2023001', 'john_doe', 'john.doe@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doe', 'John', 'A.', 'BSIT', 1, '1a', 'Student'),
(6, '2023002', 'jane_smith', 'jane.smith@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Smith', 'Jane', 'B.', 'BSCS', 2, '2b', 'Student'),
(7, '2023003', 'alice_wong', 'alice.wong@dyci.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Wong', 'Alice', 'C.', 'BSIS', 3, '3c', 'Student');

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
('123', 1, 1, 'Approved', NULL, NULL, '2025-03-24 16:02:18'),
('123', 2, 1, 'Pending', NULL, NULL, '2025-03-24 16:02:18'),
('123', 3, 1, 'Pending', NULL, NULL, '2025-03-24 16:02:18'),
('123', 4, 1, 'Approved', NULL, NULL, '2025-03-24 16:02:18'),
('123', 5, 1, 'Pending', NULL, NULL, '2025-03-24 16:02:18'),
('123', 6, 1, 'Pending', NULL, NULL, '2025-03-24 16:02:18'),
('123', 7, 1, 'Pending', NULL, NULL, '2025-03-24 16:02:18');

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
  MODIFY `clearance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `CtrlNo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `CtrlNo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
