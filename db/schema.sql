-- ============================================================================
--  BloodLink — Smart Blood Bank Management System
--  Canonical schema (v2). Replaces the stale phpMyAdmin dump in donor_app.sql.
--
--  What changed vs. donor_app.sql, and why:
--    * Adds the 4 tables the code queries but the dump never created:
--      recipients, donations, blood_stock, stock_alerts.
--    * students.blood_group is now a real column. The app previously stored a
--      donor's blood group in year_of_study (register_donor.php), read it from
--      year_of_study (reports.php), read it from status (geo_map.php), and
--      matched on blood_group (functions.php) — which did not exist. All four
--      now agree on blood_group. year_of_study is kept nullable for one release
--      so old rows survive the migration, then it can be dropped.
--    * Settles the blood_stock / stock_alerts schema conflict in favour of the
--      admin page's shape (id / blood_type / status='active'), which is the
--      richer of the two. process_donation() has been updated to match.
--    * Adds latitude/longitude to students, recipients and blood_requests so
--      the geo-location feature has somewhere to write.
--    * notices.supervisor_id is now NULL-able, so system-generated emergency
--      alerts (request_blood.php) actually persist instead of failing silently.
--    * Drops the exam-invigilation leftovers (timetable, allocation) inherited
--      from the SIWES codebase this project was adapted from.
--
--  Import:  mysql -u root donor_app < db/schema.sql
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `allocation`;
DROP TABLE IF EXISTS `timetable`;
DROP TABLE IF EXISTS `stock_alerts`;
DROP TABLE IF EXISTS `blood_stock`;
DROP TABLE IF EXISTS `donations`;
DROP TABLE IF EXISTS `recipients`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `weekly_summaries`;
DROP TABLE IF EXISTS `logbook_entries`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `blood_requests`;
DROP TABLE IF EXISTS `seminar`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `login`;

