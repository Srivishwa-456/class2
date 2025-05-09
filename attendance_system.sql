-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 30, 2025 at 11:30 AM
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
-- Database: `attendance_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'vishwa@gmail.com', '1234', '2025-04-25 04:47:45', '2025-04-25 04:55:09'),
(2, 'vikas@gmail.com', '1234', '2025-04-25 04:47:45', '2025-04-25 04:55:14');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_slot` varchar(30) NOT NULL,
  `status` enum('present','absent') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `course_id`, `date`, `time_slot`, `status`, `marked_by`, `created_at`, `updated_at`) VALUES
(49, 1, 1, '2025-02-02', '09:00 TO 10:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-26 02:04:12'),
(50, 1, 1, '2025-02-09', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(51, 1, 1, '2025-02-16', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(52, 1, 1, '2025-02-23', '09:00 TO 10:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(53, 1, 1, '2025-03-02', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(54, 1, 1, '2025-03-09', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(55, 1, 1, '2025-03-16', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(56, 1, 1, '2025-03-23', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(57, 1, 2, '2025-02-03', '11:00 TO 12:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(58, 1, 2, '2025-02-10', '11:00 TO 12:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(59, 1, 2, '2025-02-17', '11:00 TO 12:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(60, 1, 2, '2025-02-24', '11:00 TO 12:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(61, 1, 2, '2025-03-03', '11:00 TO 12:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(62, 1, 2, '2025-03-10', '11:00 TO 12:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(63, 1, 2, '2025-03-17', '11:00 TO 12:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(64, 1, 2, '2025-03-24', '11:00 TO 12:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(65, 1, 3, '2025-02-04', '13:00 TO 14:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(66, 1, 3, '2025-02-11', '13:00 TO 14:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(67, 1, 3, '2025-02-18', '13:00 TO 14:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(68, 1, 3, '2025-02-25', '13:00 TO 14:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(69, 1, 3, '2025-03-04', '13:00 TO 14:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(70, 1, 3, '2025-03-11', '13:00 TO 14:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(71, 1, 3, '2025-03-18', '13:00 TO 14:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(72, 1, 3, '2025-03-25', '13:00 TO 14:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(73, 1, 4, '2025-02-05', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(74, 1, 4, '2025-02-12', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(75, 1, 4, '2025-02-19', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(76, 1, 4, '2025-02-26', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(77, 1, 4, '2025-03-05', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(78, 1, 4, '2025-03-12', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(79, 1, 4, '2025-03-19', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(80, 1, 4, '2025-03-26', '15:00 TO 16:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(81, 2, 1, '2025-02-02', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(82, 2, 1, '2025-02-09', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(83, 2, 1, '2025-02-16', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(84, 2, 1, '2025-02-23', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(85, 2, 1, '2025-03-02', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(86, 2, 1, '2025-03-09', '09:00 TO 10:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(87, 2, 1, '2025-03-16', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(88, 2, 1, '2025-03-23', '09:00 TO 10:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(89, 2, 5, '2025-02-04', '10:00 TO 11:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(90, 2, 5, '2025-02-11', '10:00 TO 11:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(91, 2, 5, '2025-02-18', '10:00 TO 11:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(92, 2, 5, '2025-02-25', '10:00 TO 11:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(93, 2, 5, '2025-03-04', '10:00 TO 11:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(94, 2, 5, '2025-03-11', '10:00 TO 11:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(95, 2, 5, '2025-03-18', '10:00 TO 11:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(96, 2, 5, '2025-03-25', '10:00 TO 11:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(97, 2, 6, '2025-02-06', '14:00 TO 15:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(98, 2, 6, '2025-02-13', '14:00 TO 15:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(99, 2, 6, '2025-02-20', '14:00 TO 15:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(100, 2, 6, '2025-02-27', '14:00 TO 15:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(101, 2, 6, '2025-03-06', '14:00 TO 15:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(102, 2, 6, '2025-03-13', '14:00 TO 15:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(103, 2, 6, '2025-03-20', '14:00 TO 15:00', 'present', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46'),
(104, 2, 6, '2025-03-27', '14:00 TO 15:00', 'absent', 1, '2025-04-25 17:02:46', '2025-04-25 17:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `semester`, `created_at`, `updated_at`) VALUES
(1, '22CSE161', 'Data Structures', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59'),
(2, '22CSE162', 'Database Management', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59'),
(3, '22CSE163', 'Operating Systems', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59'),
(4, '22CSE164', 'Web Development', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59'),
(5, '22CSE168', 'Artificial Intelligence', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59'),
(6, '22CSE1653', 'Computer Networks', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59'),
(7, '22MEC1674', 'Python Programming', 'Spring 2025', '2025-04-25 15:25:59', '2025-04-25 15:25:59');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `usn` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `dob` date NOT NULL,
  `class` varchar(50) NOT NULL,
  `section` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `full_name`, `usn`, `email`, `phone`, `dob`, `class`, `section`, `created_at`, `updated_at`) VALUES
(1, 'vikas', '1BG22CS180', NULL, NULL, '2004-01-01', 'Computer Science', 'A', '2025-04-25 04:53:54', '2025-04-25 04:53:54'),
(2, 'vishwanath', '1BG22CS187', NULL, NULL, '2004-01-01', 'Computer Science', 'B', '2025-04-25 04:53:54', '2025-04-25 04:53:54');

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date_enrolled` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`id`, `student_id`, `course_id`, `date_enrolled`, `created_at`) VALUES
(1, 1, 1, '2025-04-25', '2025-04-25 15:25:59'),
(2, 1, 2, '2025-04-25', '2025-04-25 15:25:59'),
(3, 1, 3, '2025-04-25', '2025-04-25 15:25:59'),
(4, 1, 4, '2025-04-25', '2025-04-25 15:25:59'),
(5, 2, 1, '2025-04-25', '2025-04-25 15:25:59'),
(6, 2, 2, '2025-04-25', '2025-04-25 15:25:59'),
(7, 2, 5, '2025-04-25', '2025-04-25 15:25:59'),
(8, 2, 6, '2025-04-25', '2025-04-25 15:25:59'),
(9, 1, 6, '2025-04-25', '2025-04-25 17:38:31'),
(10, 1, 5, '2025-04-25', '2025-04-25 17:38:54'),
(11, 1, 7, '2025-04-25', '2025-04-25 17:38:59'),
(12, 2, 3, NULL, '2025-04-26 03:21:54');

-- --------------------------------------------------------

--
-- Table structure for table `subject_marks`
--

CREATE TABLE `subject_marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_type` enum('internal','midterm','final') NOT NULL,
  `marks_obtained` float NOT NULL,
  `max_marks` float NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_marks`
--

INSERT INTO `subject_marks` (`id`, `student_id`, `course_id`, `exam_type`, `marks_obtained`, `max_marks`, `remarks`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'internal', 80, 100, '', 1, '2025-04-30 08:54:18', '2025-04-30 08:54:18'),
(2, 2, 1, 'internal', 90, 100, '', 1, '2025-04-30 08:54:18', '2025-04-30 08:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `full_name`, `email`, `password`, `employee_id`, `department`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'john.smith@example.com', '1234', 'EMP001', 'Computer Science', '2025-04-25 17:49:39', '2025-04-25 17:49:39'),
(2, 'Sarah Johnson', 'sarah.johnson@example.com', '1234', 'EMP002', 'Information Technology', '2025-04-25 17:49:39', '2025-04-25 17:49:39'),
(3, 'Michael Brown', 'michael.brown@example.com', '1234', 'EMP003', 'Engineering', '2025-04-25 17:49:39', '2025-04-25 17:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_courses`
--

CREATE TABLE `teacher_courses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_courses`
--

INSERT INTO `teacher_courses` (`id`, `teacher_id`, `course_id`, `created_at`) VALUES
(1, 1, 1, '2025-04-25 17:49:39'),
(2, 1, 2, '2025-04-25 17:49:39'),
(3, 2, 3, '2025-04-25 17:49:39'),
(4, 2, 4, '2025-04-25 17:49:39'),
(5, 3, 5, '2025-04-25 17:49:39'),
(6, 3, 6, '2025-04-25 17:49:39'),
(7, 3, 7, '2025-04-25 17:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '1234', 'admin', '2025-04-25 17:02:21', '2025-04-25 17:02:21'),
(2, 'teacher1@gmail.com', '1234', 'teacher', '2025-04-25 17:02:21', '2025-04-25 17:41:21'),
(3, 'student1', '1234', 'student', '2025-04-25 17:02:21', '2025-04-25 17:02:21'),
(4, 'student2', '1234', 'student', '2025-04-25 17:02:21', '2025-04-25 17:02:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usn` (`usn`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_course_unique` (`student_id`,`course_id`),
  ADD KEY `student_courses_ibfk_2` (`course_id`);

--
-- Indexes for table `subject_marks`
--
ALTER TABLE `subject_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `marked_by` (`added_by`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_course_unique` (`teacher_id`,`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `subject_marks`
--
ALTER TABLE `subject_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subject_marks`
--
ALTER TABLE `subject_marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `teachers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
