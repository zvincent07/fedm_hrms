-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 16, 2025 at 03:45 PM
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
-- Database: `fedm_hrms`
--
CREATE DATABASE IF NOT EXISTS `fedm_hrms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fedm_hrms`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `log_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `module` varchar(64) NOT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(64) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `module`, `action`, `target_type`, `target_id`, `details`, `created_at`) VALUES
(1, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:07'),
(2, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:11'),
(3, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:31'),
(4, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:36'),
(5, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:37'),
(6, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:40'),
(7, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:41'),
(8, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:44'),
(9, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:45'),
(10, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:48'),
(11, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:49'),
(12, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:52'),
(13, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:53'),
(14, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:55'),
(15, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:07:56'),
(16, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:07:59'),
(17, 3, 'User Management', 'edit_user', 'user_account', 4, 'Old: {\"user_id\":\"4\",\"email\":\"fakename@gmail.com\",\"password\":\"$2y$10$7WdQfH4j9fwwXSGwrAdmaOsTe1DvFqAWTQ8egg0UceS32wrFZzVeu\",\"full_name\":\"fakename@gmail.com\",\"role_id\":\"2\",\"created_at\":\"2025-05-08 17:29:32\",\"updated_at\":\"2025-05-09 00:32:15\",\"job_role_id\":\"14\",\"department_id\":\"6\"}; New: {\"full_name\":\"fakename@gmail.com\",\"email\":\"fakename@gmail.com\",\"role_id\":\"2\",\"department_id\":\"6\",\"job_role_id\":\"8\"}', '2025-05-09 14:24:50'),
(18, 3, 'User Management', 'edit_user', 'user_account', 23, 'Old: {\"user_id\":\"23\",\"email\":\"asdasdasd@gmail.com\",\"password\":\"$2y$10$Z0k6hkECPi0RTSLRZn\\/9\\/eVRLZ9SMm8gvU8yK2e1pTQZ8eTKncO6S\",\"full_name\":\"asdasdasd@gmail.com\",\"role_id\":\"1\",\"created_at\":\"2025-05-09 09:59:15\",\"updated_at\":\"2025-05-09 15:47:21\",\"job_role_id\":\"5\",\"department_id\":\"6\"}; New: {\"full_name\":\"asdasdasd@gmail.com\",\"email\":\"asdasdasd@gmail.com\",\"role_id\":\"1\",\"department_id\":\"6\",\"job_role_id\":\"4\"}', '2025-05-09 14:26:43'),
(19, 3, 'User Management', 'delete_user', 'user_account', 23, 'Deleted user_id: 23', '2025-05-09 14:27:24'),
(20, 3, 'User Management', 'delete_user', 'user_account', 24, 'Deleted user_id: 24', '2025-05-09 14:27:59'),
(21, 3, 'User Management', 'delete_user', 'user_account', 25, 'Deleted user_id: 25', '2025-05-09 14:28:00'),
(22, 3, 'User Management', 'delete_user', 'user_account', 26, 'Deleted user_id: 26', '2025-05-09 14:28:03'),
(23, 3, 'User Management', 'delete_user', 'user_account', 28, 'Deleted user_id: 28', '2025-05-09 14:28:04'),
(24, 3, 'User Management', 'delete_user', 'user_account', 29, 'Deleted user_id: 29', '2025-05-09 14:28:04'),
(25, 3, 'User Management', 'delete_user', 'user_account', 30, 'Deleted user_id: 30', '2025-05-09 14:28:04'),
(26, 3, 'User Management', 'delete_user', 'user_account', 31, 'Deleted user_id: 31', '2025-05-09 14:28:05'),
(27, 3, 'User Management', 'delete_user', 'user_account', 32, 'Deleted user_id: 32', '2025-05-09 14:28:05'),
(28, 3, 'User Management', 'delete_user', 'user_account', 37, 'Deleted user_id: 37', '2025-05-09 14:28:06'),
(29, 3, 'User Management', 'delete_user', 'user_account', 38, 'Deleted user_id: 38', '2025-05-09 14:28:06'),
(30, 3, 'User Management', 'delete_user', 'user_account', 39, 'Deleted user_id: 39', '2025-05-09 14:28:06'),
(31, 3, 'User Management', 'delete_user', 'user_account', 40, 'Deleted user_id: 40', '2025-05-09 14:28:07'),
(32, 3, 'User Management', 'delete_user', 'user_account', 41, 'Deleted user_id: 41', '2025-05-09 14:28:07'),
(33, 3, 'User Management', 'delete_user', 'user_account', 42, 'Deleted user_id: 42', '2025-05-09 14:28:08'),
(34, 3, 'User Management', 'delete_user', 'user_account', 43, 'Deleted user_id: 43', '2025-05-09 14:28:08'),
(35, 3, 'User Management', 'delete_user', 'user_account', 44, 'Deleted user_id: 44', '2025-05-09 14:28:08'),
(36, 3, 'User Management', 'delete_user', 'user_account', 45, 'Deleted user_id: 45', '2025-05-09 14:28:09'),
(37, 3, 'User Management', 'delete_user', 'user_account', 46, 'Deleted user_id: 46', '2025-05-09 14:28:09'),
(38, 3, 'User Management', 'delete_user', 'user_account', 47, 'Deleted user_id: 47', '2025-05-09 14:28:10'),
(39, 3, 'User Management', 'delete_user', 'user_account', 48, 'Deleted user_id: 48', '2025-05-09 14:28:11'),
(40, 3, 'User Management', 'delete_user', 'user_account', 49, 'Deleted user_id: 49', '2025-05-09 14:28:11'),
(41, 3, 'User Management', 'delete_user', 'user_account', 50, 'Deleted user_id: 50', '2025-05-09 14:28:11'),
(42, 3, 'User Management', 'delete_user', 'user_account', 51, 'Deleted user_id: 51', '2025-05-09 14:28:12'),
(43, 3, 'User Management', 'delete_user', 'user_account', 52, 'Deleted user_id: 52', '2025-05-09 14:28:12'),
(44, 3, 'User Management', 'delete_user', 'user_account', 54, 'Deleted user_id: 54', '2025-05-09 14:28:13'),
(45, 3, 'User Management', 'delete_user', 'user_account', 55, 'Deleted user_id: 55', '2025-05-09 14:28:15'),
(46, 3, 'User Management', 'edit_user', 'user_account', 3, 'Old: {\"user_id\":\"3\",\"email\":\"qwerty@gmail.com\",\"password\":\"$2y$10$e1CwUwhh4ZWzy51babR\\/3uRMZafEFcglsWAlR7bAgeuLHJ7nlSQFe\",\"full_name\":\"qwerty@gmail.com\",\"role_id\":\"1\",\"created_at\":\"2025-05-08 16:58:34\",\"updated_at\":\"2025-05-09 12:39:14\",\"job_role_id\":null,\"department_id\":\"6\"}; New: {\"full_name\":\"qwerty@gmail.com\",\"email\":\"qwerty@gmail.com\",\"role_id\":\"1\",\"department_id\":null,\"job_role_id\":null}', '2025-05-09 14:29:02'),
(47, 3, 'User Management', 'edit_user', 'user_account', 4, 'Old: {\"user_id\":\"4\",\"email\":\"fakename@gmail.com\",\"password\":\"$2y$10$7WdQfH4j9fwwXSGwrAdmaOsTe1DvFqAWTQ8egg0UceS32wrFZzVeu\",\"full_name\":\"fakename@gmail.com\",\"role_id\":\"2\",\"created_at\":\"2025-05-08 17:29:32\",\"updated_at\":\"2025-05-09 22:24:50\",\"job_role_id\":\"8\",\"department_id\":\"6\"}; New: {\"full_name\":\"fakename@gmail.com\",\"email\":\"fakename@gmail.com\",\"role_id\":\"2\",\"department_id\":null,\"job_role_id\":\"8\"}', '2025-05-09 14:29:08'),
(48, 3, 'User Management', 'edit_user', 'user_account', 4, 'Old: {\"user_id\":\"4\",\"email\":\"fakename@gmail.com\",\"password\":\"$2y$10$7WdQfH4j9fwwXSGwrAdmaOsTe1DvFqAWTQ8egg0UceS32wrFZzVeu\",\"full_name\":\"fakename@gmail.com\",\"role_id\":\"2\",\"created_at\":\"2025-05-08 17:29:32\",\"updated_at\":\"2025-05-09 22:29:08\",\"job_role_id\":\"8\",\"department_id\":null}; New: {\"full_name\":\"fakename@gmail.com\",\"email\":\"fakename@gmail.com\",\"role_id\":\"2\",\"department_id\":null,\"job_role_id\":null}', '2025-05-09 14:29:13'),
(49, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:42:31'),
(50, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:42:35'),
(51, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 14:42:40'),
(52, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 14:42:43'),
(53, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 15:04:01'),
(54, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 15:04:09'),
(55, 3, 'User Management', 'create_account', 'user_account', 57, 'Created user: asdasdasd@gmail.com (asdasdasd@gmail.com)', '2025-05-09 15:47:16'),
(56, 3, 'Notification', 'send_notification', 'notification', 4, 'Sent notification \'adasdasd\' to all departments', '2025-05-09 15:52:40'),
(57, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-09 16:12:45'),
(58, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-09 16:12:49'),
(59, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-10 11:18:37'),
(60, 3, 'User Management', 'create_account', 'user_account', 58, 'Created user: asdadasd@gmai.com (asdadasd@gmai.com)', '2025-05-10 11:24:24'),
(61, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 03:41:27'),
(62, NULL, 'User Management', 'create_account', 'user_account', 59, 'Created user: asdadasdas@gmail.com (asdadasdas@gmail.com)', '2025-05-11 04:10:07'),
(63, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 04:11:24'),
(64, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-11 04:17:50'),
(65, 1, 'Admin', 'logout', 'user_account', 1, 'Admin logged out', '2025-05-11 04:17:54'),
(66, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 04:18:24'),
(67, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-11 04:18:41'),
(68, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 04:18:47'),
(69, 3, 'Notification', 'send_notification', 'notification', 20, 'Sent notification \'adadadasdas\' to departments: Department Managers, HR Department, IT Department, Operations\r\n', '2025-05-11 04:31:34'),
(70, 3, 'Notification', 'send_notification', 'notification', 21, 'Sent notification \'adasddadaas\' to departments: Department Managers, HR Department, IT Department, Operations\r\n', '2025-05-11 04:32:14'),
(71, NULL, 'User Management', 'create_account', 'user_account', 60, 'Created user: asdasdadsad (asdasdadsad@gmail.com)', '2025-05-11 04:33:18'),
(72, NULL, 'User Management', 'create_account', 'user_account', 61, 'Created user: fdgdfgrge@gmail.com (fdgdfgrge@gmail.com)', '2025-05-11 04:33:53'),
(73, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-11 04:37:03'),
(74, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 04:37:08'),
(75, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 04:38:51'),
(76, 3, 'User Management', 'change_password', 'user_account', 3, 'Changed own password', '2025-05-11 04:38:59'),
(77, 3, 'Notification', 'send_notification', 'notification', 31, 'Sent notification titled: ds ghfdhfgjfghjghfj', '2025-05-11 04:51:06'),
(78, 3, 'User Management', 'delete_user', 'user_account', 61, 'Deleted user_id: 61', '2025-05-11 05:07:19'),
(79, 3, 'User Management', 'delete_user', 'user_account', 60, 'Deleted user_id: 60', '2025-05-11 05:07:26'),
(80, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-11 05:13:36'),
(81, 1, 'User Management', 'create_account', 'user_account', 62, 'Created user: adsdadas (adsdadas@gmail.com)', '2025-05-11 05:13:56'),
(82, 3, 'Security', 'csrf_attempt', 'admin_page', NULL, 'CSRF token validation failed', '2025-05-11 05:39:22'),
(83, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-11 05:39:27'),
(84, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 05:39:49'),
(85, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 05:40:22'),
(86, 3, 'User Management', 'create_account', 'user_account', 63, 'Created user: asdasngdfhg@gmai.com (asdasngdfhg@gmai.com)', '2025-05-11 05:40:33'),
(87, 3, 'User Management', 'change_password', 'user_account', 3, 'Changed own password', '2025-05-11 05:41:04'),
(88, 3, 'User Management', 'delete_user', 'user_account', 63, 'Deleted user_id: 63', '2025-05-11 05:42:27'),
(89, 3, 'User Management', 'edit_user', 'user_account', 62, 'Old: {\"user_id\":\"62\",\"email\":\"adsdadas@gmail.com\",\"password\":\"$2y$10$Na1nqibdyCrCYOIik1dvT.bT6hBfalW0\\/VD6cm0rdKGGaT061JOZG\",\"full_name\":\"adsdadas\",\"role_id\":\"2\",\"created_at\":\"2025-05-11 13:13:56\",\"updated_at\":\"2025-05-11 13:13:56\",\"job_role_id\":\"22\",\"department_id\":\"2\"}; New: {\"full_name\":\"adsdadasadasdasasdfsdfsdfsdf\",\"email\":\"adsdadas@gmail.com\",\"role_id\":\"2\",\"department_id\":\"2\",\"job_role_id\":\"22\"}', '2025-05-11 05:42:46'),
(90, 3, 'User Management', 'create_account', 'user_account', 64, 'Created user: John Doe (johndoe@fake.com)', '2025-05-11 05:47:00'),
(91, 3, 'User Management', 'create_account', 'user_account', 65, 'Created user: Jane Doe (janedoe@fake.com)', '2025-05-11 05:47:23'),
(92, 3, 'User Management', 'create_account', 'user_account', 66, 'Created user: Bob Joe (bobjoe@fake.com)', '2025-05-11 05:47:44'),
(93, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-11 06:06:28'),
(94, 3, 'Notification', 'send_notification', 'notification', 32, 'Sent notification titled: asdadaad', '2025-05-11 06:32:21'),
(95, 3, 'Attendance Management', 'view_record', 'attendance', 5, 'Viewed attendance record for John Doe on 2025-05-07', '2025-05-11 06:59:03'),
(96, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from pending to approved', '2025-05-11 06:59:15'),
(97, 3, 'Resignation Management', 'update_status', 'resignation', 1, 'Updated resignation status for Bob Joe from pending to rejected', '2025-05-11 06:59:31'),
(98, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from approved to pending', '2025-05-11 06:59:44'),
(99, 3, 'Resignation Management', 'update_status', 'resignation', 1, 'Updated resignation status for Bob Joe from rejected to pending', '2025-05-11 06:59:52'),
(100, 3, 'Attendance Management', 'update_time_in', 'attendance', 5, 'Updated time in for John Doe on 2025-05-07 from 09:00:00 to 08:00', '2025-05-11 07:12:52'),
(101, 3, 'Attendance Management', 'update_time_in', 'attendance', 5, 'Updated time in for John Doe on 2025-05-07 from 08:00:00 to 09:00', '2025-05-11 07:15:06'),
(102, 3, 'Attendance Management', 'update_attendance', 'attendance', 5, 'Updated attendance for John Doe on 2025-05-07 - Time In: 09:00:00 to 09:00, Status: present to leave', '2025-05-11 07:21:14'),
(103, 3, 'Attendance Management', 'update_attendance', 'attendance', 5, 'Updated attendance for John Doe on 2025-05-07 - Time In: 09:00:00 to 09:00, Status: leave to present', '2025-05-11 07:21:28'),
(104, 3, 'User Management', 'create_account', 'user_account', 67, 'Created user: asdagsgsd@gmail.com (asdagsgsd@gmail.com)', '2025-05-11 07:50:21'),
(105, 3, 'User Management', 'change_password', 'user_account', 3, 'Changed own password', '2025-05-11 07:50:36'),
(106, 3, 'Notification', 'send_notification', 'notification', 33, 'Sent notification titled: asdafafafafafas', '2025-05-11 07:50:49'),
(107, 3, 'User Management', 'delete_user', 'user_account', 67, 'Deleted user_id: 67', '2025-05-11 07:50:57'),
(108, 3, 'Notification', 'send_notification', 'notification', 34, 'Sent notification titled: agsdfgdfgdfgdf', '2025-05-11 07:51:45'),
(109, 3, 'Notification', 'send_notification', 'notification', 35, 'Sent notification titled: saafsfssdafsdfsdf', '2025-05-11 07:52:13'),
(110, 3, 'Notification', 'send_notification', 'notification', 36, 'Sent notification titled: saafsfssdafsdfsdf', '2025-05-11 07:52:15'),
(111, 3, 'Leave Management', 'update_status', 'leave_request', 1, 'Updated leave request status for Jane Doe from pending to approved', '2025-05-11 07:53:02'),
(112, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from pending to approved', '2025-05-11 07:53:04'),
(113, 3, 'Resignation Management', 'update_status', 'resignation', 1, 'Updated resignation status for Bob Joe from pending to approved', '2025-05-11 07:53:13'),
(114, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from approved to pending', '2025-05-11 07:53:27'),
(115, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from pending to pending', '2025-05-11 07:53:29'),
(116, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from pending to pending', '2025-05-11 07:53:30'),
(117, 3, 'Leave Management', 'update_status', 'leave_request', 2, 'Updated leave request status for Jane Doe from pending to pending', '2025-05-11 07:53:33'),
(118, 3, 'Leave Management', 'update_status', 'leave_request', 1, 'Updated leave request status for Jane Doe from approved to pending', '2025-05-11 07:53:35'),
(119, 3, 'Resignation Management', 'update_status', 'resignation', 1, 'Updated resignation status for Bob Joe from approved to pending', '2025-05-11 07:53:48'),
(120, 57, 'User Management', 'create_account', 'user_account', 68, 'Created user: asdf (asd@gmail.com)', '2025-05-11 15:34:59'),
(121, 57, 'User Management', 'create_account', 'user_account', 69, 'Created user: qewew (qewew@gmail.com)', '2025-05-11 15:35:18'),
(122, 57, 'Notification', 'send_notification', 'notification', 37, 'Sent notification titled: fsfsf', '2025-05-12 11:43:15'),
(123, 57, 'Notification', 'send_notification', 'notification', 38, 'Sent notification titled: Hello', '2025-05-12 11:44:31'),
(124, 57, 'Notification', 'send_notification', 'notification', 39, 'Sent notification titled: Testing muna', '2025-05-12 11:46:22'),
(125, 57, 'Notification', 'send_notification', 'notification', 40, 'Sent notification titled: hagdhjagjhsdgahjsd', '2025-05-12 11:47:17'),
(126, 69, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-12 12:41:50'),
(127, 62, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-12 12:48:52'),
(128, 59, 'User Management', 'create_account', 'user_account', 70, 'Created user: Glycel Yvon Virtuci (gly@gmail.com)', '2025-05-12 23:48:49'),
(129, 59, 'Admin', 'login', 'user_account', 59, 'User logged in: asdadasdas@gmail.com (asdadasdas@gmail.com)', '2025-05-12 23:55:22'),
(130, 59, 'Admin', 'logout', 'user_account', 59, 'Admin logged out', '2025-05-12 23:56:07'),
(131, 69, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-13 00:07:49'),
(132, 69, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-13 00:07:59'),
(133, 58, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-13 04:33:50'),
(134, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-13 04:36:05'),
(135, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-13 05:02:20'),
(136, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-13 05:02:26'),
(137, 3, 'User Management', 'create_account', 'user_account', 71, 'Created user: manager@gmail.com (manager@gmail.com)', '2025-05-13 05:02:46'),
(138, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-13 05:02:47'),
(139, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-13 06:03:56'),
(140, 64, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-13 06:06:19'),
(141, 3, 'User Management', 'delete_user', 'user_account', 4, 'Deleted user_id: 4', '2025-05-13 06:18:52'),
(142, 3, 'Resignation Management', 'update_status', 'resignation', 18, 'Updated resignation status for asdadasdas@gmail.com from cancelled to pending', '2025-05-13 06:53:49'),
(143, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 03:54:06'),
(144, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 03:54:35'),
(145, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 04:33:36'),
(146, 3, 'User Management', 'create_account', 'user_account', 72, 'Created user: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 04:33:57'),
(147, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 04:34:01'),
(148, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:34:38'),
(149, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:34:43'),
(150, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:34:46'),
(151, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:35:43'),
(152, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:36:02'),
(153, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 04:38:24'),
(154, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 04:38:26'),
(155, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:38:36'),
(156, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:38:40'),
(157, 72, 'Admin', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 04:40:06'),
(158, 72, 'Security', 'unauthorized_access', 'admin_page', NULL, 'Unauthorized access attempt to admin page', '2025-05-14 04:51:34'),
(159, 72, 'Admin', 'logout', 'user_account', 72, 'Admin logged out', '2025-05-14 04:54:45'),
(160, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 04:58:47'),
(161, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 04:59:11'),
(162, 72, 'Admin', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 04:59:17'),
(163, 72, 'Admin', 'logout', 'user_account', 72, 'Admin logged out', '2025-05-14 05:03:29'),
(164, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 05:03:37'),
(165, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 05:03:39'),
(166, 72, 'Admin', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:03:44'),
(167, 72, 'Admin', 'logout', 'user_account', 72, 'Admin logged out', '2025-05-14 05:04:09'),
(168, 72, 'Admin', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:04:15'),
(169, 72, 'Admin', 'logout', 'user_account', 72, 'Admin logged out', '2025-05-14 05:05:13'),
(170, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 05:05:20'),
(171, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 05:05:27'),
(172, 72, 'Admin', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:06:35'),
(173, 72, 'Admin', 'logout', 'user_account', 72, 'Admin logged out', '2025-05-14 05:25:03'),
(174, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 05:25:11'),
(175, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 05:25:16'),
(176, 72, 'Admin', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:25:21'),
(177, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-14 05:32:06'),
(178, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:32:35'),
(179, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-14 05:32:42'),
(180, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 05:32:46'),
(181, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 05:32:56'),
(182, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:34:39'),
(183, 72, 'Resignation Management', 'update_status', 'resignation', 19, 'Updated resignation status for Glycel Yvon Virtuci from pending to approved', '2025-05-14 05:36:04'),
(184, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-14 05:36:16'),
(185, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-14 05:37:09'),
(186, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-14 05:37:13'),
(187, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-14 05:37:25'),
(188, 72, 'Resignation Management', 'update_status', 'resignation', 19, 'Updated resignation status for Glycel Yvon Virtuci from approved to pending', '2025-05-14 05:37:33'),
(189, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-15 09:37:04'),
(190, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-15 09:37:17'),
(191, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-15 23:07:19'),
(192, 3, 'User Management', 'create_account', 'user_account', 73, 'Created user: employee1@gmail.com (employee1@gmail.com)', '2025-05-15 23:07:50'),
(193, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-15 23:07:52'),
(194, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-15 23:31:53'),
(195, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-15 23:35:55'),
(196, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-15 23:36:05'),
(197, 3, 'User Management', 'create_account', 'user_account', 74, 'Created user: qwerty1@gmail.com (qwerty1@gmail.com)', '2025-05-15 23:39:08'),
(198, 3, 'User Management', 'change_password', 'user_account', 3, 'Changed own password', '2025-05-15 23:39:25'),
(199, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-15 23:39:49'),
(200, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-15 23:41:13'),
(201, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-15 23:41:27'),
(202, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-15 23:47:29'),
(203, 3, 'User Management', 'create_account', 'user_account', 75, 'Created user: employee2@gmail.com (employee2@gmail.com)', '2025-05-15 23:47:55'),
(204, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-15 23:47:57'),
(205, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-15 23:51:09'),
(206, 3, 'User Management', 'create_account', 'user_account', 76, 'Created user: employee3@gmail.com (employee3@gmail.com)', '2025-05-15 23:51:25'),
(207, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-15 23:51:27'),
(208, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-16 00:08:32'),
(209, 72, 'Resignation Management', 'update_status', 'resignation', 19, 'Updated resignation status for Glycel Yvon Virtuci from rejected to approved', '2025-05-16 00:10:20'),
(210, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-16 00:10:53'),
(211, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-16 00:10:57'),
(212, 3, 'Notification', 'send_notification', 'notification', 42, 'Sent notification titled: asd', '2025-05-16 00:12:48'),
(213, 3, 'User Management', 'create_account', 'user_account', 77, 'Created user: qweq (qweq@gmail.com)', '2025-05-16 00:13:32'),
(214, 3, 'User Management', 'change_password', 'user_account', 3, 'Changed own password', '2025-05-16 00:13:43'),
(215, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-16 00:13:51'),
(216, 3, 'Admin', 'logout', 'user_account', 3, 'Admin logged out', '2025-05-16 12:39:19'),
(217, 72, 'HR', 'login', 'user_account', 72, 'User logged in: qwerty123@gmail.com (qwerty123@gmail.com)', '2025-05-16 12:46:13'),
(218, 72, 'HR', 'logout', 'user_account', 72, 'HR logged out', '2025-05-16 12:51:12'),
(219, 3, 'Admin', 'login', 'user_account', 3, 'User logged in: qwerty@gmail.com (qwerty@gmail.com)', '2025-05-16 13:19:37'),
(220, 3, 'User Management', 'create_account', 'user_account', 78, 'Created user: Glycel Yvon Virtucio (glycel.virtucio@gmail.com)', '2025-05-16 13:21:20'),
(221, 3, 'User Management', 'create_account', 'user_account', 79, 'Created user: John vincent Laylo (vincent@gmail.com)', '2025-05-16 13:22:07'),
(222, 3, 'User Management', 'edit_user', 'user_account', 72, 'Old: {\"user_id\":\"72\",\"email\":\"qwerty123@gmail.com\",\"password\":\"$2y$10$sGBwFOcL9ybOyl8jFqhDNeu7t.6d04PhvoOtPvWVIq6MRwE5ve83S\",\"full_name\":\"qwerty123@gmail.com\",\"role_id\":\"4\",\"created_at\":\"2025-05-14 12:33:57\",\"updated_at\":\"2025-05-14 12:33:57\",\"job_role_id\":\"22\",\"department_id\":\"2\",\"date_of_birth\":null,\"mobile_number\":null,\"gender\":null,\"civil_status\":null,\"address\":null,\"nationality\":null,\"manager_rating\":null,\"employment_type\":\"Full-Time\"}; New: {\"full_name\":\"Human Resource\",\"email\":\"hr@gmail.com\",\"role_id\":\"4\",\"department_id\":\"2\",\"job_role_id\":\"22\"}', '2025-05-16 13:24:47'),
(223, 3, 'User Management', 'change_user_password', 'user_account', 72, 'Changed password for user_id: 72', '2025-05-16 13:24:47'),
(224, 3, 'User Management', 'delete_user', 'user_account', 57, 'Deleted user_id: 57', '2025-05-16 13:24:54'),
(225, 3, 'User Management', 'delete_user', 'user_account', 59, 'Deleted user_id: 59', '2025-05-16 13:25:08'),
(226, 3, 'User Management', 'edit_user', 'user_account', 71, 'Old: {\"user_id\":\"71\",\"email\":\"manager@gmail.com\",\"password\":\"$2y$10$YlRlxh5ONMoMjN1r5PULRuI53kThQTWJXSOqDvO1hjIcRTlj0oJUu\",\"full_name\":\"manager@gmail.com\",\"role_id\":\"3\",\"created_at\":\"2025-05-13 13:02:46\",\"updated_at\":\"2025-05-13 13:02:46\",\"job_role_id\":\"30\",\"department_id\":\"4\",\"date_of_birth\":null,\"mobile_number\":null,\"gender\":null,\"civil_status\":null,\"address\":null,\"nationality\":null,\"manager_rating\":null,\"employment_type\":\"Full-Time\"}; New: {\"full_name\":\"Manager\",\"email\":\"manager@gmail.com\",\"role_id\":\"3\",\"department_id\":\"4\",\"job_role_id\":\"30\"}', '2025-05-16 13:26:51'),
(227, 3, 'User Management', 'edit_user', 'user_account', 71, 'Old: {\"user_id\":\"71\",\"email\":\"manager@gmail.com\",\"password\":\"$2y$10$YlRlxh5ONMoMjN1r5PULRuI53kThQTWJXSOqDvO1hjIcRTlj0oJUu\",\"full_name\":\"Manager\",\"role_id\":\"3\",\"created_at\":\"2025-05-13 13:02:46\",\"updated_at\":\"2025-05-16 21:26:51\",\"job_role_id\":\"30\",\"department_id\":\"4\",\"date_of_birth\":null,\"mobile_number\":null,\"gender\":null,\"civil_status\":null,\"address\":null,\"nationality\":null,\"manager_rating\":null,\"employment_type\":\"Full-Time\"}; New: {\"full_name\":\"Manager\",\"email\":\"manager@gmail.com\",\"role_id\":\"3\",\"department_id\":\"4\",\"job_role_id\":\"30\"}', '2025-05-16 13:27:10'),
(228, 3, 'User Management', 'change_user_password', 'user_account', 71, 'Changed password for user_id: 71', '2025-05-16 13:27:10'),
(229, 3, 'User Management', 'create_account', 'user_account', 80, 'Created user: Jerome Padre (jerome@gmail.com)', '2025-05-16 13:27:52'),
(230, 3, 'User Management', 'create_account', 'user_account', 81, 'Created user: Christine Mendoza (tin@gmail.com)', '2025-05-16 13:28:23');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notice`
--