-- ---------------------------------------------------------------- login ----
-- Administrator accounts. PASSWORD is plaintext today; Phase 2 replaces this
-- with password_hash() and widens the column to 255.
CREATE TABLE `login` (
  `user_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `USERNAME`  VARCHAR(50)  NOT NULL,
  `PASSWORD`  VARCHAR(255) NOT NULL,
  `PW_STATUS` VARCHAR(20)  NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_login_username` (`USERNAME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------- staff ----
-- Hospital officers. (Table name retained from the SIWES original.)
CREATE TABLE `staff` (
  `staff_id`        INT(11) NOT NULL AUTO_INCREMENT,
  `staff_name`      VARCHAR(100) NOT NULL,
  `phone`           VARCHAR(20)  NOT NULL,
  `email`           VARCHAR(100) NOT NULL,
  `position`        VARCHAR(150) NOT NULL,
  `password`        VARCHAR(255) NOT NULL,
  `date_registered` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `uq_staff_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Hospital officers / blood bank staff';

-- ------------------------------------------------------------- students ----
-- Blood donors. (Table name retained from the SIWES original.)
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
  `year_of_study`       VARCHAR(50)  DEFAULT NULL COMMENT 'LEGACY: held the blood group before v2. Drop after migration.',
  `status`              VARCHAR(50)  DEFAULT NULL COMMENT 'LEGACY: geo_map.php read the blood group from here.',
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
-- NEW in v2. Queried by register_recipient.php, user_login(), recipient_form.php
-- and manage_recipients.php, but never created by the old dump.
-- Standardised on recipient_id (manage_recipients.php ordered by `id`).
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

-- ------------------------------------------------------------- seminar ----
-- Blood donation drives / events.
CREATE TABLE `seminar` (
  `seminar_id`    INT(11) NOT NULL AUTO_INCREMENT,
  `seminar_title` VARCHAR(255) NOT NULL COMMENT 'Blood drive title',
  `seminar_date`  DATE NOT NULL,
  `seminar_time`  TIME NOT NULL,
  `venue`         VARCHAR(255) NOT NULL,
  `level`         VARCHAR(100) DEFAULT NULL COMMENT 'Target blood group / eligibility',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seminar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Blood donation drives / events';

-- ------------------------------------------------------- blood_requests ----
-- recipient_id, latitude and longitude are new in v2. request_blood.php used to
-- add recipient_id at runtime with MariaDB-only "ADD COLUMN IF NOT EXISTS",
-- which is a syntax error on MySQL; declaring it here removes that dependency.
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

-- ------------------------------------------------------------ projects ----
-- Donor-to-officer assignments.
CREATE TABLE `projects` (
  `project_id`          INT(11) NOT NULL AUTO_INCREMENT,
  `title`               VARCHAR(255) NOT NULL COMMENT 'Hospital / blood bank name',
  `assigned_student`    INT(11) NOT NULL COMMENT 'FK -> students.student_id (donor)',
  `assigned_supervisor` INT(11) NOT NULL COMMENT 'FK -> staff.staff_id (officer)',
  `status`              VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `methodology`         VARCHAR(255) DEFAULT NULL COMMENT 'Donation schedule / frequency',
  `description`         TEXT DEFAULT NULL,
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  KEY `fk_project_student` (`assigned_student`),
  KEY `fk_project_supervisor` (`assigned_supervisor`),
  CONSTRAINT `fk_project_student` FOREIGN KEY (`assigned_student`)
    REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_supervisor` FOREIGN KEY (`assigned_supervisor`)
    REFERENCES `staff` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Donor-to-officer assignments';

-- ------------------------------------------------------------- notices ----
-- Emergency alerts. supervisor_id is NULL-able in v2: request_blood.php posts
-- system-generated alerts with no officer attached, and the old NOT NULL made
-- that insert fail silently while showing the user a success message.
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

-- ----------------------------------------------------- logbook_entries ----
-- Donor-submitted activity log. NOTE: this is NOT the donation ledger — that is
-- the donations table below. reports.php currently counts rows here as
-- "Total Donations", which Phase 4 repoints.
CREATE TABLE `logbook_entries` (
  `log_id`             INT(11) NOT NULL AUTO_INCREMENT,
  `student_id`         INT(11) NOT NULL,
  `entry_date`         DATE NOT NULL,
  `activities`         TEXT NOT NULL,
  `supervisor_comment` TEXT DEFAULT NULL,
  `status`             ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `fk_log_student` (`student_id`),
  CONSTRAINT `fk_log_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------- weekly_summaries ----
CREATE TABLE `weekly_summaries` (
  `summary_id`         INT(11) NOT NULL AUTO_INCREMENT,
  `student_id`         INT(11) NOT NULL,
  `week_start`         DATE NOT NULL,
  `week_end`           DATE NOT NULL,
  `summary`            TEXT NOT NULL,
  `status`             ENUM('pending','approved','rejected') DEFAULT 'pending',
  `supervisor_comment` TEXT DEFAULT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`summary_id`),
  KEY `fk_summary_student` (`student_id`),
  CONSTRAINT `fk_summary_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------- chat_messages ----
CREATE TABLE `chat_messages` (
  `message_id`    INT(11) NOT NULL AUTO_INCREMENT,
  `supervisor_id` INT(11) NOT NULL,
  `student_id`    INT(11) NOT NULL,
  `sender`        ENUM('supervisor','student') NOT NULL,
  `message`       TEXT NOT NULL,
  `is_read`       TINYINT(1) DEFAULT 0,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_chat_thread` (`supervisor_id`,`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Chat between hospital officers and donors';

-- ---------------------------------------------------------- attendance ----
CREATE TABLE `attendance` (
  `attendance_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `supervisor_id`   INT(11) NOT NULL,
  `student_id`      INT(11) NOT NULL,
  `project_id`      INT(11) NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status`          ENUM('Present','Absent') DEFAULT 'Absent',
  `remarks`         VARCHAR(255) DEFAULT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Donor check-in at blood drives';

-- ----------------------------------------------------------- donations ----
-- NEW in v2. The real donation ledger, written transactionally by
-- process_donation(). Never created by the old dump.
CREATE TABLE `donations` (
  `donation_id`   INT(11) NOT NULL AUTO_INCREMENT,
  `donor_id`      INT(11) NOT NULL COMMENT 'FK -> students.student_id',
  `recipient_id`  INT(11) DEFAULT NULL,
  `request_id`    INT(11) DEFAULT NULL,
  `officer_id`    INT(11) NOT NULL COMMENT 'FK -> staff.staff_id, who confirmed it',
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
  CONSTRAINT `fk_donation_donor` FOREIGN KEY (`donor_id`)
    REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_donation_recipient` FOREIGN KEY (`recipient_id`)
    REFERENCES `recipients` (`recipient_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_donation_request` FOREIGN KEY (`request_id`)
    REFERENCES `blood_requests` (`request_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Completed blood donations — the real ledger';

-- --------------------------------------------------------- blood_stock ----
-- NEW in v2. Schema conflict resolved in favour of manage_blood_stock.php
-- (id / blood_type). process_donation() has been updated to match.
CREATE TABLE `blood_stock` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `blood_type`          VARCHAR(5) NOT NULL,
  `units_available`     INT(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` INT(11) NOT NULL DEFAULT 5,
  `updated_by`          INT(11) DEFAULT NULL COMMENT 'FK -> login.user_id',
  `last_updated`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_blood_type` (`blood_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Units in inventory per blood type';

-- -------------------------------------------------------- stock_alerts ----
-- NEW in v2. Uses status='active'/'resolved' (the admin page's vocabulary);
-- process_donation() previously wrote 'unresolved' and has been updated.
CREATE TABLE `stock_alerts` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `blood_type`     VARCHAR(5) NOT NULL,
  `units_at_alert` INT(11) NOT NULL,
  `threshold`      INT(11) NOT NULL,
  `status`         ENUM('active','resolved') NOT NULL DEFAULT 'active',
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alerts_status` (`status`,`blood_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Low-stock alerts raised against blood_stock';

SET FOREIGN_KEY_CHECKS = 1;
