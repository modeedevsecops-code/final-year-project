<?php
// ============================================================================
//  BloodLink — migrate a LEGACY database (September 2026 schema: students,
//  staff, recipients, global blood_stock, plus the old SIWES tables) to the
//  current schema in db/schema.sql, keeping its data.
//
//    students   -> members            (same ids; reg_no becomes member_code)
//    recipients -> members            (merged by email, otherwise appended)
//    staff      -> hospital_workers   (same ids; bank taken from the position text)
//    blood_stock / stock_alerts       -> assigned to Barau Dikko (bank 1); the other
//                                        four seed banks get the seed stock
//    blood_requests, donations, notices, login -> copied with ids remapped
//    plain-text passwords             -> bcrypt hashes
//    attendance, chat_messages, logbook_entries, projects, seminar,
//    weekly_summaries, students, staff, recipients -> dropped
//
//  Command line only. Back up the database first (Local: Database tab ->
//  Open Adminer -> Export). Run from the project folder:
//      php db/migrate_legacy.php
//  It refuses to run on a database that is already current.
// ============================================================================

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
require_once 'config/functions.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$c = $db->connection;

function q($sql) { global $c; return mysqli_query($c, $sql); }
function rows($sql) { $r = q($sql); return $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : []; }
function has_table($t) { return (bool)rows("SHOW TABLES LIKE '" . addslashes($t) . "'"); }
function say($m) { fwrite(STDOUT, $m . PHP_EOL); }

$resuming = has_table('_legacy_students');
if (!$resuming && (!has_table('students') || has_table('members'))) {
    say(has_table('members') ? 'Database is already on the current schema — nothing to do.'
                             : 'No legacy tables found (students) — nothing to migrate.');
    exit(0);
}

// 1. Set the legacy tables aside under _legacy_ names (a re-run after an
//    interrupted attempt picks up the tables already set aside).
q('SET FOREIGN_KEY_CHECKS = 0');
if (!$resuming) {
    foreach (array_column(rows("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE()"), 't') as $t) {
        q("RENAME TABLE `$t` TO `_legacy_$t`");
    }
}
$legacy = array_map(function ($t) { return substr($t, 8); },
    array_column(rows("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '\\_legacy\\_%'"), 't'));
