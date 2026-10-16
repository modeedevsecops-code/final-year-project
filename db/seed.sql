-- ============================================================================
--  BloodLink — QA seed data (v3, aligned with the brief)
--
--  Donors are spread deliberately across the 56-day eligibility boundary so the
--  matching rule can be tested without editing rows:
--    DAY 30 -> NOT eligible · DAY 55 -> NOT eligible · DAY 56 -> eligible · NULL -> eligible
--  All eight blood groups are represented for ABO/Rh compatibility testing.
--
--  Screening: donor_screenings rows at the end (one member pre-cleared, one
--  self-check only, one deferred).
--
--  NEW in v3: blood_banks (5 real Kaduna/Kano/Zaria facilities with coordinates),
--  officers belong to a bank, and blood_stock / stock_alerts are per bank.
--
--  Import AFTER schema.sql:
--    mysql -u root donor_app < db/schema.sql
--    mysql -u root donor_app < db/seed.sql
--
--  A member is one unified account (donor + recipient). Members log in with
--  email + password; every member is also a searchable donor.
--
--  CREDENTIALS
--    Admin     login.php        admin / admin
--    Officers  user-login.php   <email> / Officer@123
--    Members   user-login.php   <email> / Member@123
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `contact_messages`;
TRUNCATE TABLE `stock_alerts`;
TRUNCATE TABLE `blood_stock`;
TRUNCATE TABLE `donations`;
TRUNCATE TABLE `donor_screenings`;
TRUNCATE TABLE `notices`;
TRUNCATE TABLE `blood_requests`;
TRUNCATE TABLE `members`;
TRUNCATE TABLE `hospital_workers`;
TRUNCATE TABLE `blood_banks`;
TRUNCATE TABLE `login`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------ admin --
INSERT INTO `login` (`user_id`, `USERNAME`, `PASSWORD`, `PW_STATUS`) VALUES
(1, 'admin', '$2y$12$52quccz.VxhptI6QK4x1d.qPl2lvfa9/wROxMUZr43H9/ogM8cSzS', 'Active');

-- ------------------------------------------------------------- blood_banks --
-- Real facilities in Kaduna, Zaria and Kano, with geocoded coordinates.
INSERT INTO `blood_banks` (`bank_id`,`name`,`address`,`latitude`,`longitude`,`contact_phone`) VALUES
(1, 'Barau Dikko Teaching Hospital',        'Lafiya Road, Kaduna',                 10.5105000, 7.4165000, '08030000001'),
(2, '44 Nigerian Army Reference Hospital',  'Kawo, Kaduna',                        10.5560000, 7.4270000, '08030000002'),
(3, 'Yusuf Dantsoho Memorial Hospital',     'Tudun Wada, Kaduna',                  10.5090000, 7.4200000, '08030000003'),
(4, 'ABU Teaching Hospital',                'Shika, Zaria, Kaduna State',          11.1113000, 7.7227000, '08030000004'),
(5, 'Aminu Kano Teaching Hospital',         'Zaria Road, Kano',                    11.9800000, 8.5300000, '08030000005');

-- --------------------------------------------------------------- officers --
-- Each officer belongs to a blood bank (hospital_workers.blood_bank_id).
INSERT INTO `hospital_workers` (`worker_id`, `name`, `phone`, `email`, `position`, `blood_bank_id`, `password`) VALUES
(1, 'Maryam Ibrahim', '08127494994', 'maryam@bloodlink.test', 'Blood Bank Officer', 1, '$2y$12$5SPz3nCmiahAt0ovpXc6OeybV163.l07y9fypcMdKX7Fk.vltpLHC'),
(2, 'Samuel Adeyemi', '08031122334', 'samuel@bloodlink.test', 'Blood Bank Officer', 4, '$2y$12$5SPz3nCmiahAt0ovpXc6OeybV163.l07y9fypcMdKX7Fk.vltpLHC');

-- ---------------------------------------------------------------- members --
-- Unified accounts (donor + recipient). Members 1-9 are seeded as active
-- donors spread across the 56-day boundary; members 10-12 are seeded as the
-- people who file the demo requests — but each is equally a searchable donor.
-- All log in with their email + Member@123.
INSERT INTO `members`
  (`member_id`,`name`,`email`,`phone`,`member_code`,`blood_group`,`last_donation_date`,`address`,`latitude`,`longitude`,`password`) VALUES