DROP TABLE IF EXISTS `admin_notice`;
CREATE TABLE `admin_notice` (
  `notice_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_role` varchar(32) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
CREATE TABLE `application` (
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `resume_url` text NOT NULL,
  `portfolio_url` text DEFAULT NULL,
  `position` varchar(128) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'submitted',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `attendance_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','late','absent','undertime','overtime') NOT NULL DEFAULT 'present',
  `work_hours` decimal(5,2) DEFAULT NULL,
  `late_minutes` int(11) DEFAULT NULL,
  `undertime_minutes` int(11) DEFAULT NULL,
  `overtime_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `employee_id`, `date`, `check_in`, `check_out`, `status`, `work_hours`, `late_minutes`, `undertime_minutes`, `overtime_minutes`, `created_at`, `updated_at`) VALUES
(1, 64, '2025-05-01', '09:00:00', '17:00:00', 'present', NULL, NULL, NULL, NULL, '2025-05-11 15:42:13', '2025-05-11 15:42:13'),
(2, 64, '2025-05-02', '09:00:00', '17:00:00', 'present', NULL, NULL, NULL, NULL, '2025-05-11 15:42:13', '2025-05-11 15:42:13'),
(3, 64, '2025-05-03', '09:00:00', '17:00:00', 'present', NULL, NULL, NULL, NULL, '2025-05-11 15:42:13', '2025-05-11 15:42:13'),
(4, 64, '2025-05-06', '09:00:00', '17:00:00', 'present', NULL, NULL, NULL, NULL, '2025-05-11 15:42:13', '2025-05-11 15:42:13'),
(5, 64, '2025-05-07', '09:00:00', '17:00:00', 'present', NULL, NULL, NULL, NULL, '2025-05-11 15:42:13', '2025-05-11 15:42:13'),
(6, 68, '2025-05-12', '00:00:05', '21:12:28', 'overtime', 20.21, NULL, NULL, NULL, '2025-05-11 16:00:05', '2025-05-12 13:12:28'),
(7, 57, '2025-05-12', '01:36:53', '10:37:08', 'overtime', 8.00, NULL, NULL, NULL, '2025-05-11 17:36:53', '2025-05-12 02:37:08'),
(8, 68, '2025-05-13', '00:39:47', NULL, 'present', NULL, NULL, NULL, NULL, '2025-05-12 16:39:47', '2025-05-12 16:39:47'),
(9, 59, '2025-05-13', '01:14:29', '06:42:18', 'undertime', 4.46, NULL, NULL, NULL, '2025-05-12 17:14:29', '2025-05-12 22:42:18'),
(10, 69, '2025-05-13', '07:56:12', NULL, 'present', NULL, NULL, NULL, NULL, '2025-05-12 23:56:12', '2025-05-12 23:56:12'),
(11, 70, '2025-05-13', '09:14:50', NULL, 'late', NULL, NULL, NULL, NULL, '2025-05-13 01:14:50', '2025-05-13 01:14:50'),
(12, 65, '2025-05-13', '12:34:37', NULL, 'late', NULL, NULL, NULL, NULL, '2025-05-13 04:34:37', '2025-05-13 04:34:37'),
(13, 64, '2025-05-13', '14:06:16', NULL, 'late', NULL, NULL, NULL, NULL, '2025-05-13 06:06:16', '2025-05-13 06:06:16'),
(14, 70, '2025-05-14', '11:55:12', NULL, 'late', NULL, NULL, NULL, NULL, '2025-05-14 03:55:12', '2025-05-14 03:55:12'),
(15, 70, '2025-05-16', '07:06:16', NULL, 'present', NULL, NULL, NULL, NULL, '2025-05-15 23:06:16', '2025-05-15 23:06:16'),
(16, 73, '2025-05-16', '07:45:37', NULL, 'present', NULL, NULL, NULL, NULL, '2025-05-15 23:45:37', '2025-05-15 23:45:37'),
(17, 75, '2025-05-16', '07:49:25', '19:57:07', 'overtime', 11.13, NULL, NULL, NULL, '2025-05-15 23:49:25', '2025-05-16 11:57:07'),
(18, 76, '2025-05-16', '07:52:36', NULL, 'present', NULL, NULL, NULL, NULL, '2025-05-15 23:52:36', '2025-05-15 23:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_modification`
--

DROP TABLE IF EXISTS `attendance_modification`;
CREATE TABLE `attendance_modification` (
  `modification_id` bigint(20) NOT NULL,
  `employee_id` bigint(20) DEFAULT NULL,
  `date_of_attendance` date DEFAULT NULL,
  `original_time_in` time DEFAULT NULL,
  `original_time_out` time DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `evidence_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_modification`
--

INSERT INTO `attendance_modification` (`modification_id`, `employee_id`, `date_of_attendance`, `original_time_in`, `original_time_out`, `requested_time_in`, `requested_time_out`, `total_hours`, `remarks`, `reason`, `evidence_file`, `status`, `requested_at`) VALUES
(1, 57, '2025-05-12', '01:36:00', NULL, '02:47:00', NULL, NULL, '0', 'Forgot to time in/out', NULL, 'cancelled', '2025-05-11 18:47:53'),
(2, 57, '2025-05-11', '01:36:00', '10:37:00', '08:30:00', '15:00:00', 6.50, '0', 'System error', NULL, 'pending', '2025-05-12 03:28:19'),
(3, 68, '2025-05-12', '00:00:00', '21:12:00', '21:13:00', '04:13:00', 7.00, '0', 'System error', NULL, 'pending', '2025-05-12 13:13:32'),
(4, 59, '2025-05-06', '01:14:00', '13:18:00', '01:15:00', '14:15:00', 13.00, '0', 'Device not working', NULL, 'cancelled', '2025-05-12 17:16:11'),
(5, 70, '2025-05-13', '09:14:00', NULL, '08:16:00', NULL, NULL, '0', 'System error', NULL, 'approved', '2025-05-15 10:12:58'),
(6, 76, '2025-05-16', '07:52:00', NULL, '06:00:00', NULL, NULL, '0', 'System error', NULL, 'pending', '2025-05-15 23:57:14'),
(7, 76, '2025-05-16', '07:52:00', NULL, '06:00:00', NULL, NULL, '0', 'System error', NULL, 'pending', '2025-05-15 23:57:58'),
(8, 76, '2025-05-16', '07:52:00', NULL, '06:00:00', NULL, NULL, '0', 'System error', NULL, 'pending', '2025-05-16 00:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
CREATE TABLE `department` (
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `name`) VALUES
(4, 'Department Managers'),
(2, 'HR Department'),
(1, 'IT Department'),
(3, 'Operations\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `employee_rating`
--

DROP TABLE IF EXISTS `employee_rating`;
CREATE TABLE `employee_rating` (
  `rating_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `rated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `punctuality` tinyint(4) NOT NULL,
  `work_quality` tinyint(4) NOT NULL,
  `productivity` tinyint(4) NOT NULL,
  `teamwork` tinyint(4) NOT NULL,
  `professionalism` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_role`
--

DROP TABLE IF EXISTS `job_role`;
CREATE TABLE `job_role` (
  `job_role_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(64) NOT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_role`
--

INSERT INTO `job_role` (`job_role_id`, `title`, `department_id`) VALUES
(15, 'IT Manager / Head of IT', 1),
(16, 'System Administrator', 1),
(17, 'IT Support Specialist', 1),
(18, 'Software Developer / Web Developer', 1),
(19, 'Database Administrator (DBA)', 1),
(20, 'Network Engineer', 1),
(21, 'Cybersecurity Analyst', 1),
(22, 'HR Manager / HR Head', 2),
(23, 'HR Officer / Generalist', 2),
(24, 'Recruitment Specialist', 2),
(25, 'Training and Development Officer', 2),
(26, 'Compensation and Benefits Officer', 2),
(27, 'HRIS Administrator', 2),
(28, 'Department Head / Manager', 4),
(29, 'Team Leaders / Supervisors', 4),
(30, 'Administrative Support', 4),
(31, 'Analysts / Coordinators', 4),
(32, 'Operations Manager', 3),
(33, 'Operations Supervisor', 3),
(34, 'Logistics Coordinator', 3),
(35, 'Process Analyst', 3),
(36, 'Customer Service Lead', 3),
(37, 'Quality Assurance Officer', 3);

-- --------------------------------------------------------

--
-- Table structure for table `leave_application`
--

DROP TABLE IF EXISTS `leave_application`;
CREATE TABLE `leave_application` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(32) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `resumption_date` date NOT NULL,
  `reason` text NOT NULL,
  `handover_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_application`
--

INSERT INTO `leave_application` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `duration`, `resumption_date`, `reason`, `handover_doc`, `status`, `applied_at`) VALUES
(1, 68, 'Vacation Leave', '2025-05-15', '2025-05-17', 2, '2025-05-17', 'Lalangiy na ', 'uploads/leave_handover/6821f588373c0.png', 'pending', '2025-05-12 21:20:08'),
(2, 68, 'Vacation Leave', '2025-05-15', '2025-05-17', 3, '2025-05-19', 'Wala lalangoy na', NULL, 'pending', '2025-05-12 21:22:48'),
(3, 68, 'Sick Leave', '2025-05-11', '2025-05-12', 2, '2025-05-13', 'Wala lalangoy na', NULL, 'pending', '2025-05-12 23:46:45'),
(4, 59, 'Maternity Leave', '2025-05-23', '2025-05-24', 2, '2025-05-25', 'hkhjjk', NULL, 'approved', '2025-05-13 01:15:00'),
(5, 70, 'Sick Leave', '2025-05-13', '2025-05-13', 1, '2025-05-14', 'zasas', NULL, 'approved', '2025-05-13 09:17:06'),
(6, 76, 'Sick Leave', '2025-05-17', '2025-05-19', 3, '2025-05-20', 'sick', 'uploads/leave_handover/68268108059f8.pdf', 'approved', '2025-05-16 08:04:24'),
(7, 75, 'Sick Leave', '2025-05-16', '2025-05-17', 2, '2025-05-18', '123', NULL, 'pending', '2025-05-16 19:57:48');

-- --------------------------------------------------------

--
-- Table structure for table `leave_request`
--

DROP TABLE IF EXISTS `leave_request`;
CREATE TABLE `leave_request` (
  `leave_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `resumption_date` date NOT NULL,
  `reason` text NOT NULL,
  `hand_over_document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `process_remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `notification_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(64) NOT NULL,
  `content` text NOT NULL,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `scheduled_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notification_id`, `sender_id`, `title`, `type`, `content`, `scheduled_at`, `created_at`, `scheduled_date`) VALUES
(1, 3, 'Announcement', 'Company-wide Announcements', 'Announcement', '2025-05-10 05:35:00', '2025-05-09 15:35:49', NULL),
(2, 3, 'asdasda', 'Payroll and Compensation Notifications', 'asdasda', '2025-05-09 15:47:52', '2025-05-09 15:47:52', NULL),
(3, 3, 'adadasda', 'Payroll and Compensation Notifications', 'adadasda', '2025-05-09 15:49:47', '2025-05-09 15:49:47', NULL),
(4, 3, 'adasdasd', 'Company-wide Announcements', 'adasdasd', '2025-05-09 15:52:40', '2025-05-09 15:52:40', NULL),
(5, 3, 'wqqeqewq', 'Company-wide Announcements', 'wqqeqewq', '2025-05-12 04:51:00', '2025-05-11 03:50:37', NULL),
(6, 3, 'wqqeqewq', 'Company-wide Announcements', 'wqqeqewq', '2025-05-12 04:51:00', '2025-05-11 03:50:48', NULL),
(7, 3, 'asdasfsdfs', 'Company-wide Announcements', 'asdasfsdfs', '2025-05-12 04:52:00', '2025-05-11 03:51:11', NULL),
(8, 3, 'asdasfsdfs', 'Company-wide Announcements', 'asdasfsdfs', '2025-05-12 04:52:00', '2025-05-11 03:59:23', NULL),
(9, 3, 'asdasdadasdfdgdfg', 'Payroll and Compensation Notifications', 'asdasdadasdfdgdfg', '2025-05-12 03:59:00', '2025-05-11 03:59:53', NULL),
(10, 3, 'qweqweeqw', 'Company-wide Announcements', 'qweqweeqw', '2025-05-11 04:03:41', '2025-05-11 04:03:41', NULL),
(11, 3, 'qweqweeqw', 'Company-wide Announcements', 'qweqweeqw', '2025-05-11 04:04:12', '2025-05-11 04:04:12', NULL),
(12, 3, 'qweqweeqw', 'Company-wide Announcements', 'qweqweeqw', '2025-05-11 04:04:17', '2025-05-11 04:04:17', NULL),
(13, 3, 'qweqweqw', 'Company-wide Announcements', 'qweqweqw', '2025-05-11 04:04:31', '2025-05-11 04:04:31', NULL),
(14, 3, 'qwewqeqwwe', 'Company-wide Announcements', 'qwewqeqwwe', '2025-05-13 04:09:00', '2025-05-11 04:09:16', NULL),
(15, 3, 'asdadadasdsa', 'Company-wide Announcements', 'asdadadasdsa', '2025-05-11 04:18:33', '2025-05-11 04:18:33', NULL),
(16, 3, 'asdada', 'Training and Development Notifications', 'asdadsd', '2025-05-12 04:22:00', '2025-05-11 04:22:57', NULL),
(17, 3, 'asdada', 'Training and Development Notifications', 'asdadsd', '2025-05-12 04:22:00', '2025-05-11 04:23:29', NULL),
(18, 3, 'asdada', 'Training and Development Notifications', 'asdadsd', '2025-05-12 04:22:00', '2025-05-11 04:26:10', NULL),
(19, 3, 'dasdasdadsasd', 'Company-wide Announcements', 'dasdasdadsasd', '2025-05-11 04:26:33', '2025-05-11 04:26:33', NULL),
(20, 3, 'adadadasdas', 'Payroll and Compensation Notifications', 'adadadasdas', '2025-05-11 04:31:34', '2025-05-11 04:31:34', NULL),
(21, 3, 'adasddadaas', 'Payroll and Compensation Notifications', 'adasddadaas', '2025-05-11 04:32:14', '2025-05-11 04:32:14', NULL),
(22, 3, 'asdadaddasasd', 'Payroll and Compensation Notifications', 'asdadaddasasd', '2025-05-11 04:43:55', '2025-05-12 04:42:00', NULL),
(23, 3, 'adadsdadasdasa', 'Company-wide Announcements', 'asdasdasd', '2025-05-11 04:44:08', '2025-05-10 22:44:08', NULL),
(24, 3, 'adadsdadasdasa', 'Company-wide Announcements', 'asdasdasd', '2025-05-11 04:44:55', '2025-05-10 22:44:55', NULL),
(25, 3, 'asdasdasdadsd', 'Company-wide Announcements', 'asdasdasdadsd', '2025-05-11 04:45:16', '2025-05-10 22:45:16', NULL),
(26, 3, 'asdasdasdadsd', 'Company-wide Announcements', 'asdasdasdadsd', '2025-05-11 04:45:29', '2025-05-10 22:45:29', NULL),
(27, 3, 'asdasdasdadsd', 'Company-wide Announcements', 'asdasdasdadsd', '2025-05-11 04:45:49', '2025-05-10 22:45:49', NULL),
(28, 3, 'asdasdasdadsd', 'Company-wide Announcements', 'asdasdasdadsd', '2025-05-11 04:49:29', '2025-05-10 22:49:29', NULL),
(29, 3, 'asdasdasdadsd', 'Company-wide Announcements', 'asdasdasdadsd', '2025-05-11 04:49:36', '2025-05-10 22:49:36', NULL),
(30, 3, 'asdshfghfghfhf', 'Leave Request Status', 'asdadasdassddadsasd', '2025-05-11 04:49:51', '2025-05-12 04:49:00', NULL),
(31, 3, 'ds ghfdhfgjfghjghfj', 'Employee Management Notifications', 'ds ghfdhfgjfghjghfj', '2025-05-11 04:51:06', '2025-05-15 04:51:00', NULL),
(32, 3, 'asdadaad', 'Company-wide Announcements', 'asdadaad', '2025-05-11 06:32:21', '2025-05-11 00:32:21', NULL),
(33, 3, 'asdafafafafafas', 'Payroll and Compensation Notifications', 'asdafafafafafas', '2025-05-11 07:50:49', '2025-05-11 01:50:49', NULL),
(34, 3, 'agsdfgdfgdfgdf', 'Payroll and Compensation Notifications', 'dfgdfgdgdgf', '2025-05-11 07:51:45', '2025-05-12 07:51:00', NULL),
(35, 3, 'saafsfssdafsdfsdf', 'Company-wide Announcements', 'asfafafasaf', '2025-05-11 07:52:13', '2025-05-12 07:52:00', NULL),
(36, 3, 'saafsfssdafsdfsdf', 'Company-wide Announcements', 'asfafafasaf', '2025-05-11 07:52:15', '2025-05-12 07:52:00', NULL),
(37, 57, 'fsfsf', 'Company-wide Announcements', 'fadfadasdasd', '2025-05-12 11:43:15', '2025-05-12 11:44:00', NULL),
(38, 57, 'Hello', 'Training and Development Notifications', 'This wiejlkdabhshdbasfnbhjdsgjhadssd', '2025-05-12 11:44:31', '2025-05-12 05:44:31', NULL),
(39, 57, 'Testing muna', 'Leave Request Status', 'Hello guiz test test', '2025-05-12 11:46:22', '2025-05-12 05:46:22', NULL),
(40, 57, 'hagdhjagjhsdgahjsd', 'Company-wide Announcements', 'What', '2025-05-12 11:47:17', '2025-05-12 05:47:17', NULL),
(41, 1, 'Future Test', 'announcement', 'This should not show yet', '2025-05-12 12:30:22', '2025-05-12 12:30:22', '2025-12-31 23:59:00'),
(42, 3, 'asd', 'Payroll and Compensation Notifications', 'asd', '2025-05-16 00:12:48', '2025-05-17 00:12:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_recipient`
--

DROP TABLE IF EXISTS `notification_recipient`;
CREATE TABLE `notification_recipient` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `notification_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_recipient`
--

INSERT INTO `notification_recipient` (`id`, `notification_id`, `department_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4),
(5, 2, 4),
(6, 3, 3),
(7, 4, 1),
(8, 4, 2),
(9, 4, 3),
(10, 4, 4),
(11, 5, 1),
(12, 5, 2),
(13, 5, 3),
(14, 5, 4),
(15, 6, 1),
(16, 6, 2),
(17, 6, 3),
(18, 6, 4),
(19, 7, 4),
(20, 8, 4),
(21, 9, 1),
(22, 9, 2),
(23, 9, 3),
(24, 9, 4),
(25, 10, 1),
(26, 10, 2),
(27, 10, 3),
(28, 10, 4),
(29, 11, 1),
(30, 11, 2),
(31, 11, 3),
(32, 11, 4),
(33, 12, 1),
(34, 12, 2),
(35, 12, 3),
(36, 12, 4),
(37, 13, 1),
(38, 13, 2),
(39, 13, 3),
(40, 13, 4),
(41, 14, 1),
(42, 14, 2),
(43, 14, 3),
(44, 14, 4),
(45, 15, 1),
(46, 15, 2),
(47, 15, 3),
(48, 15, 4),
(49, 16, 4),
(50, 17, 4),
(51, 18, 4),
(52, 19, 1),
(53, 19, 2),
(54, 19, 3),
(55, 19, 4),
(56, 20, 4),
(57, 20, 2),
(58, 20, 1),
(59, 20, 3),
(60, 21, 4),
(61, 21, 2),
(62, 21, 1),
(63, 21, 3);

-- --------------------------------------------------------

--
-- Table structure for table `resignation`
--

DROP TABLE IF EXISTS `resignation`;
CREATE TABLE `resignation` (
  `resignation_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `comments` text DEFAULT NULL,
  `resignation_letter` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `process_remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resignation`
--

INSERT INTO `resignation` (`resignation_id`, `employee_id`, `reason`, `comments`, `resignation_letter`, `status`, `submitted_at`, `processed_at`, `processed_by`, `process_remarks`) VALUES
(19, 70, 'Career growth', 'huhu', NULL, 'rejected', '2025-05-13 02:45:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `name`) VALUES
(1, 'Admin'),
(2, 'Employee'),
(4, 'Hr'),
(3, 'Manager');

-- --------------------------------------------------------

--
-- Table structure for table `todos`
--

DROP TABLE IF EXISTS `todos`;
CREATE TABLE `todos` (
  `todo_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `task` text NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `todos`
--

INSERT INTO `todos` (`todo_id`, `employee_id`, `task`, `status`, `due_date`, `created_at`, `updated_at`) VALUES
(11, 69, 'dasda', 'pending', NULL, '2025-05-13 00:00:38', '2025-05-13 00:00:38'),
(12, 69, 'dasd', 'pending', NULL, '2025-05-13 00:01:01', '2025-05-13 00:01:01'),
(13, 69, 'dsda', 'pending', NULL, '2025-05-13 00:01:28', '2025-05-13 00:01:28'),
(14, 70, 'ada', 'pending', NULL, '2025-05-13 01:15:02', '2025-05-13 01:15:02'),
(15, 70, 'Hello po', 'pending', NULL, '2025-05-13 02:45:48', '2025-05-13 02:45:48'),
(16, 70, 'Testing muna', 'pending', NULL, '2025-05-13 02:45:52', '2025-05-13 02:45:52');

-- --------------------------------------------------------

--
-- Stand-in structure for view `todos_with_employee`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `todos_with_employee`;
CREATE TABLE `todos_with_employee` (
`todo_id` bigint(20) unsigned
,`employee_id` bigint(20) unsigned
,`employee_name` varchar(255)
,`employee_email` varchar(255)
,`department_name` varchar(64)
,`job_title` varchar(64)
,`task` text
,`status` enum('pending','completed')
,`due_date` date
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

DROP TABLE IF EXISTS `user_account`;
CREATE TABLE `user_account` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `job_role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `mobile_number` varchar(30) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `manager_rating` int(11) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT 'Full-Time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`user_id`, `email`, `password`, `full_name`, `role_id`, `created_at`, `updated_at`, `job_role_id`, `department_id`, `date_of_birth`, `mobile_number`, `gender`, `civil_status`, `address`, `nationality`, `manager_rating`, `employment_type`) VALUES
(3, 'qwerty@gmail.com', '$2y$10$3O0HBaPY0p4.1uewtOs5f.eSnb4r7DqEnKPiXkOcUVXZ1YCtqxYEe', 'qwerty@gmail.com', 1, '2025-05-08 08:58:34', '2025-05-16 00:13:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(58, 'asdadasd@gmai.com', '$2y$10$ewPzERJIieCm8dejuKGKh.6kpgADhZFo4IMu.mut085rmzvFrmwlu', 'asdadasd@gmai.com', 2, '2025-05-10 11:24:24', '2025-05-10 11:24:24', 22, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(62, 'adsdadas@gmail.com', '$2y$10$Na1nqibdyCrCYOIik1dvT.bT6hBfalW0/VD6cm0rdKGGaT061JOZG', 'adsdadasadasdasasdfsdfsdfsdf', 2, '2025-05-11 05:13:56', '2025-05-11 05:42:46', 22, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(64, 'johndoe@fake.com', '$2y$10$71eIlfiFEHCML5aBnv9cgu3HZjqlQ/CsWIjjPmuvV1jMzaUFDNThC', 'John Doe', 2, '2025-05-11 05:47:00', '2025-05-11 05:47:00', 15, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(65, 'janedoe@fake.com', '$2y$10$fhLG4o8enyzvdao.SCaFmur5RBn0UF6338WCFxRPIMSt/KYGLlALq', 'Jane Doe', 2, '2025-05-11 05:47:23', '2025-05-11 05:47:23', 22, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(66, 'bobjoe@fake.com', '$2y$10$vTkSaHKMJDZtsttsdgIBI.QXvCBBORyg0myr.dzSBI3jCFFJNBHbO', 'Bob Joe', 2, '2025-05-11 05:47:44', '2025-05-11 05:47:44', 34, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(68, 'asd@gmail.com', '$2y$10$XAnzKuZNxGJNdwTZaFg7c.48AOLXBHMraVTjdwpo2z7P50CHL.ivS', 'asdf', 2, '2025-05-11 15:34:59', '2025-05-11 15:34:59', 26, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(69, 'qewew@gmail.com', '$2y$10$I01lU6FAADWXzw.jfl5NCenJ0kPDEMMclGRsGeZO4w2SGu/eM6xLi', 'qewew', 2, '2025-05-11 15:35:18', '2025-05-11 15:35:18', 23, 2, '2025-05-13', '9923123123123', 'Female', 'Single', 'zxdasdasd', 'Filipino', NULL, NULL),
(70, 'gly@gmail.com', '$2y$10$/TD3TVTARpZOJMXXis0p2.BsqGVcit1dEsjfweVgeDpjgrjI30Q3C', 'Glycel Yvon Virtuci', 2, '2025-05-12 23:48:49', '2025-05-12 23:48:49', 37, 3, '2004-10-12', '09933148234', 'Female', 'Single', 'zxdasdasd', 'Filipino', NULL, 'Full-Time'),
(71, 'manager@gmail.com', '$2y$10$1z21CiG84Ofh2EXcleEfyuBwV3yGsjpvGQvGTXhlDRhiQzmeiWGrG', 'Manager', 3, '2025-05-13 05:02:46', '2025-05-16 13:27:10', 30, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(72, 'hr@gmail.com', '$2y$10$byfHTvSDIAID3vtCSDO.GuudolInUKoQkn55C1u/UmS95oaIxNZH6', 'Human Resource', 4, '2025-05-14 04:33:57', '2025-05-16 13:24:47', 22, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(73, 'employee1@gmail.com', '$2y$10$03YEf5xxeJXFEEe3leHtmO8jR.Y5V5C3hr8BC0Bj6PH3FhMamqeFm', 'employee1@gmail.com', 2, '2025-05-15 23:07:50', '2025-05-15 23:07:50', 15, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(74, 'qwerty1@gmail.com', '$2y$10$pzK8gIbJjxKknjrFvl8CG.s/9fxc5fyrkAu09FYJzBdawrHTZV9Hq', 'qwerty1@gmail.com', 2, '2025-05-15 23:39:08', '2025-05-15 23:39:08', 26, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(75, 'employee2@gmail.com', '$2y$10$03DgsidAPiWdV184J2w94.tSm8tovCmaBuZKmTZ8akUTQdXYNMLl.', 'employee2@gmail.com', 2, '2025-05-15 23:47:55', '2025-05-15 23:47:55', 30, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(76, 'employee3@gmail.com', '$2y$10$P0A3zOla14TcPR4GGR9jjuM1mVO6doSwwhtiIW9uyZFURgBFabyWy', 'employee3@gmail.com', 2, '2025-05-15 23:51:25', '2025-05-15 23:51:25', 26, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(77, 'qweq@gmail.com', '$2y$10$lFSzrpUFKqQGd0k46GB5eeHzELhrU01IVuvGdc9.chwrOpO66x6D2', 'qweq', 2, '2025-05-16 00:13:32', '2025-05-16 00:13:32', 30, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(78, 'glycel.virtucio@gmail.com', '$2y$10$RKSuDsET8DHDc8KUTnWgPenJYSTGlqyNtJGFlpka2VFBgTq6BQvFC', 'Glycel Yvon Virtucio', 2, '2025-05-16 13:21:20', '2025-05-16 13:21:20', 19, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(79, 'vincent@gmail.com', '$2y$10$g.ppQHSRQfevzym6Zh2cv.h84HqouYrWp0K4TyNyAmN7ifJbiGLDy', 'John vincent Laylo', 2, '2025-05-16 13:22:07', '2025-05-16 13:22:07', 33, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(80, 'jerome@gmail.com', '$2y$10$sZcnyf1KOl7uqeh7XY0bvu438aWrKK1mFyfbpje.DqrGORE6bzolW', 'Jerome Padre', 2, '2025-05-16 13:27:52', '2025-05-16 13:27:52', 35, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time'),
(81, 'tin@gmail.com', '$2y$10$T6ROA7tMfdChmwCZmYCEI.sOFwFSHlhhD9AI6YzHSB7yveYFARP4.', 'Christine Mendoza', 2, '2025-05-16 13:28:23', '2025-05-16 13:28:23', 18, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Full-Time');

-- --------------------------------------------------------

--
-- Structure for view `todos_with_employee`
--
DROP TABLE IF EXISTS `todos_with_employee`;

DROP VIEW IF EXISTS `todos_with_employee`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `todos_with_employee`  AS SELECT `t`.`todo_id` AS `todo_id`, `t`.`employee_id` AS `employee_id`, `u`.`full_name` AS `employee_name`, `u`.`email` AS `employee_email`, `d`.`name` AS `department_name`, `j`.`title` AS `job_title`, `t`.`task` AS `task`, `t`.`status` AS `status`, `t`.`due_date` AS `due_date`, `t`.`created_at` AS `created_at`, `t`.`updated_at` AS `updated_at` FROM (((`todos` `t` left join `user_account` `u` on(`t`.`employee_id` = `u`.`user_id`)) left join `department` `d` on(`u`.`department_id` = `d`.`department_id`)) left join `job_role` `j` on(`u`.`job_role_id` = `j`.`job_role_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admin_notice`
--
ALTER TABLE `admin_notice`
  ADD PRIMARY KEY (`notice_id`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`application_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `attendance_modification`
--
ALTER TABLE `attendance_modification`
  ADD PRIMARY KEY (`modification_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `employee_rating`
--
ALTER TABLE `employee_rating`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `rated_by` (`rated_by`);

--
-- Indexes for table `job_role`
--
ALTER TABLE `job_role`
  ADD PRIMARY KEY (`job_role_id`);

--
-- Indexes for table `leave_application`
--
ALTER TABLE `leave_application`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `status` (`status`),
  ADD KEY `type` (`type`),
  ADD KEY `fk_leave_processor` (`processed_by`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `notification_recipient`
--
ALTER TABLE `notification_recipient`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resignation`
--
ALTER TABLE `resignation`
  ADD PRIMARY KEY (`resignation_id`),
  ADD UNIQUE KEY `unique_resignation_employee` (`employee_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `status` (`status`),
  ADD KEY `fk_resignation_processor` (`processed_by`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `todos`
--
ALTER TABLE `todos`
  ADD PRIMARY KEY (`todo_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `status` (`status`),
  ADD KEY `due_date` (`due_date`);

--
-- Indexes for table `user_account`
--
ALTER TABLE `user_account`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `admin_notice`
--
ALTER TABLE `admin_notice`
  MODIFY `notice_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `application_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `attendance_modification`
--
ALTER TABLE `attendance_modification`
  MODIFY `modification_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employee_rating`
--
ALTER TABLE `employee_rating`
  MODIFY `rating_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_role`
--
ALTER TABLE `job_role`
  MODIFY `job_role_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `leave_application`
--
ALTER TABLE `leave_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leave_request`
--
ALTER TABLE `leave_request`
  MODIFY `leave_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `notification_recipient`
--
ALTER TABLE `notification_recipient`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `resignation`
--
ALTER TABLE `resignation`
  MODIFY `resignation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `todos`
--
ALTER TABLE `todos`
  MODIFY `todo_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_rating`
--
ALTER TABLE `employee_rating`
  ADD CONSTRAINT `employee_rating_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_rating_ibfk_2` FOREIGN KEY (`rated_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD CONSTRAINT `fk_leave_employee` FOREIGN KEY (`employee_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_leave_processor` FOREIGN KEY (`processed_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `resignation`
--
ALTER TABLE `resignation`
  ADD CONSTRAINT `fk_resignation_employee` FOREIGN KEY (`employee_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_resignation_processor` FOREIGN KEY (`processed_by`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `todos`
--
ALTER TABLE `todos`
  ADD CONSTRAINT `fk_todo_employee` FOREIGN KEY (`employee_id`) REFERENCES `user_account` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
