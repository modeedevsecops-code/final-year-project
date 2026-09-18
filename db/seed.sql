-- ============================================================================
--  BloodLink — QA seed data
--
--  Purpose-built for the QA script, not for a demo screenshot. In particular
--  the donors below are spread deliberately across the 56-day eligibility
--  boundary so the matching rule can be tested without editing rows by hand:
--
--    DAY 30  -> must NOT appear as eligible
--    DAY 56  -> must appear (the rule is "at least 56 days")
--    DAY 55  -> must NOT appear (the off-by-one guard)
--    NULL    -> never donated, must appear
--
--  All eight blood groups are represented so ABO/Rh compatibility can be
--  checked end to end: an O- request must match only O- donors; an AB+
--  request must match all eight.
--
--  Coordinates are real locations in Kaduna and Kano.
--
--  Import AFTER schema.sql:
--    mysql -u root donor_app < db/schema.sql
--    mysql -u root donor_app < db/seed.sql
--
--  CREDENTIALS
--    Admin      login.php        admin / admin
--    Officers   user-login.php   <email> / Officer@123
--    Donors     user-login.php   <reg_no> / Donor@123
--    Recipients user-login.php   <email>  / Recipient@123
--
--  All passwords are bcrypt hashes (Phase 2). The plaintext equivalents are the
--  ones in the CREDENTIALS block above. The login code verifies with
--  password_verify() and will also transparently upgrade any legacy plaintext
--  row to a hash on first successful login.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `stock_alerts`;
TRUNCATE TABLE `blood_stock`;
TRUNCATE TABLE `donations`;
TRUNCATE TABLE `chat_messages`;
TRUNCATE TABLE `attendance`;
TRUNCATE TABLE `weekly_summaries`;
TRUNCATE TABLE `logbook_entries`;
TRUNCATE TABLE `notices`;
TRUNCATE TABLE `projects`;
TRUNCATE TABLE `blood_requests`;
TRUNCATE TABLE `recipients`;
TRUNCATE TABLE `seminar`;
TRUNCATE TABLE `students`;
TRUNCATE TABLE `staff`;
TRUNCATE TABLE `login`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------ admin --
INSERT INTO `login` (`user_id`, `USERNAME`, `PASSWORD`, `PW_STATUS`) VALUES
(1, 'admin', '$2y$12$52quccz.VxhptI6QK4x1d.qPl2lvfa9/wROxMUZr43H9/ogM8cSzS', 'Active');

-- --------------------------------------------------------------- officers --
INSERT INTO `staff` (`staff_id`, `staff_name`, `phone`, `email`, `position`, `password`) VALUES
(1, 'Maryam Ibrahim', '08127494994', 'maryam@bloodlink.test', 'Blood Bank Officer – Barau Dikko Teaching Hospital', '$2y$12$5SPz3nCmiahAt0ovpXc6OeybV163.l07y9fypcMdKX7Fk.vltpLHC'),
(2, 'Samuel Adeyemi', '08031122334', 'samuel@bloodlink.test', 'Blood Bank Officer – ABU Teaching Hospital, Zaria',   '$2y$12$5SPz3nCmiahAt0ovpXc6OeybV163.l07y9fypcMdKX7Fk.vltpLHC');

-- ----------------------------------------------------------------- donors --
-- year_of_study / status are intentionally left NULL: v2 stores the blood
-- group in blood_group only. If either column reappears with a blood group in
-- it, something is still writing to the legacy column.
INSERT INTO `students`
  (`student_id`,`name`,`email`,`phone`,`reg_no`,`blood_group`,`last_donation_date`,`address`,`latitude`,`longitude`,`password`) VALUES
