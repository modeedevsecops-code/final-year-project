-- ============================================================================
--  BloodLink — QA seed data (v3, aligned with the brief)
--
--  Donors are spread deliberately across the 56-day eligibility boundary so the
--  matching rule can be tested without editing rows:
--    DAY 30 -> NOT eligible · DAY 55 -> NOT eligible · DAY 56 -> eligible · NULL -> eligible
--  All eight blood groups are represented for ABO/Rh compatibility testing.
--
--  NEW in v3: blood_banks (5 real Kaduna/Kano/Zaria facilities with coordinates),
--  officers belong to a bank, and blood_stock / stock_alerts are per bank.
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
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `stock_alerts`;
TRUNCATE TABLE `blood_stock`;
TRUNCATE TABLE `donations`;
TRUNCATE TABLE `notices`;
TRUNCATE TABLE `blood_requests`;
TRUNCATE TABLE `recipients`;
TRUNCATE TABLE `students`;
TRUNCATE TABLE `staff`;
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
-- Each officer belongs to a blood bank (staff.blood_bank_id).
INSERT INTO `staff` (`staff_id`, `staff_name`, `phone`, `email`, `position`, `blood_bank_id`, `password`) VALUES
(1, 'Maryam Ibrahim', '08127494994', 'maryam@bloodlink.test', 'Blood Bank Officer', 1, '$2y$12$5SPz3nCmiahAt0ovpXc6OeybV163.l07y9fypcMdKX7Fk.vltpLHC'),
(2, 'Samuel Adeyemi', '08031122334', 'samuel@bloodlink.test', 'Blood Bank Officer', 4, '$2y$12$5SPz3nCmiahAt0ovpXc6OeybV163.l07y9fypcMdKX7Fk.vltpLHC');

-- ----------------------------------------------------------------- donors --
INSERT INTO `students`
  (`student_id`,`name`,`email`,`phone`,`reg_no`,`blood_group`,`last_donation_date`,`address`,`latitude`,`longitude`,`password`) VALUES
(1, 'Zainab Auwal',     'zainab@bloodlink.test',  '08023456701', 'BL-KD-001', 'O-',  NULL,
    'Ahmadu Bello Way, Kaduna',            10.5222000,  7.4383000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(2, 'Kamalu Yahaya',    'kamalu@bloodlink.test',  '08023456702', 'BL-KD-002', 'O+',  NULL,
    'Kawo, Kaduna',                        10.5560000,  7.4270000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(3, 'Aliyu Abubakar',   'aliyu@bloodlink.test',   '08023456703', 'BL-KD-003', 'A+',  DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    'Barnawa, Kaduna',                     10.4736000,  7.4165000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(4, 'Fatima Sani',      'fatima@bloodlink.test',  '08023456704', 'BL-KD-004', 'A-',  DATE_SUB(CURDATE(), INTERVAL 56 DAY),
    'Malali, Kaduna',                      10.5480000,  7.4400000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(5, 'Ibrahim Musa',     'ibrahim@bloodlink.test', '08023456705', 'BL-KD-005', 'B+',  DATE_SUB(CURDATE(), INTERVAL 55 DAY),
    'Ungwan Rimi, Kaduna',                 10.5333000,  7.4500000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(6, 'Hauwa Garba',      'hauwa@bloodlink.test',   '08023456706', 'BL-KD-006', 'B-',  DATE_SUB(CURDATE(), INTERVAL 200 DAY),
    'Tudun Wada, Kaduna',                  10.5090000,  7.4200000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(7, 'Yusuf Danladi',    'yusuf@bloodlink.test',   '08023456707', 'BL-KN-007', 'AB+', DATE_SUB(CURDATE(), INTERVAL 120 DAY),
    'Zaria Road, Kano',                    11.9964000,  8.5200000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(8, 'Amina Bello',      'amina@bloodlink.test',   '08023456708', 'BL-KN-008', 'AB-', NULL,
    'Kano City Centre, Kano',              12.0022000,  8.5920000, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u'),
(9, 'Grace Okonkwo',    'grace@bloodlink.test',   '08023456709', 'BL-KD-009', 'O+',  NULL,
    NULL, NULL, NULL, '$2y$12$YQHGMXIcbFpUJaeKpeW6p.V8lIlU.4zdS8ZjPx0j0JyCH6dw9FY3u');

-- ------------------------------------------------------------- recipients --
INSERT INTO `recipients`
  (`recipient_id`,`name`,`email`,`phone`,`address`,`blood_group`,`latitude`,`longitude`,`password`) VALUES
(1, 'Hassan Umar',    'hassan@bloodlink.test',  '08051112201', 'Barau Dikko Teaching Hospital, Kaduna', 'O-',
    10.5105000, 7.4165000, '$2y$12$evuR8xaawLq2FXYrNI4tWulKnxxLbD/dKP6q.e84A/GXHM0mLEhgG'),
(2, 'Blessing Eze',   'blessing@bloodlink.test','08051112202', 'ABU Teaching Hospital, Zaria',          'AB+',
    11.1113000, 7.7227000, '$2y$12$evuR8xaawLq2FXYrNI4tWulKnxxLbD/dKP6q.e84A/GXHM0mLEhgG'),
(3, 'Musa Lawal',     'musa@bloodlink.test',    '08051112203', 'Aminu Kano Teaching Hospital, Kano',    'B+',
    11.9800000, 8.5300000, '$2y$12$evuR8xaawLq2FXYrNI4tWulKnxxLbD/dKP6q.e84A/GXHM0mLEhgG');

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
(1, 1, 'Hassan Umar',  'O-',  2, 'Barau Dikko Teaching Hospital',       'Kaduna',      10.5105000, 7.4165000, 'Critical Emergency', 'Pending',  'Hassan Umar',  '08051112201'),
(2, 2, 'Blessing Eze', 'AB+', 1, 'ABU Teaching Hospital',               'Zaria',       11.1113000, 7.7227000, 'Urgent',             'Pending',  'Blessing Eze', '08051112202'),
(3, 3, 'Musa Lawal',   'B+',  3, 'Aminu Kano Teaching Hospital',        'Kano',        11.9800000, 8.5300000, 'Normal',             'Approved', 'Musa Lawal',   '08051112203'),
(4, 1, 'Hassan Umar',  'A+',  1, 'Barau Dikko Teaching Hospital',       'Kaduna',      10.5105000, 7.4165000, 'Normal',             'Fulfilled','Hassan Umar',  '08051112201');

-- ----------------------------------------------------------- notices -------
INSERT INTO `notices` (`supervisor_id`,`title`,`message`) VALUES
(1,    'O- donors needed at Barau Dikko', 'We are critically low on O-. Any eligible O- donor please report to the blood bank.'),
(NULL, 'Emergency: O- blood needed at Barau Dikko Teaching Hospital',
       '2 unit(s) of O- needed urgently for patient Hassan Umar at Barau Dikko Teaching Hospital (Kaduna). Contact: 08051112201.');
