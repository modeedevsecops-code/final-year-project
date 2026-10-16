-- ============================================================================
--  BloodLink — upgrade: donor health screening (October 2026)
--
--  Adds the donor_screenings table and donations.screening_id to a database
--  that already has the 11-table schema. Safe to run more than once.
--
--    mysql -u root -proot donor_app < db/upgrade_2026_10_screening.sql
--
--  In Local: Database tab -> Open Adminer -> donor_app -> Import -> this file.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `donor_screenings` (
  `screening_id`      INT(11) NOT NULL AUTO_INCREMENT,
  `member_id`         INT(11) NOT NULL COMMENT 'FK -> members.member_id (the donor screened)',
  `source`            ENUM('self','officer') NOT NULL COMMENT 'Member self-check or officer screening at the bank',
  `officer_id`        INT(11) DEFAULT NULL COMMENT 'FK -> hospital_workers.worker_id (NULL for a self-check)',
  `blood_bank_id`     INT(11) DEFAULT NULL COMMENT 'FK -> blood_banks.bank_id where it was done',
  `answers`           JSON NOT NULL COMMENT 'Health questionnaire answers (question key -> yes/no)',
  `sex`               ENUM('male','female') DEFAULT NULL,
  `genotype`          ENUM('AA','AS','AC','SS','SC','unknown') DEFAULT NULL,
  `weight_kg`         DECIMAL(5,1) DEFAULT NULL,
  `hemoglobin_g_dl`   DECIMAL(4,1) DEFAULT NULL,
  `bp_systolic`       SMALLINT DEFAULT NULL,
  `bp_diastolic`      SMALLINT DEFAULT NULL,
  `pulse_bpm`         SMALLINT DEFAULT NULL,
  `temperature_c`     DECIMAL(4,1) DEFAULT NULL,
  `hiv_result`        ENUM('negative','positive','not_tested') NOT NULL DEFAULT 'not_tested',
  `hbsag_result`      ENUM('negative','positive','not_tested') NOT NULL DEFAULT 'not_tested' COMMENT 'Hepatitis B surface antigen',
  `hcv_result`        ENUM('negative','positive','not_tested') NOT NULL DEFAULT 'not_tested' COMMENT 'Hepatitis C antibody',
  `syphilis_result`   ENUM('negative','positive','not_tested') NOT NULL DEFAULT 'not_tested',
  `malaria_result`    ENUM('negative','positive','not_tested') NOT NULL DEFAULT 'not_tested',
  `outcome`           ENUM('eligible','temporary_deferral','permanent_deferral') NOT NULL,
  `deferral_reasons`  VARCHAR(500) DEFAULT NULL COMMENT 'Why the donor was deferred (shown to officers)',
  `deferred_until`    DATE DEFAULT NULL COMMENT 'Temporary deferral ends on this date',
  `notes`             VARCHAR(500) DEFAULT NULL,
  `screened_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`screening_id`),
  KEY `idx_screening_member` (`member_id`,`source`,`screened_at`),
  KEY `fk_screening_officer` (`officer_id`),
  KEY `fk_screening_bank` (`blood_bank_id`),
  CONSTRAINT `fk_screening_member` FOREIGN KEY (`member_id`)
    REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_screening_officer` FOREIGN KEY (`officer_id`)
    REFERENCES `hospital_workers` (`worker_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_screening_bank` FOREIGN KEY (`blood_bank_id`)
    REFERENCES `blood_banks` (`bank_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Pre-donation health screenings (self-check and officer screening)';

-- donations.screening_id (+ index and FK), only if it isn't there yet.
SET @has_col = (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'donations' AND column_name = 'screening_id');
SET @sql = IF(@has_col = 0,
  'ALTER TABLE `donations`
     ADD COLUMN `screening_id` INT(11) DEFAULT NULL COMMENT ''FK -> donor_screenings.screening_id that cleared the donor'' AFTER `status`,
     ADD KEY `fk_donation_screening` (`screening_id`),
     ADD CONSTRAINT `fk_donation_screening` FOREIGN KEY (`screening_id`)
       REFERENCES `donor_screenings` (`screening_id`) ON DELETE SET NULL',
  'SELECT ''donations.screening_id already present''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `notifications` MODIFY `type` VARCHAR(30) NOT NULL
  COMMENT 'request_new / request_status / donation / screening / emergency / low_stock / member_new / contact / alert';
