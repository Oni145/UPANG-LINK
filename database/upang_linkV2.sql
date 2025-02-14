-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2025 at 03:09 PM
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
-- Database: `upang_link`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `token` char(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `is_active`) VALUES
(1, 'Academic Documents', 'Transcripts, certificates, and other academic records', 1),
(2, 'Student ID', 'Student identification card and related items (1x1 ID photo required)', 1),
(3, 'Uniforms', 'School uniform requests', 1),
(4, 'Books and Modules', 'Academic materials and learning resources', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','in_progress','completed') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `user_id`, `type_id`, `status`, `submitted_at`, `updated_at`) VALUES
(3, 2, 1, 'approved', '2025-02-13 15:39:46', '2025-02-13 15:46:57'),
(4, 3, 1, 'approved', '2025-02-14 04:51:24', '2025-02-14 04:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `request_notes`
--

CREATE TABLE `request_notes` (
  `note_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_requirement_notes`
--

CREATE TABLE `request_requirement_notes` (
  `note_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `requirement_name` varchar(100) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_types`
--

CREATE TABLE `request_types` (
  `type_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements`)),
  `processing_time` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_types`
--

INSERT INTO `request_types` (`type_id`, `category_id`, `name`, `description`, `requirements`, `processing_time`, `is_active`) VALUES
(1, 1, 'Transcript of Records', 'Official academic transcript', '{\"fields\": [{\"name\": \"clearance_form\", \"label\": \"Clearance Form\", \"type\": \"file\", \"required\": true, \"allowed_types\": \"pdf,jpg,png\", \"description\": \"Fully accomplished clearance form\"}, {\"name\": \"request_letter\", \"label\": \"Request Letter\", \"type\": \"file\", \"required\": true, \"allowed_types\": \"pdf,doc,docx\", \"description\": \"Formal letter stating the purpose of requesting TOR\"}, {\"name\": \"purpose\", \"label\": \"Purpose\", \"type\": \"text\", \"required\": true, \"description\": \"State the purpose of requesting TOR\"}, {\"name\": \"additional_docs\", \"label\": \"Additional Supporting Documents\", \"type\": \"file\", \"required\": false, \"allowed_types\": \"pdf,jpg,png,doc,docx\", \"description\": \"Any additional documents to support your request (optional)\"}], \"instructions\": \"Please ensure all required documents are complete. Additional supporting documents are optional but may help process your request faster.\"}', '5-7 working days', 1),
(2, 1, 'Enrollment Certificate', 'Proof of enrollment document', NULL, '2-3 working days', 1),
(3, 2, 'New Student ID', 'First time ID request', NULL, '5-7 working days', 1),
(4, 2, 'ID Replacement', 'Lost or damaged ID replacement', '{\"fields\": [{\"name\": \"affidavit_loss\", \"label\": \"Affidavit of Loss\", \"type\": \"file\", \"required\": true, \"allowed_types\": \"pdf\", \"description\": \"Notarized affidavit of loss\"}, {\"name\": \"id_picture\", \"label\": \"1x1 ID Picture\", \"type\": \"file\", \"required\": true, \"allowed_types\": \"jpg,png\", \"description\": \"Recent 1x1 ID picture with white background\"}, {\"name\": \"payment_receipt\", \"label\": \"Payment Receipt\", \"type\": \"file\", \"required\": false, \"allowed_types\": \"pdf,jpg,png\", \"description\": \"Receipt of payment for ID replacement (can be submitted later)\"}, {\"name\": \"remarks\", \"label\": \"Additional Remarks\", \"type\": \"text\", \"required\": false, \"description\": \"Any additional information about your ID replacement request\"}], \"instructions\": \"Submit the required documents. Payment receipt can be submitted later but must be provided before ID release.\"}', '5-7 working days', 1),
(5, 3, 'PE Uniform Request', 'Physical Education uniform set', NULL, '3-5 working days', 1),
(6, 3, 'School Uniform Request', 'Regular school uniform set', NULL, '3-5 working days', 1),
(7, 4, 'Course Module Request', 'Subject-specific learning materials', NULL, '1-2 working days', 1);

-- --------------------------------------------------------

--
-- Table structure for table `required_documents`
--

CREATE TABLE `required_documents` (
  `document_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `required_documents`
--

INSERT INTO `required_documents` (`document_id`, `request_id`, `document_type`, `file_name`, `file_path`, `uploaded_at`, `is_verified`) VALUES
(2, 3, 'clearance_form', '☆ㅤㅤ firefly iconㅤ!.jpg', 'uploads/☆ㅤㅤ firefly iconㅤ!.jpg', '2025-02-13 15:39:46', 0),
(3, 3, 'request_letter', '-BALITANG-KASAYSAYAN.docx', 'uploads/-BALITANG-KASAYSAYAN.docx', '2025-02-13 15:39:46', 0),
(4, 4, 'clearance_form', 'firefly honkai star rail ✧___。.jpg', 'uploads/firefly honkai star rail ✧___。.jpg', '2025-02-14 04:51:24', 0),
(5, 4, 'request_letter', '-BALITANG-KASAYSAYAN.docx', 'uploads/-BALITANG-KASAYSAYAN.docx', '2025-02-14 04:51:24', 0);

-- --------------------------------------------------------

--
-- Table structure for table `requirement_templates`
--

CREATE TABLE `requirement_templates` (
  `template_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `requirement_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `file_types` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('student','admin','staff') NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `block` varchar(10) DEFAULT NULL,
  `admission_year` varchar(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `student_number`, `password`, `first_name`, `last_name`, `role`, `course`, `year_level`, `block`, `admission_year`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', NULL, NULL, NULL, NULL, '2025-02-13 15:32:22', '2025-02-13 15:32:22'),
(2, '0001-2021-00123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Matthew Cymon', 'Estrada', 'student', 'BSIT', 3, 'BN', '2021', '2025-02-13 15:32:22', '2025-02-13 15:32:22'),
(3, '0001-2021-00124', '$2y$10$UKDFCRTqw5bNIlgpgYnOmuX.WsnaFTrk1JDqcIEOEutO18Ml3Bv2y', 'John', 'Doe', 'student', 'BSIT', 1, 'A', '2023', '2025-02-14 04:48:37', '2025-02-14 04:48:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `login_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logins`
--

INSERT INTO `user_logins` (`login_id`, `user_id`, `token`, `login_time`, `ip_address`, `user_agent`) VALUES
(1, 3, 'b9bf13cce2088dc71fd0b8f92fe1997d', '2025-02-14 12:53:18', '::1', 'PostmanRuntime/7.43.0'),
(2, 3, '83dc7634abbe2f319f85c48a30478aab', '2025-02-14 12:53:38', '::1', 'PostmanRuntime/7.43.0'),
(3, 3, '5efb233959ae73ec0dec87ec01706f46', '2025-02-14 13:04:11', '::1', 'PostmanRuntime/7.43.0'),
(4, 3, '4903b196ab952193c07784e1a7ec1938', '2025-02-14 13:09:52', '::1', 'PostmanRuntime/7.43.0');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`token`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `request_notes`
--
ALTER TABLE `request_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `request_requirement_notes`
--
ALTER TABLE `request_requirement_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `request_types`
--
ALTER TABLE `request_types`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `required_documents`
--
ALTER TABLE `required_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `requirement_templates`
--
ALTER TABLE `requirement_templates`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`login_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `request_notes`
--
ALTER TABLE `request_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_requirement_notes`
--
ALTER TABLE `request_requirement_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_types`
--
ALTER TABLE `request_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `required_documents`
--
ALTER TABLE `required_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `requirement_templates`
--
ALTER TABLE `requirement_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `request_types` (`type_id`);

--
-- Constraints for table `request_notes`
--
ALTER TABLE `request_notes`
  ADD CONSTRAINT `request_notes_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`),
  ADD CONSTRAINT `request_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `request_requirement_notes`
--
ALTER TABLE `request_requirement_notes`
  ADD CONSTRAINT `request_requirement_notes_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`),
  ADD CONSTRAINT `request_requirement_notes_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `request_types`
--
ALTER TABLE `request_types`
  ADD CONSTRAINT `request_types_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `required_documents`
--
ALTER TABLE `required_documents`
  ADD CONSTRAINT `required_documents_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`);

--
-- Constraints for table `requirement_templates`
--
ALTER TABLE `requirement_templates`
  ADD CONSTRAINT `requirement_templates_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `request_types` (`type_id`);

--
-- Constraints for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD CONSTRAINT `user_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
