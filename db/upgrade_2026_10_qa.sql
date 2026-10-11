-- ============================================================================
--  BloodLink — upgrade for an EXISTING database (October 2026 QA fixes).
--  Fresh installs don't need this: db/schema.sql already includes it.
--
--  1. Member IDs become unique (login accepts email OR Member ID, so two members
--     sharing an ID could lock each other out). Existing duplicates are renamed
--     first by appending the member's numeric id.
--  2. Old double-escaped emergency alerts are turned back into plain text.
--  3. Adds the notifications (bell) and contact_messages (admin inbox) tables.
--  4. Drops tables left over from the student-logbook (SIWES) version and the
--     old donor/recipient split — nothing in BloodLink uses them.
--  5. Requests at a registered bank get that bank's exact coordinates.
--
--  Run:  mysql -u root -p donor_app < db/upgrade_2026_10_qa.sql   (safe to re-run)
-- ============================================================================

UPDATE members m
JOIN (
    SELECT member_code, MIN(member_id) AS keep_id
    FROM members GROUP BY member_code HAVING COUNT(*) > 1
) d ON d.member_code = m.member_code AND m.member_id <> d.keep_id
SET m.member_code = CONCAT(m.member_code, '-', m.member_id);

UPDATE members SET member_code = UPPER(member_code), email = LOWER(email);

-- Swap the plain index for a unique one (skipped if already done, so the
-- script is safe to run more than once).
SET @has_unique := (SELECT COUNT(*) FROM information_schema.statistics
                    WHERE table_schema = DATABASE() AND table_name = 'members' AND index_name = 'uq_members_member_code');
SET @has_plain  := (SELECT COUNT(*) FROM information_schema.statistics
                    WHERE table_schema = DATABASE() AND table_name = 'members' AND index_name = 'idx_members_member_code');
SET @sql := IF(@has_unique > 0, 'DO 0',
              IF(@has_plain > 0,
                 'ALTER TABLE members DROP INDEX idx_members_member_code, ADD UNIQUE KEY uq_members_member_code (member_code)',
                 'ALTER TABLE members ADD UNIQUE KEY uq_members_member_code (member_code)'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Emergency alerts used to be HTML-escaped before saving AND again on display,
-- so "&" showed as "&amp;". They are now stored as plain text; un-escape old rows.
UPDATE notices SET
    title   = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title,   '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&'),
    message = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(message, '&lt;', '<'), '&gt;', '>'), '&quot;', '"'), '&#039;', ''''), '&amp;', '&');

-- Notifications (the bell) and contact-form messages (admin Messages inbox).
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `recipient_role`  ENUM('admin','officer','member') NOT NULL,
  `recipient_id`    INT(11) NOT NULL,
  `type`            VARCHAR(30)  NOT NULL,
  `title`           VARCHAR(200) NOT NULL,
  `body`            VARCHAR(500) DEFAULT NULL,
  `link`            VARCHAR(255) DEFAULT NULL,
  `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notif_recipient` (`recipient_role`, `recipient_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `subject`    VARCHAR(150) NOT NULL,
  `message`    TEXT NOT NULL,
  `status`     ENUM('new','read') NOT NULL DEFAULT 'new',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_contact_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tables left over from the earlier student-logbook (SIWES) version and the
-- pre-merge donor/recipient split. Nothing in BloodLink reads or writes them.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `attendance`, `chat_messages`, `logbook_entries`, `projects`, `seminar`,
                     `staff`, `students`, `weekly_summaries`, `allocation`, `timetable`,
                     `donors`, `recipients`;
SET FOREIGN_KEY_CHECKS = 1;

-- Requests filed at a registered blood bank take that bank's exact coordinates
-- (free-text geocoding of the hospital name could land kilometres away).
UPDATE blood_requests br
JOIN blood_banks bb ON LOWER(TRIM(bb.name)) = LOWER(TRIM(br.hospital_name))
SET br.latitude = bb.latitude, br.longitude = bb.longitude
WHERE bb.latitude IS NOT NULL;