-- Never donated -> always eligible
(1, 'Zainab Auwal',     'zainab@bloodlink.test',  '08023456701', 'BL-KD-001', 'O-',  NULL,
    'Ahmadu Bello Way, Kaduna',            10.5222000,  7.4383000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(2, 'Kamalu Yahaya',    'kamalu@bloodlink.test',  '08023456702', 'BL-KD-002', 'O+',  NULL,
    'Kawo, Kaduna',                        10.5560000,  7.4270000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
-- DAY 30: NOT eligible. Must be absent from match_donor.php.
(3, 'Aliyu Abubakar',   'aliyu@bloodlink.test',   '08023456703', 'BL-KD-003', 'A+',  DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    'Barnawa, Kaduna',                     10.4736000,  7.4165000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
-- DAY 56 exactly: MUST be eligible. The boundary case.
(4, 'Fatima Sani',      'fatima@bloodlink.test',  '08023456704', 'BL-KD-004', 'A-',  DATE_SUB(CURDATE(), INTERVAL 56 DAY),
    'Malali, Kaduna',                      10.5480000,  7.4400000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
-- DAY 55: must NOT be eligible. Catches an off-by-one in the rule.
(5, 'Ibrahim Musa',     'ibrahim@bloodlink.test', '08023456705', 'BL-KD-005', 'B+',  DATE_SUB(CURDATE(), INTERVAL 55 DAY),
    'Ungwan Rimi, Kaduna',                 10.5333000,  7.4500000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
-- Long past the window -> eligible
(6, 'Hauwa Garba',      'hauwa@bloodlink.test',   '08023456706', 'BL-KD-006', 'B-',  DATE_SUB(CURDATE(), INTERVAL 200 DAY),
    'Tudun Wada, Kaduna',                  10.5090000,  7.4200000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(7, 'Yusuf Danladi',    'yusuf@bloodlink.test',   '08023456707', 'BL-KN-007', 'AB+', DATE_SUB(CURDATE(), INTERVAL 120 DAY),
    'Zaria Road, Kano',                    11.9964000,  8.5200000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(8, 'Amina Bello',      'amina@bloodlink.test',   '08023456708', 'BL-KN-008', 'AB-', NULL,
    'Kano City Centre, Kano',              12.0022000,  8.5920000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
-- No coordinates: the map must skip this row without a JS error.
(9, 'Grace Okonkwo',    'grace@bloodlink.test',   '08023456709', 'BL-KD-009', 'O+',  NULL,
    NULL, NULL, NULL, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u');

-- ------------------------------------------------------------- recipients --
-- Password for all three: Recipient@123
INSERT INTO `recipients`
  (`recipient_id`,`name`,`email`,`phone`,`address`,`blood_group`,`latitude`,`longitude`,`password`) VALUES
(1, 'Hassan Umar',    'hassan@bloodlink.test',  '08051112201', 'Barau Dikko Teaching Hospital, Kaduna', 'O-',
    10.5105000, 7.4165000, '$2y$12$evuR8xaawLq2FXYrNI4tWulKnxxLbD/dKP6q.e84A/GXHM0mLEhgG'),
(2, 'Blessing Eze',   'blessing@bloodlink.test','08051112202', 'ABU Teaching Hospital, Zaria',          'AB+',
    11.1113000, 7.7227000, '$2y$12$evuR8xaawLq2FXYrNI4tWulKnxxLbD/dKP6q.e84A/GXHM0mLEhgG'),
(3, 'Musa Lawal',     'musa@bloodlink.test',    '08051112203', 'Aminu Kano Teaching Hospital, Kano',    'B+',
    11.9800000, 8.5300000, '$2y$12$evuR8xaawLq2FXYrNI4tWulKnxxLbD/dKP6q.e84A/GXHM0mLEhgG');

-- ---------------------------------------------------------- blood_stock ----
-- A- is seeded BELOW its threshold so an active alert exists on first load and
-- dashboard.php has a non-zero "Low Stock Alerts" count to verify against.
INSERT INTO `blood_stock` (`blood_type`,`units_available`,`low_stock_threshold`) VALUES
('A+',  12, 5),
('A-',   2, 5),
('B+',   9, 5),
('B-',   6, 5),
('AB+',  4, 3),
('AB-',  3, 3),
('O+',  18, 8),
('O-',   7, 8);

INSERT INTO `stock_alerts` (`blood_type`,`units_at_alert`,`threshold`,`status`) VALUES
('A-', 2, 5, 'active'),
('O-', 7, 8, 'active');

-- -------------------------------------------------------- blood_requests --
INSERT INTO `blood_requests`
  (`request_id`,`recipient_id`,`patient_name`,`blood_group`,`units_needed`,`hospital_name`,`location`,`latitude`,`longitude`,`urgency_level`,`status`,`requested_by`,`contact_phone`) VALUES
-- O- : the strictest case. Only O- donors (1) may match.
(1, 1, 'Hassan Umar',  'O-',  2, 'Barau Dikko Teaching Hospital',       'Kaduna',      10.5105000, 7.4165000, 'Critical Emergency', 'Pending',  'Hassan Umar',  '08051112201'),
-- AB+ : the universal recipient. All eight groups are compatible.
(2, 2, 'Blessing Eze', 'AB+', 1, 'ABU Teaching Hospital',               'Zaria',       11.1113000, 7.7227000, 'Urgent',             'Pending',  'Blessing Eze', '08051112202'),
(3, 3, 'Musa Lawal',   'B+',  3, 'Aminu Kano Teaching Hospital',        'Kano',        11.9800000, 8.5300000, 'Normal',             'Approved', 'Musa Lawal',   '08051112203'),
-- Already fulfilled: must not appear in the officer's open queue.
(4, 1, 'Hassan Umar',  'A+',  1, 'Barau Dikko Teaching Hospital',       'Kaduna',      10.5105000, 7.4165000, 'Normal',             'Fulfilled','Hassan Umar',  '08051112201');

-- --------------------------------------------------- officer assignments --
INSERT INTO `projects` (`project_id`,`title`,`assigned_student`,`assigned_supervisor`,`status`,`methodology`,`description`) VALUES
(1, 'Barau Dikko Teaching Hospital, Kaduna', 1, 1, 'In Progress', 'Every 3 months', 'Regular voluntary donor'),
(2, 'Barau Dikko Teaching Hospital, Kaduna', 3, 1, 'In Progress', 'Every 6 months', 'Walk-in donor'),
(3, 'ABU Teaching Hospital, Zaria',          7, 2, 'In Progress', 'Every 4 months', 'Regular voluntary donor');

-- ----------------------------------------------------------- notices -------
-- supervisor_id NULL exercises the nullable column added in v2.
INSERT INTO `notices` (`supervisor_id`,`title`,`message`) VALUES
(1,    'O- donors needed at Barau Dikko', 'We are critically low on O-. Any eligible O- donor please report to the blood bank.'),
(NULL, 'Emergency: O- blood needed at Barau Dikko Teaching Hospital',
       '2 unit(s) of O- needed urgently for patient Hassan Umar at Barau Dikko Teaching Hospital (Kaduna). Contact: 08051112201.');

-- ----------------------------------------------------------- blood drive --
INSERT INTO `seminar` (`seminar_title`,`seminar_date`,`seminar_time`,`venue`,`level`) VALUES
('World Blood Donor Day Drive', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '09:00:00', 'Barau Dikko Teaching Hospital, Kaduna', 'All blood groups'),
('Campus Blood Drive',          DATE_ADD(CURDATE(), INTERVAL 30 DAY), '10:00:00', 'ABU Zaria Main Campus',                 'O- and O+ priority');

-- ------------------------------------------------------------ chat sample --
INSERT INTO `chat_messages` (`supervisor_id`,`student_id`,`sender`,`message`,`is_read`) VALUES
(1, 1, 'supervisor', 'Hello Zainab, are you available to donate this week?', 1),
(1, 1, 'student',    'Yes sir, I am available on Thursday.',                  0);
