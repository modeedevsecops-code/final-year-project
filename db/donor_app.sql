-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 24, 2026 at 10:52 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `donor_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL COMMENT 'FK → staff.staff_id',
  `student_id` int(11) NOT NULL COMMENT 'FK → students.student_id',
  `project_id` int(11) NOT NULL COMMENT 'FK → projects.project_id',
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') DEFAULT 'Absent',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Donor check-in / attendance at blood drives';

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE `blood_requests` (
  `request_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `blood_group` varchar(10) NOT NULL,
  `units_needed` int(11) NOT NULL DEFAULT 1,
  `hospital_name` varchar(150) NOT NULL,
  `location` varchar(255) NOT NULL,
  `urgency_level` enum('Normal','Urgent','Critical Emergency') NOT NULL DEFAULT 'Urgent',
  `status` enum('Pending','Approved','Fulfilled','Cancelled') NOT NULL DEFAULT 'Pending',
  `requested_by` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`request_id`, `patient_name`, `blood_group`, `units_needed`, `hospital_name`, `location`, `urgency_level`, `status`, `requested_by`, `contact_phone`, `created_at`) VALUES
(1, 'Amina Bello', 'O+', 2, 'Aminu Kano Teaching Hospital', 'Zaria Road, Kano', 'Critical Emergency', 'Pending', 'Dr. Hassan', '08031234567', '2026-07-24 08:00:58'),
(2, 'John Danladi', 'B-', 1, 'Murtala Muhammad Specialist Hospital', 'Kano City Center', 'Urgent', 'Approved', 'Nurse Mary', '08029876543', '2026-07-24 08:00:58'),
(3, 'Kabiru Sani', 'AB+', 3, 'BUK Medical Centre', 'New Campus, Kano', 'Normal', 'Fulfilled', 'Dr. Aliyu', '08051112233', '2026-07-24 08:00:58'),
(4, 'Yusuf', 'A+', 1, 'ABU Teaching hospital', 'Kaduna', 'Normal', 'Pending', 'Aliyu', '0812749494', '2026-07-24 08:23:34'),
(5, 'Yusuf', 'A+', 1, 'ABU Teaching hospital', 'Kaduna', 'Normal', 'Approved', 'Aliyu', '0812749494', '2026-07-24 08:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL COMMENT 'FK → staff.staff_id',
  `student_id` int(11) NOT NULL COMMENT 'FK → students.student_id',
  `sender` enum('supervisor','student') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Chat messages between hospital officers and donors';

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `supervisor_id`, `student_id`, `sender`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'supervisor', 'hey', 1, '2026-07-24 08:46:30'),
(2, 1, 1, 'student', 'How are u Sir', 0, '2026-07-24 08:47:46');

-- --------------------------------------------------------

--
-- Table structure for table `logbook_entries`
--

CREATE TABLE `logbook_entries` (
  `log_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `activities` text NOT NULL,
  `supervisor_comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logbook_entries`
--

INSERT INTO `logbook_entries` (`log_id`, `student_id`, `entry_date`, `activities`, `supervisor_comment`, `status`, `created_at`) VALUES
(1, 1, '2026-02-20', 'I will donate', 'Approved. Healthy donation details.', 'approved', '2026-07-24 08:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `user_id` int(11) NOT NULL,
  `USERNAME` varchar(20) NOT NULL,
  `PASSWORD` varchar(20) NOT NULL,
  `PW_STATUS` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`user_id`, `USERNAME`, `PASSWORD`, `PW_STATUS`) VALUES
(1, 'admin', 'admin', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `notice_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`notice_id`, `supervisor_id`, `title`, `message`, `created_at`) VALUES
(1, 1, 'Hello', 'We need donor', '2026-07-24 08:06:41');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Hospital / Blood Bank name & location',
  `assigned_student` int(11) NOT NULL COMMENT 'FK → students.student_id (Donor)',
  `assigned_supervisor` int(11) NOT NULL COMMENT 'FK → staff.staff_id (Hospital Officer)',
  `status` varchar(50) NOT NULL DEFAULT 'Pending' COMMENT 'Pending | In Progress | Completed',
  `methodology` varchar(255) DEFAULT NULL COMMENT 'Donation schedule / frequency',
  `description` text DEFAULT NULL COMMENT 'Hospital address / extra notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Donor-to-Officer assignments (replaces SIWES projects)';

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `title`, `assigned_student`, `assigned_supervisor`, `status`, `methodology`, `description`, `created_at`) VALUES
(1, 'Dantsoho', 1, 1, 'In Progress', '1 month', 'We need it now', '2026-07-24 08:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `seminar`
--

CREATE TABLE `seminar` (
  `seminar_id` int(11) NOT NULL,
  `seminar_title` varchar(255) NOT NULL COMMENT 'Blood drive / event title',
  `seminar_date` date NOT NULL,
  `seminar_time` time NOT NULL,
  `venue` varchar(255) NOT NULL COMMENT 'Hospital / blood bank location',
  `level` varchar(100) DEFAULT NULL COMMENT 'Blood group target / eligibility',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Blood donation drives / events (renamed from seminar)';

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `staff_name` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `date_registered` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `staff_name`, `phone`, `email`, `position`, `password`, `date_registered`) VALUES
(1, 'MAryam', '08127494994', 'cryptonewbies09@gmail.com', 'Admin – Barau Dikko', '123456', '2026-07-24 08:18:48');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `reg_no` varchar(100) NOT NULL,
  `year_of_study` varchar(50) NOT NULL,
  `status` text DEFAULT NULL,
  `password` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `name`, `email`, `phone`, `reg_no`, `year_of_study`, `status`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Zainab auwal', 'zainab@gmail.com', '092387267', '487278', 'A+', NULL, '12345', '2026-07-24 08:03:22', '2026-07-24 08:16:05'),
(2, 'Kamalu yahaya', 'ksofttechnova@gmail.com', '08127494994', '487278', 'A+', NULL, '12345', '2026-07-24 08:15:36', '2026-07-24 08:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_summaries`
--

CREATE TABLE `weekly_summaries` (
  `summary_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'FK → students.student_id',
  `week_start` date NOT NULL COMMENT 'Donation period start',
  `week_end` date NOT NULL COMMENT 'Donation period end',
  `summary` text NOT NULL COMMENT 'Donation period notes / summary',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `supervisor_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Donor donation period / summary reports';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`);

--
-- Indexes for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `logbook_entries`
--
ALTER TABLE `logbook_entries`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`notice_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `fk_project_student` (`assigned_student`),
  ADD KEY `fk_project_supervisor` (`assigned_supervisor`);

--
-- Indexes for table `seminar`
--
ALTER TABLE `seminar`
  ADD PRIMARY KEY (`seminar_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weekly_summaries`
--
ALTER TABLE `weekly_summaries`
  ADD PRIMARY KEY (`summary_id`),
  ADD KEY `fk_summary_student` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_requests`
--
ALTER TABLE `blood_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `logbook_entries`
--
ALTER TABLE `logbook_entries`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `notice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seminar`
--
ALTER TABLE `seminar`
  MODIFY `seminar_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `weekly_summaries`
--
ALTER TABLE `weekly_summaries`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_project_student` FOREIGN KEY (`assigned_student`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_project_supervisor` FOREIGN KEY (`assigned_supervisor`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_summaries`
--
ALTER TABLE `weekly_summaries`
  ADD CONSTRAINT `fk_summary_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
