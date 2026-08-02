-- =====================================================================
--  ICT Ticket Support System — Complete Database Schema
--  Fully updated. Compatible with MySQL 5.7+ and MariaDB 10+.
--  ---------------------------------------------------------------------
--  HOW TO IMPORT
--   Local XAMPP  : phpMyAdmin → create a database named
--                  `ict_support_system`, select it, then Import this file.
--   InfinityFree : phpMyAdmin → open your ASSIGNED database (e.g.
--                  `if0_XXXXXXXX_ict_support`) and Import this file.
--                  No CREATE DATABASE / USE line is needed — the file
--                  drops and recreates the tables inside the database
--                  you have selected. Do NOT import into the default
--                  `phpmyadmin` / `information_schema` database.
--   CLI (XAMPP)  :  mysql -u root -e "CREATE DATABASE IF NOT EXISTS ict_support_system"
--                   mysql -u root ict_support_system < database/schema.sql
--
--  Connection settings for the app live in  .env  (DB_HOST, DB_PORT,
--  DB_NAME, DB_USER, DB_PASSWORD) and are read by  config/db.php.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Drop tables (order irrelevant while FK checks are disabled)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `otp_codes`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `subcategories`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `departments`;

-- ---------------------------------------------------------------------
-- Departments
-- ---------------------------------------------------------------------
CREATE TABLE `departments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Users  (self-registered accounts require admin approval)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(150) NOT NULL,
    `employee_number` VARCHAR(50) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(150) NOT NULL,
    `job_title` VARCHAR(120) DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `role` ENUM('admin', 'ict', 'employee') NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `approval_status` ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved',
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `review_reason` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `employee_number` (`employee_number`),
    KEY `department_id` (`department_id`),
    CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`)
        REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Categories / Subcategories
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `subcategories` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `category_id` INT NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Tickets
-- ---------------------------------------------------------------------
CREATE TABLE `tickets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `tracking_code` VARCHAR(40) NOT NULL,
    `employee_id` INT NOT NULL,
    `department_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `subcategory_id` INT NOT NULL,
    `assigned_to` INT DEFAULT NULL,
    `description` TEXT,
    `priority` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    `status` ENUM('Submitted', 'Assigned', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Submitted',
    `resolution_note` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tracking_code` (`tracking_code`),
    KEY `employee_id` (`employee_id`),
    KEY `department_id` (`department_id`),
    KEY `category_id` (`category_id`),
    KEY `subcategory_id` (`subcategory_id`),
    KEY `assigned_to` (`assigned_to`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`employee_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`department_id`)
        REFERENCES `departments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`subcategory_id`)
        REFERENCES `subcategories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`assigned_to`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Comments (staff updates / resolution timeline)
-- ---------------------------------------------------------------------
CREATE TABLE `comments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `comment_text` TEXT NOT NULL,
    `is_timeline` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_id` (`ticket_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`ticket_id`)
        REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Attachments (evidence uploaded with each ticket)
-- ---------------------------------------------------------------------
CREATE TABLE `attachments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(120) DEFAULT NULL,
    `file_size` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_id` (`ticket_id`),
    CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`ticket_id`)
        REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Notifications (email queue)
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `recipient_email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_id` (`ticket_id`),
    CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`ticket_id`)
        REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- OTP codes (login verification)
-- ---------------------------------------------------------------------
CREATE TABLE `otp_codes` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `code` VARCHAR(6) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempts` INT DEFAULT 0,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------
INSERT INTO `departments` (`name`) VALUES
('Administration'),
('Academics'),
('Finance'),
('Library'),
('Examinations')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `categories` (`name`) VALUES
('Hardware'),
('Software'),
('Network'),
('Printer'),
('Internet'),
('Email')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `subcategories` (`category_id`, `name`)
SELECT c.`id`, s.`subname`
FROM `categories` c
JOIN (
    SELECT 'Hardware' AS cname, 'Desktop Failure' AS subname UNION ALL
    SELECT 'Hardware', 'Laptop Battery' UNION ALL
    SELECT 'Software', 'Application Crash' UNION ALL
    SELECT 'Software', 'OS Update Error' UNION ALL
    SELECT 'Network', 'LAN Connection' UNION ALL
    SELECT 'Network', 'Wi-Fi Authentication' UNION ALL
    SELECT 'Printer', 'Paper Jam' UNION ALL
    SELECT 'Printer', 'Toner Issue' UNION ALL
    SELECT 'Internet', 'Slow Internet' UNION ALL
    SELECT 'Internet', 'No Internet Access' UNION ALL
    SELECT 'Email', 'Cannot Send Email' UNION ALL
    SELECT 'Email', 'Password Reset'
) s ON s.`cname` = c.`name`
WHERE NOT EXISTS (
    SELECT 1 FROM `subcategories` sc
    WHERE sc.`category_id` = c.`id` AND sc.`name` = s.`subname`
);

-- Default accounts (change these passwords after first login).
-- Admin-created accounts are active immediately (approval_status = 'approved').
INSERT INTO `users`
    (`full_name`, `employee_number`, `phone`, `email`, `job_title`, `department_id`, `role`, `password`)
VALUES
('System Administrator', 'ADM-0001', '08000000001', 'admin@institution.edu', 'ICT Director', 1, 'admin', '$2y$10$F9JLAG4ziAJ9MWH3Mza11.kQDp/bLNu1/hq2ovSgJKRVoGCYMDvfa'),
('ICT Support Officer', 'ICT-0001', '08000000002', 'ict1@institution.edu', 'Support Engineer', 1, 'ict', '$2y$10$H5rH6Dz0C4PaElScehIAB.k/4/TVfes6U354svL0dXk9i7FAjZDGm'),
('Alice N. Employee', 'EMP-1001', '08000000003', 'alice.employee@institution.edu', 'Administrative Assistant', 1, 'employee', '$2y$10$L57knu8saNK2L2R1uQ9/DeKbJF7lhKP2rwxJ/RAQUmbWG6nnLzuWq'),
('Brian K. Employee', 'EMP-1002', '08000000004', 'brian.employee@institution.edu', 'Accounts Officer', 3, 'employee', '$2y$10$5PYAEtJD8qKJQxu9m.JJSuZcI03kfUAZyP1X73n9rGk0idtXg235a')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

SET FOREIGN_KEY_CHECKS = 1;