(1, 'Zainab Auwal',     'zainab@bloodlink.test',  '08023456701', 'BL-KD-001', 'O-',  NULL,
    'Ahmadu Bello Way, Kaduna',            10.5222000,  7.4383000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(2, 'Kamalu Yahaya',    'kamalu@bloodlink.test',  '08023456702', 'BL-KD-002', 'O+',  NULL,
    'Kawo, Kaduna',                        10.5560000,  7.4270000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(3, 'Aliyu Abubakar',   'aliyu@bloodlink.test',   '08023456703', 'BL-KD-003', 'A+',  DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    'Barnawa, Kaduna',                     10.4736000,  7.4165000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(4, 'Fatima Sani',      'fatima@bloodlink.test',  '08023456704', 'BL-KD-004', 'A-',  DATE_SUB(CURDATE(), INTERVAL 56 DAY),
    'Malali, Kaduna',                      10.5480000,  7.4400000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(5, 'Ibrahim Musa',     'ibrahim@bloodlink.test', '08023456705', 'BL-KD-005', 'B+',  DATE_SUB(CURDATE(), INTERVAL 55 DAY),
    'Ungwan Rimi, Kaduna',                 10.5333000,  7.4500000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(6, 'Hauwa Garba',      'hauwa@bloodlink.test',   '08023456706', 'BL-KD-006', 'B-',  DATE_SUB(CURDATE(), INTERVAL 200 DAY),
    'Tudun Wada, Kaduna',                  10.5090000,  7.4200000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(7, 'Yusuf Danladi',    'yusuf@bloodlink.test',   '08023456707', 'BL-KN-007', 'AB+', DATE_SUB(CURDATE(), INTERVAL 120 DAY),
    'Zaria Road, Kano',                    11.9964000,  8.5200000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(8, 'Amina Bello',      'amina@bloodlink.test',   '08023456708', 'BL-KN-008', 'AB-', NULL,
    'Kano City Centre, Kano',              12.0022000,  8.5920000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(9, 'Grace Okonkwo',    'grace@bloodlink.test',   '08023456709', 'BL-KD-009', 'O+',  NULL,
    NULL, NULL, NULL, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(10,'Hassan Umar',      'hassan@bloodlink.test',  '08051112201', 'BL-KD-010', 'O-',  NULL,
    'Barau Dikko Teaching Hospital, Kaduna', 10.5105000, 7.4165000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(11,'Blessing Eze',     'blessing@bloodlink.test','08051112202', 'BL-ZR-011', 'AB+', NULL,
    'ABU Teaching Hospital, Zaria',          11.1113000, 7.7227000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He'),
(12,'Musa Lawal',       'musa@bloodlink.test',    '08051112203', 'BL-KN-012', 'B+',  NULL,
    'Aminu Kano Teaching Hospital, Kano',    11.9800000, 8.5300000, '$2y$12$t2BtcLkDQ735ZZOVdvLT2uU3LZ78uX9CMagcOyUV0Kk3v3vbiu/He');

-- ---------------------------------------------------------- blood_stock ----
-- Per bank per type. Bank 1 (Barau Dikko) has A- and O- BELOW threshold so two
-- active low-stock alerts exist on first load; other banks are healthy.
INSERT INTO `blood_stock` (`blood_bank_id`,`blood_type`,`units_available`,`low_stock_threshold`) VALUES
-- Bank 1 — Barau Dikko
(1,'A+',12,5),(1,'A-',2,5),(1,'B+',9,5),(1,'B-',6,5),(1,'AB+',4,3),(1,'AB-',3,3),(1,'O+',18,8),(1,'O-',7,8),
-- Bank 2 — 44 Army
(2,'A+',10,5),(2,'A-',8,5),(2,'B+',7,5),(2,'B-',6,5),(2,'AB+',5,3),(2,'AB-',4,3),(2,'O+',20,8),(2,'O-',12,8),
-- Bank 3 — Dantsoho
(3,'A+',9,5),(3,'A-',6,5),(3,'B+',8,5),(3,'B-',7,5),(3,'AB+',4,3),(3,'AB-',3,3),(3,'O+',15,8),(3,'O-',10,8),
-- Bank 4 — ABU Zaria
(4,'A+',14,5),(4,'A-',7,5),(4,'B+',11,5),(4,'B-',6,5),(4,'AB+',6,3),(4,'AB-',5,3),(4,'O+',22,8),(4,'O-',13,8),
-- Bank 5 — Aminu Kano
(5,'A+',8,5),(5,'A-',9,5),(5,'B+',10,5),(5,'B-',8,5),(5,'AB+',5,3),(5,'AB-',4,3),(5,'O+',16,8),(5,'O-',11,8);

INSERT INTO `stock_alerts` (`blood_bank_id`,`blood_type`,`units_at_alert`,`threshold`,`status`) VALUES
(1,'A-', 2, 5, 'active'),
(1,'O-', 7, 8, 'active');

-- -------------------------------------------------------- blood_requests --
INSERT INTO `blood_requests`
  (`request_id`,`recipient_id`,`patient_name`,`blood_group`,`units_needed`,`hospital_name`,`location`,`latitude`,`longitude`,`urgency_level`,`status`,`requested_by`,`contact_phone`) VALUES
(1, 10, 'Hassan Umar',  'O-',  2, 'Barau Dikko Teaching Hospital',       'Kaduna',      10.5105000, 7.4165000, 'Critical Emergency', 'Pending',  'Hassan Umar',  '08051112201'),
(2, 11, 'Blessing Eze', 'AB+', 1, 'ABU Teaching Hospital',               'Zaria',       11.1113000, 7.7227000, 'Urgent',             'Pending',  'Blessing Eze', '08051112202'),
(3, 12, 'Musa Lawal',   'B+',  3, 'Aminu Kano Teaching Hospital',        'Kano',        11.9800000, 8.5300000, 'Normal',             'Approved', 'Musa Lawal',   '08051112203'),
(4, 10, 'Hassan Umar',  'A+',  1, 'Barau Dikko Teaching Hospital',       'Kaduna',      10.5105000, 7.4165000, 'Normal',             'Fulfilled','Hassan Umar',  '08051112201');

-- ----------------------------------------------------------- notices -------
INSERT INTO `notices` (`officer_id`,`title`,`message`) VALUES
(1,    'O- donors needed at Barau Dikko', 'We are critically low on O-. Any eligible O- donor please report to the blood bank.'),
(NULL, 'Emergency: O- blood needed at Barau Dikko Teaching Hospital',
       '2 unit(s) of O- needed urgently for patient Hassan Umar at Barau Dikko Teaching Hospital (Kaduna). Contact: 08051112201.');

-- ---- Member (donor) availability (defaults: available=1 / anytime / both) ----
UPDATE members SET is_available = 0 WHERE member_id IN (5, 8);            -- a couple unavailable for demo
UPDATE members SET availability_schedule='emergencies', contact_preference='phone' WHERE member_id = 1;

-- ---- Emergency severity + priority on seeded requests ----
UPDATE blood_requests SET severity='critical', required_within_hours=3,  priority_score=95 WHERE request_id = 1;
UPDATE blood_requests SET severity='severe',   required_within_hours=8,  priority_score=70 WHERE request_id = 2;
UPDATE blood_requests SET severity='moderate', required_within_hours=48, priority_score=40 WHERE request_id = 3;

-- ------------------------------------------------- notifications (demo) ---
-- A few unread notifications so the bell has something to show on first login.
INSERT INTO `notifications` (`recipient_role`,`recipient_id`,`type`,`title`,`body`,`link`) VALUES
('officer', 1, 'request_new', 'New O- request', 'Critical Emergency · 2 unit(s) of O- for Hassan Umar at Barau Dikko Teaching Hospital.', 'match_donor.php?request_id=1'),
('officer', 2, 'request_new', 'New O- request', 'Critical Emergency · 2 unit(s) of O- for Hassan Umar at Barau Dikko Teaching Hospital.', 'match_donor.php?request_id=1'),
('officer', 1, 'low_stock',   'Low stock: O- at Barau Dikko Teaching Hospital', '7 unit(s) left — below the threshold of 8.', 'officer_dashboard.php'),
('admin',   1, 'low_stock',   'Low stock: A- at Barau Dikko Teaching Hospital', '2 unit(s) left — below the threshold of 5.', 'manage_blood_stock.php?bank=1'),
('admin',   1, 'contact',     'New message: Partnership enquiry', 'From Dr. Bala Usman (bala.usman@example.test).', 'messages.php'),
('member',  1, 'emergency',   'Emergency: O- needed at Barau Dikko Teaching Hospital', '2 unit(s) needed urgently. You are a compatible, eligible donor — please contact the blood bank.', 'emergency_alerts.php'),
('member', 10, 'request_status', 'Request received', 'Your O- request for Hassan Umar is Pending — officers have been notified.', 'request_history.php');

INSERT INTO `contact_messages` (`name`,`email`,`subject`,`message`) VALUES
('Dr. Bala Usman', 'bala.usman@example.test', 'Partnership enquiry',
 'Our hospital in Zaria would like to register as a partner blood bank. Who should we speak to about getting set up on BloodLink?');

-- ---------------------------------------------------- donor_screenings ---
-- Zainab (O-) did the online self-check yesterday and was screened by Maryam at
-- Barau Dikko today, so she can be confirmed against request #1 straight away.
-- Kamalu has only done the self-check: an officer must screen him before a
-- walk-in donation. Grace's self-check flagged malaria three days ago, so she is
-- deferred for 28 days and left out of donor matching.
INSERT INTO `donor_screenings`
(`member_id`,`source`,`officer_id`,`blood_bank_id`,`answers`,`sex`,`genotype`,`weight_kg`,`hemoglobin_g_dl`,`bp_systolic`,`bp_diastolic`,`pulse_bpm`,`temperature_c`,
 `hiv_result`,`hbsag_result`,`hcv_result`,`syphilis_result`,`malaria_result`,`outcome`,`deferral_reasons`,`deferred_until`,`screened_at`) VALUES
(1, 'self',    NULL, NULL, '{"unwell":"no","age_ok":"yes","weight_ok":"yes","hiv_hep":"no","blood_disorder":"no","chronic":"no","injected_drugs":"no","risk_partner":"no","malaria":"no","antibiotics":"no","tattoo":"no","transfusion":"no","surgery":"no","pregnant":"no"}', 'female', 'AA', NULL, NULL, NULL, NULL, NULL, NULL,
 'not_tested','not_tested','not_tested','not_tested','not_tested', 'eligible', NULL, NULL, NOW() - INTERVAL 1 DAY),
(1, 'officer', 1, 1,       '{"unwell":"no","age_ok":"yes","weight_ok":"yes","hiv_hep":"no","blood_disorder":"no","chronic":"no","injected_drugs":"no","risk_partner":"no","malaria":"no","antibiotics":"no","tattoo":"no","transfusion":"no","surgery":"no","pregnant":"no"}', 'female', 'AA', 61.0, 13.4, 118, 76, 72, 36.6,
 'negative','negative','negative','negative','negative', 'eligible', NULL, NULL, NOW()),
(2, 'self',    NULL, NULL, '{"unwell":"no","age_ok":"yes","weight_ok":"yes","hiv_hep":"no","blood_disorder":"no","chronic":"no","injected_drugs":"no","risk_partner":"no","malaria":"no","antibiotics":"no","tattoo":"no","transfusion":"no","surgery":"no"}', 'male', 'AS', NULL, NULL, NULL, NULL, NULL, NULL,
 'not_tested','not_tested','not_tested','not_tested','not_tested', 'eligible', NULL, NULL, NOW()),
(9, 'self',    NULL, NULL, '{"unwell":"no","age_ok":"yes","weight_ok":"yes","hiv_hep":"no","blood_disorder":"no","chronic":"no","injected_drugs":"no","risk_partner":"no","malaria":"yes","antibiotics":"no","tattoo":"no","transfusion":"no","surgery":"no","pregnant":"no"}', 'female', 'unknown', NULL, NULL, NULL, NULL, NULL, NULL,
 'not_tested','not_tested','not_tested','not_tested','not_tested', 'temporary_deferral', 'Malaria in the last 4 weeks', CURDATE() + INTERVAL 25 DAY, NOW() - INTERVAL 3 DAY);
