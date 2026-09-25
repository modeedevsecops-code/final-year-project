-- ============================================================================
--  BloodLink — Enhanced Blood Bank Management System
--  Canonical schema (v3). Aligned with the project brief's ERD (Figure 5.3):
--  the design centres on blood_banks (registered facilities), with blood_stock
--  and stock_alerts scoped PER BANK, and staff (hospital officers) belonging to
--  a bank. The Blood Bank Locator ranks these banks by distance and routes to
--  them (Nominatim + OSRM).
--
--  Tables: login, blood_banks, staff, students (donors), recipients,
--          blood_requests, notices, donations, blood_stock, stock_alerts.
--
--  Import:  mysql -u root donor_app < db/schema.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `allocation`;
DROP TABLE IF EXISTS `timetable`;
DROP TABLE IF EXISTS `stock_alerts`;
DROP TABLE IF EXISTS `blood_stock`;
DROP TABLE IF EXISTS `donations`;
DROP TABLE IF EXISTS `recipients`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `blood_requests`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `blood_banks`;
DROP TABLE IF EXISTS `login`;

-- ---------------------------------------------------------------- login ----
-- Administrator accounts.
CREATE TABLE `login` (
  `user_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `USERNAME`  VARCHAR(50)  NOT NULL,
  `PASSWORD`  VARCHAR(255) NOT NULL,
  `PW_STATUS` VARCHAR(20)  NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_login_username` (`USERNAME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------- blood_banks ----
-- Registered blood-bank facilities. Central to the ERD and the locator: each
-- has a geocoded location and contact so donors/recipients can find and route
-- to the nearest one. NEW in v3 (was missing though the brief specified it).
CREATE TABLE `blood_banks` (
  `bank_id`       INT(11) NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `address`       VARCHAR(255) NOT NULL,
  `latitude`      DECIMAL(10,7) DEFAULT NULL,
  `longitude`     DECIMAL(10,7) DEFAULT NULL,
  `contact_phone` VARCHAR(20)  DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bank_id`),
  UNIQUE KEY `uq_bank_name` (`name`),
  KEY `idx_bank_geo` (`latitude`,`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Registered blood-bank facilities';

-- ---------------------------------------------------------------- staff ----
-- Hospital officers. Each belongs to a blood bank (brief ERD: staff.hospital_id).
CREATE TABLE `staff` (
  `staff_id`        INT(11) NOT NULL AUTO_INCREMENT,
  `staff_name`      VARCHAR(100) NOT NULL,
  `phone`           VARCHAR(20)  NOT NULL,
  `email`           VARCHAR(100) NOT NULL,
  `position`        VARCHAR(150) NOT NULL,
  `blood_bank_id`   INT(11)      DEFAULT NULL COMMENT 'FK -> blood_banks.bank_id',
  `password`        VARCHAR(255) NOT NULL,
  `date_registered` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `uq_staff_email` (`email`),
  KEY `fk_staff_bank` (`blood_bank_id`),
  CONSTRAINT `fk_staff_bank` FOREIGN KEY (`blood_bank_id`)
    REFERENCES `blood_banks` (`bank_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Hospital officers / blood bank staff';

-- ------------------------------------------------------------- students ----
-- Blood donors. (Table name retained from the original codebase.)
CREATE TABLE `students` (
  `student_id`          INT(11) NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(100) NOT NULL,
  `email`               VARCHAR(100) NOT NULL,
  `phone`               VARCHAR(20)  NOT NULL,
  `reg_no`              VARCHAR(100) NOT NULL COMMENT 'Donor ID used at login',
  `blood_group`         VARCHAR(5)   DEFAULT NULL COMMENT 'A+ A- B+ B- AB+ AB- O+ O-',
  `last_donation_date`  DATE         DEFAULT NULL COMMENT 'NULL = never donated; drives the 56-day rule',
  `address`             VARCHAR(255) DEFAULT NULL,
  `latitude`            DECIMAL(10,7) DEFAULT NULL,
  `longitude`           DECIMAL(10,7) DEFAULT NULL,
  `password`            VARCHAR(255) NOT NULL,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `uq_students_email` (`email`),
  KEY `idx_students_blood_group` (`blood_group`),
  KEY `idx_students_reg_no` (`reg_no`),
  KEY `idx_students_geo` (`latitude`,`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Blood donors';

-- ----------------------------------------------------------- recipients ----
CREATE TABLE `recipients` (
  `recipient_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(100) NOT NULL,
  `email`        VARCHAR(100) NOT NULL,
  `phone`        VARCHAR(20)  NOT NULL,
  `address`      VARCHAR(255) DEFAULT NULL,
  `blood_group`  VARCHAR(5)   DEFAULT NULL,
  `latitude`     DECIMAL(10,7) DEFAULT NULL,
  `longitude`    DECIMAL(10,7) DEFAULT NULL,
  `password`     VARCHAR(255) NOT NULL COMMENT 'bcrypt via password_hash()',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`recipient_id`),
  UNIQUE KEY `uq_recipients_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Blood recipients / patients';

-- ------------------------------------------------------- blood_requests ----
CREATE TABLE `blood_requests` (
  `request_id`    INT(11) NOT NULL AUTO_INCREMENT,
  `recipient_id`  INT(11) DEFAULT NULL,
  `patient_name`  VARCHAR(100) NOT NULL,
  `blood_group`   VARCHAR(10)  NOT NULL,
  `units_needed`  INT(11) NOT NULL DEFAULT 1,
  `hospital_name` VARCHAR(150) NOT NULL,
  `location`      VARCHAR(255) NOT NULL,
  `latitude`      DECIMAL(10,7) DEFAULT NULL,
  `longitude`     DECIMAL(10,7) DEFAULT NULL,
  `urgency_level` ENUM('Normal','Urgent','Critical Emergency') NOT NULL DEFAULT 'Urgent',
  `status`        ENUM('Pending','Approved','Fulfilled','Cancelled') NOT NULL DEFAULT 'Pending',
  `requested_by`  VARCHAR(100) DEFAULT NULL,
  `contact_phone` VARCHAR(20)  DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `idx_requests_status` (`status`),
  KEY `idx_requests_blood_group` (`blood_group`),
  KEY `fk_request_recipient` (`recipient_id`),
  CONSTRAINT `fk_request_recipient` FOREIGN KEY (`recipient_id`)
    REFERENCES `recipients` (`recipient_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------- notices ----
-- Emergency alerts. supervisor_id NULL = system-generated.
CREATE TABLE `notices` (
  `notice_id`     INT(11) NOT NULL AUTO_INCREMENT,
  `supervisor_id` INT(11) DEFAULT NULL COMMENT 'NULL = system-generated alert',
  `title`         VARCHAR(255) NOT NULL,
  `message`       TEXT NOT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notice_id`),
  KEY `fk_notice_supervisor` (`supervisor_id`),
  CONSTRAINT `fk_notice_supervisor` FOREIGN KEY (`supervisor_id`)
    REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------- donations ----
CREATE TABLE `donations` (
  `donation_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `donor_id`      INT(11) NOT NULL COMMENT 'FK -> students.student_id',
  `recipient_id`  INT(11) DEFAULT NULL,
  `request_id`    INT(11) DEFAULT NULL,
  `officer_id`    INT(11) NOT NULL COMMENT 'FK -> staff.staff_id, who confirmed it',
  `blood_bank_id` INT(11) DEFAULT NULL COMMENT 'FK -> blood_banks.bank_id, where it was banked',
  `blood_group`   VARCHAR(5) NOT NULL,
  `units`         INT(11) NOT NULL DEFAULT 1,
  `donation_date` DATE NOT NULL,
  `status`        ENUM('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Completed',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`donation_id`),
  KEY `idx_donations_donor` (`donor_id`),
  KEY `idx_donations_recipient` (`recipient_id`),
  KEY `idx_donations_date` (`donation_date`),
  KEY `fk_donation_request` (`request_id`),
  KEY `fk_donation_bank` (`blood_bank_id`),
  CONSTRAINT `fk_donation_donor` FOREIGN KEY (`donor_id`)
    REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_donation_recipient` FOREIGN KEY (`recipient_id`)
    REFERENCES `recipients` (`recipient_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_donation_request` FOREIGN KEY (`request_id`)
    REFERENCES `blood_requests` (`request_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_donation_bank` FOREIGN KEY (`blood_bank_id`)
    REFERENCES `blood_banks` (`bank_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Completed blood donations — the real ledger';

-- --------------------------------------------------------- blood_stock ----
-- Per-bank inventory (brief ERD: blood_stock.blood_bank_id). One row per
-- (bank, blood type).
CREATE TABLE `blood_stock` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `blood_bank_id`       INT(11) NOT NULL COMMENT 'FK -> blood_banks.bank_id',
  `blood_type`          VARCHAR(5) NOT NULL,
  `units_available`     INT(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` INT(11) NOT NULL DEFAULT 5,
  `updated_by`          INT(11) DEFAULT NULL COMMENT 'FK -> login.user_id',
  `last_updated`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_bank_type` (`blood_bank_id`,`blood_type`),
  CONSTRAINT `fk_stock_bank` FOREIGN KEY (`blood_bank_id`)
    REFERENCES `blood_banks` (`bank_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Units in inventory per bank per blood type';

-- -------------------------------------------------------- stock_alerts ----
-- Low-stock alerts per bank per type.
CREATE TABLE `stock_alerts` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `blood_bank_id`  INT(11) NOT NULL COMMENT 'FK -> blood_banks.bank_id',
  `blood_type`     VARCHAR(5) NOT NULL,
  `units_at_alert` INT(11) NOT NULL,
  `threshold`      INT(11) NOT NULL,
  `status`         ENUM('active','resolved') NOT NULL DEFAULT 'active',
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alerts_status` (`status`,`blood_bank_id`,`blood_type`),
  KEY `fk_alert_bank` (`blood_bank_id`),
  CONSTRAINT `fk_alert_bank` FOREIGN KEY (`blood_bank_id`)
    REFERENCES `blood_banks` (`bank_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Low-stock alerts raised against blood_stock';

SET FOREIGN_KEY_CHECKS = 1;