// Constraint names are unique per database, so free the old ones.
foreach (rows("SELECT table_name AS t, constraint_name AS n FROM information_schema.table_constraints
               WHERE table_schema = DATABASE() AND constraint_type = 'FOREIGN KEY' AND table_name LIKE '\\_legacy\\_%'") as $fk) {
    q("ALTER TABLE `{$fk['t']}` DROP FOREIGN KEY `{$fk['n']}`");
}
say(($resuming ? 'Resuming: ' : 'Set aside ') . count($legacy) . ' legacy tables.');

// 2. Create the current schema exactly as db/schema.sql defines it.
mysqli_multi_query($c, file_get_contents('db/schema.sql'));
do { if ($r = mysqli_store_result($c)) mysqli_free_result($r); } while (mysqli_more_results($c) && mysqli_next_result($c));
q('SET FOREIGN_KEY_CHECKS = 0');
say('Created the current tables from db/schema.sql.');

// 3. Blood banks + stock: the seed's five registered banks and their stock.
$seed = file_get_contents('db/seed.sql');
foreach (['blood_banks', 'blood_stock'] as $t) {
    preg_match('/INSERT INTO `' . $t . '`.*?;\s*$/ms', $seed, $m);
    q($m[0]);
}
// The legacy (single, global) stock was Barau Dikko's: it overwrites bank 1.
foreach (rows('SELECT * FROM _legacy_blood_stock') as $s) {
    $st = mysqli_prepare($c, 'UPDATE blood_stock SET units_available = ?, low_stock_threshold = ?, last_updated = ? WHERE blood_bank_id = 1 AND blood_type = ?');
    mysqli_stmt_bind_param($st, 'iiss', $s['units_available'], $s['low_stock_threshold'], $s['last_updated'], $s['blood_type']);
    mysqli_stmt_execute($st);
}
foreach (rows('SELECT * FROM _legacy_stock_alerts') as $a) {
    $st = mysqli_prepare($c, 'INSERT INTO stock_alerts (blood_bank_id, blood_type, units_at_alert, threshold, status, created_at, resolved_at) VALUES (1, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($st, 'siisss', $a['blood_type'], $a['units_at_alert'], $a['threshold'], $a['status'], $a['created_at'], $a['resolved_at']);
    mysqli_stmt_execute($st);
}
say('Blood banks: 5 · stock rows: ' . rows('SELECT COUNT(*) n FROM blood_stock')[0]['n'] . ' (legacy stock assigned to Barau Dikko).');

// 4. Admin accounts.
q('INSERT INTO login (user_id, USERNAME, PASSWORD, PW_STATUS) SELECT user_id, USERNAME, PASSWORD, PW_STATUS FROM _legacy_login');

// 5. Officers: staff -> hospital_workers, bank matched from the position text.
$banks = rows('SELECT bank_id, name FROM blood_banks');
foreach (rows('SELECT * FROM _legacy_staff') as $s) {
    $bank = null;
    foreach ($banks as $b) {
        $short = preg_replace('/\s+(Teaching|Memorial|Reference)?\s*Hospital.*$/i', '', $b['name']);
        if (stripos($s['position'], $short) !== false) { $bank = (int)$b['bank_id']; break; }
    }
    $position = trim(preg_split('/\s+[\x{2013}\x{2014}-]\s+|\s+\x{FFFD}\s+/u', $s['position'])[0]) ?: 'Blood Bank Officer';
    $st = mysqli_prepare($c, 'INSERT INTO hospital_workers (worker_id, name, phone, email, position, blood_bank_id, password, date_registered) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $email = strtolower($s['email']);
    mysqli_stmt_bind_param($st, 'issssiss', $s['staff_id'], $s['staff_name'], $s['phone'], $email, $position, $bank, $s['password'], $s['date_registered']);
    mysqli_stmt_execute($st);
    say("Officer {$s['staff_name']} -> bank " . ($bank ?? 'NONE — assign one in Hospital Officers'));
}

// 6. Members: students keep their ids; recipients merge by email or are appended.
$used_codes = [];
$code_for = function ($wanted) use (&$used_codes, $c) {
    $wanted = strtoupper(trim((string)$wanted));
    if ($wanted !== '' && preg_match('/^[A-Z0-9-]{3,30}$/', $wanted) && !isset($used_codes[$wanted])) { $used_codes[$wanted] = 1; return $wanted; }
    do { $code = 'BL-' . strtoupper(bin2hex(random_bytes(3))); } while (isset($used_codes[$code]));
    $used_codes[$code] = 1; return $code;
};
$ins = 'INSERT INTO members (member_id, name, email, phone, member_code, blood_group, last_donation_date, address, latitude, longitude, password, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
foreach (rows('SELECT * FROM _legacy_students') as $s) {
    $st = mysqli_prepare($c, $ins);
    $email = strtolower($s['email']); $code = $code_for($s['reg_no']);
    mysqli_stmt_bind_param($st, 'isssssssddsss', $s['student_id'], $s['name'], $email, $s['phone'], $code, $s['blood_group'],
        $s['last_donation_date'], $s['address'], $s['latitude'], $s['longitude'], $s['password'], $s['created_at'], $s['updated_at']);
    mysqli_stmt_execute($st);
}
$recipient_map = [];
foreach (rows('SELECT * FROM _legacy_recipients') as $r) {
    $email = strtolower($r['email']);
    $hit = rows("SELECT member_id FROM members WHERE email = '" . mysqli_real_escape_string($c, $email) . "'");
    if ($hit) { $recipient_map[$r['recipient_id']] = (int)$hit[0]['member_id']; continue; }
    $st = mysqli_prepare($c, 'INSERT INTO members (name, email, phone, member_code, blood_group, address, latitude, longitude, password, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $code = $code_for('');
    mysqli_stmt_bind_param($st, 'ssssssddss', $r['name'], $email, $r['phone'], $code, $r['blood_group'], $r['address'], $r['latitude'], $r['longitude'], $r['password'], $r['created_at']);
    mysqli_stmt_execute($st);
    $recipient_map[$r['recipient_id']] = mysqli_insert_id($c);
}
say('Members: ' . rows('SELECT COUNT(*) n FROM members')[0]['n'] . ' (' . count($recipient_map) . ' former recipients mapped).');

// 7. Blood requests (recipient ids remapped; registered banks give exact coordinates; live priority).
$ops = new operations();
$sev = ['Critical Emergency' => 'critical', 'Urgent' => 'severe', 'Normal' => 'moderate'];
foreach (rows('SELECT * FROM _legacy_blood_requests') as $r) {
    $rid = $r['recipient_id'] !== null ? ($recipient_map[$r['recipient_id']] ?? null) : null;
    $bank = rows("SELECT latitude, longitude FROM blood_banks WHERE LOWER(name) = LOWER('" . mysqli_real_escape_string($c, $r['hospital_name']) . "')");
    $lat = $bank ? $bank[0]['latitude'] : $r['latitude']; $lng = $bank ? $bank[0]['longitude'] : $r['longitude'];
    $severity = $sev[$r['urgency_level']] ?? 'moderate';
    $priority = $ops->compute_priority($severity, $r['blood_group'], null);
    $st = mysqli_prepare($c, 'INSERT INTO blood_requests (request_id, recipient_id, patient_name, blood_group, units_needed, hospital_name, location, latitude, longitude,
                              urgency_level, severity, priority_score, status, requested_by, contact_phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($st, 'iississddssissss', $r['request_id'], $rid, $r['patient_name'], $r['blood_group'], $r['units_needed'], $r['hospital_name'],
        $r['location'], $lat, $lng, $r['urgency_level'], $severity, $priority, $r['status'], $r['requested_by'], $r['contact_phone'], $r['created_at']);
    mysqli_stmt_execute($st);
}

// 8. Donations (donor = same id; recipient remapped; bank = the confirming officer's).
foreach (rows('SELECT * FROM _legacy_donations') as $d) {
    $rid = $d['recipient_id'] !== null ? ($recipient_map[$d['recipient_id']] ?? null) : null;
    $b = rows('SELECT blood_bank_id FROM hospital_workers WHERE worker_id = ' . (int)$d['officer_id']);
    $bank = $b ? $b[0]['blood_bank_id'] : null;
    $st = mysqli_prepare($c, 'INSERT INTO donations (donation_id, donor_id, recipient_id, request_id, officer_id, blood_bank_id, blood_group, units, donation_date, status, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($st, 'iiiiiisisss', $d['donation_id'], $d['donor_id'], $rid, $d['request_id'], $d['officer_id'], $bank, $d['blood_group'], $d['units'],
        $d['donation_date'], $d['status'], $d['created_at']);
    mysqli_stmt_execute($st);
}

// 9. Emergency alerts (supervisor_id -> officer_id), stored as plain text.
foreach (rows('SELECT * FROM _legacy_notices') as $n) {
    $st = mysqli_prepare($c, 'INSERT INTO notices (notice_id, officer_id, title, message, created_at) VALUES (?, ?, ?, ?, ?)');
    $title = html_entity_decode($n['title'], ENT_QUOTES); $msg = html_entity_decode($n['message'], ENT_QUOTES);
    mysqli_stmt_bind_param($st, 'iisss', $n['notice_id'], $n['supervisor_id'], $title, $msg, $n['created_at']);
    mysqli_stmt_execute($st);
}

// 10. Hash any plain-text passwords.
$hashed = 0;
foreach ([['members', 'member_id', 'password'], ['hospital_workers', 'worker_id', 'password'], ['login', 'user_id', 'PASSWORD']] as [$t, $pk, $col]) {
    foreach (rows("SELECT `$pk` AS id, `$col` AS pw FROM `$t`") as $u) {
        if (password_get_info($u['pw'])['algo']) continue;
        $h = password_hash($u['pw'], PASSWORD_DEFAULT);
        $st = mysqli_prepare($c, "UPDATE `$t` SET `$col` = ? WHERE `$pk` = ?");
        mysqli_stmt_bind_param($st, 'si', $h, $u['id']);
        mysqli_stmt_execute($st); $hashed++;
    }
}
say("Hashed $hashed plain-text password(s).");

// 11. Drop the legacy tables.
foreach ($legacy as $t) { q("DROP TABLE IF EXISTS `_legacy_$t`"); }
q('SET FOREIGN_KEY_CHECKS = 1');

$tables = array_column(rows("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name"), 't');
say('Done. Tables now: ' . implode(', ', $tables) . ' (' . count($tables) . ').');
