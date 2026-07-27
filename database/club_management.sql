-- =====================================================================
-- ELITE CLUB MANAGEMENT PORTAL
-- Database: club_management
-- Engine: MySQL 8.x / MariaDB 10.x (XAMPP)
-- Import this file via phpMyAdmin -> Import
-- =====================================================================

DROP DATABASE IF EXISTS `club_management`;
CREATE DATABASE `club_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `club_management`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- admins
-- ---------------------------------------------------------------------
CREATE TABLE `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('admin','member') NOT NULL DEFAULT 'admin',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- membership_plans
-- ---------------------------------------------------------------------
CREATE TABLE `membership_plans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `duration_months` INT(11) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `benefits` TEXT DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- members
-- ---------------------------------------------------------------------
CREATE TABLE `members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_no` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `plan_id` INT(11) DEFAULT NULL,
  `join_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `emergency_contact` VARCHAR(120) DEFAULT NULL,
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_no` (`member_no`),
  UNIQUE KEY `uq_member_email` (`email`),
  KEY `idx_member_status` (`status`),
  KEY `idx_member_plan` (`plan_id`),
  KEY `idx_member_expiry` (`expiry_date`),
  CONSTRAINT `fk_member_plan` FOREIGN KEY (`plan_id`)
    REFERENCES `membership_plans` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- payments
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` VARCHAR(30) NOT NULL,
  `member_id` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` ENUM('Cash','Card','Bank Transfer','UPI','Cheque') NOT NULL DEFAULT 'Cash',
  `reference_no` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('paid','pending','failed') NOT NULL DEFAULT 'paid',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receipt` (`receipt_no`),
  KEY `idx_pay_member` (`member_id`),
  KEY `idx_pay_date` (`payment_date`),
  KEY `idx_pay_status` (`status`),
  CONSTRAINT `fk_pay_member` FOREIGN KEY (`member_id`)
    REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- attendance
-- ---------------------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `member_id` INT(11) NOT NULL,
  `check_date` DATE NOT NULL,
  `check_time` TIME NOT NULL,
  `status` ENUM('present','late','absent') NOT NULL DEFAULT 'present',
  `note` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_date` (`member_id`,`check_date`),
  KEY `idx_att_date` (`check_date`),
  KEY `idx_att_status` (`status`),
  CONSTRAINT `fk_att_member` FOREIGN KEY (`member_id`)
    REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- events
-- ---------------------------------------------------------------------
CREATE TABLE `events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `location` VARCHAR(200) DEFAULT NULL,
  `event_date` DATE NOT NULL,
  `event_time` TIME NOT NULL,
  `organizer` VARCHAR(150) DEFAULT NULL,
  `max_participants` INT(11) DEFAULT 0,
  `status` ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_event_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- event_registration
-- ---------------------------------------------------------------------
CREATE TABLE `event_registration` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `event_id` INT(11) NOT NULL,
  `member_id` INT(11) NOT NULL,
  `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_member` (`event_id`,`member_id`),
  KEY `idx_reg_member` (`member_id`),
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`)
    REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_member` FOREIGN KEY (`member_id`)
    REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- announcements
-- ---------------------------------------------------------------------
CREATE TABLE `announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `body` TEXT NOT NULL,
  `audience` ENUM('all','members','admins') NOT NULL DEFAULT 'all',
  `created_by` INT(11) DEFAULT NULL,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ann_status` (`status`),
  CONSTRAINT `fk_ann_admin` FOREIGN KEY (`created_by`)
    REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- settings
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- activity_logs
-- ---------------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `user_type` ENUM('admin','member') NOT NULL DEFAULT 'admin',
  `action` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Default data
-- ---------------------------------------------------------------------
INSERT INTO `admins` (`name`,`email`,`password`,`phone`,`role`) VALUES
('System Administrator','admin@club.com','$2y$10$/Y4YDyhPv822hByCd87xm.KjDFRZ5UyzvCRk/B/ICOAVESxBilyni','555-0100','admin');
-- Default password: admin123

INSERT INTO `membership_plans` (`name`,`duration_months`,`price`,`benefits`,`status`) VALUES
('Monthly',1,50.00,'Full gym & lounge access, 4 classes/month','active'),
('Quarterly',3,135.00,'Full access, unlimited classes, 1 guest pass/month','active'),
('Half Yearly',6,250.00,'Full access, unlimited classes, 2 guest passes/month, free locker','active'),
('Yearly',12,480.00,'Everything in Half Yearly plus priority booking & merch kit','active');

INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','Elite Club Management Portal'),
('club_name','Elite Club'),
('currency','$'),
('timezone','Asia/Kolkata'),
('late_threshold','09:00:00'),
('session_timeout','1800');

INSERT INTO `announcements` (`title`,`body`,`audience`,`created_by`,`status`) VALUES
('Welcome to Elite Club','We are thrilled to have you on board. Check the events page for upcoming activities!','all',1,'published'),
('New Yoga Classes Starting Soon','Starting next month, weekend yoga sessions will be available at no extra cost for all members.','members',1,'published');
