<?php
// Errors are always logged, but only shown on screen when APP_DEBUG is on.
// It defaults to OFF so stack traces, SQL and file paths never leak to users
// (BL-24). Turn it on for local dev by putting  define('APP_DEBUG', true);
// in config/config.local.php.
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');

// Since PHP 8.1 mysqli throws on any failed query (BL-26). This codebase mostly
// doesn't check return values, so a stray failure would otherwise be a blank
// 500. Log every uncaught error and show a friendly page in production; show the
// detail only when APP_DEBUG is on.
set_exception_handler(function ($e) {
    error_log('[BloodLink] Uncaught ' . get_class($e) . ': ' . $e->getMessage()
              . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) { http_response_code(500); }
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre style="white-space:pre-wrap">' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        echo '<!doctype html><meta charset="utf-8">'
           . '<div style="font-family:system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#333">'
           . '<h2 style="color:#7a0000">Something went wrong</h2>'
           . '<p>An unexpected error occurred. Please try again, or contact the administrator if it persists.</p>'
           . '</div>';
    }
    exit;
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('config/db.php');
$db = new dbconfig();

class operations extends dbconfig{

    //Saving Records
     //Inserting Record into the Database

// In your operations class
// Inserting Record into the Database

// Admin adds a member (a unified donor+recipient account).
public function add_donor() {
    global $db;

    if (isset($_POST['btn_add_donor']) || isset($_POST['btn_add_member'])) {
        list($f, $errors) = bl_validate_member_input($_POST, $db->connection);
        if ($errors) {
            $this->set_message(bl_errors_html($errors));
            return;
        }
        list(, $code, $geocoded) = bl_create_member($db->connection, $f);
        $note = ($f['address'] !== '' && !$geocoded) ? ' The address could not be located on the map — edit it to add coordinates.' : '';
        $this->set_message('<div class="alert alert-success text-center">Member added (ID ' . htmlspecialchars($code) . ').' . htmlspecialchars($note) . '</div>');
        header('Location: manage_members.php');
        exit;
    }
}

// Member updates their own availability (toggle + schedule + contact preference).
public function update_donor_availability($member_id) {
    global $db;
    if (!isset($_POST['btn_update_availability'])) return;
    bl_csrf_check();
    $member_id = intval($member_id);
    $available = isset($_POST['is_available']) ? 1 : 0;
    $schedule  = in_array($_POST['availability_schedule'] ?? '', BL_SCHEDULES, true) ? $_POST['availability_schedule'] : 'anytime';
    $contact   = in_array($_POST['contact_preference'] ?? '', BL_CONTACT_PREFS, true) ? $_POST['contact_preference'] : 'both';
    $q = "UPDATE members SET is_available=$available, availability_schedule='$schedule', contact_preference='$contact' WHERE member_id=$member_id";
    if (mysqli_query($db->connection, $q)) {
        $this->set_message('<div class="alert alert-success text-center">Availability updated.</div>');
    } else {
        $this->set_message('<div class="alert alert-danger">Could not update availability.</div>');
    }
    header('Location: member_dashboard.php');
    exit;
}

// Directory search for the Find Donors page. $mode: 'compatible' (default) or
// 'exact'. Optionally available-only, and ranked by distance from an origin.
public function search_donors($blood_group, $mode = 'compatible', $available_only = true, $olat = null, $olng = null) {
    global $db;
    $blood_group = strtoupper(trim($blood_group));
    if ($mode === 'exact') {
        $types = [$blood_group];
    } else {
        $types = $this->get_compatible_donor_types($blood_group);
    }
    if (empty($types)) return [];
    $in = implode(',', array_map(function($t) use ($db){ return "'".mysqli_real_escape_string($db->connection,$t)."'"; }, $types));

    $has_geo = is_numeric($olat) && is_numeric($olng);
    $dist = 'NULL AS distance_km';
    if ($has_geo) {
        $olat=(float)$olat; $olng=(float)$olng;
        $dist = "CASE WHEN latitude IS NULL OR longitude IS NULL THEN NULL ELSE ROUND(
                 6371*ACOS(LEAST(1.0, COS(RADIANS($olat))*COS(RADIANS(latitude))*COS(RADIANS(longitude)-RADIANS($olng))
                 + SIN(RADIANS($olat))*SIN(RADIANS(latitude)))),1) END AS distance_km";
    }
    $avail = $available_only ? "AND is_available = 1" : "";
    $q = "SELECT member_id AS donor_id, name, email, phone, blood_group, last_donation_date, is_available,
                 availability_schedule, contact_preference, latitude, longitude, $dist
          FROM members WHERE blood_group IN ($in) $avail";
    $res = mysqli_query($db->connection, $q);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    foreach ($rows as &$r) {
        $r['eligible'] = $this->is_donor_eligible($r['last_donation_date']) ? 1 : 0;
        $r['compatibility_score'] = $this->compatibility_score($r['blood_group'], $blood_group);
    }
    unset($r);
    usort($rows, function($a,$b) use ($has_geo){
        if ($a['eligible'] != $b['eligible']) return $b['eligible'] <=> $a['eligible'];
        if ($a['compatibility_score'] != $b['compatibility_score']) return $b['compatibility_score'] <=> $a['compatibility_score'];
        if ($has_geo){ $ad=is_null($a['distance_km'])?INF:(float)$a['distance_km']; $bd=is_null($b['distance_km'])?INF:(float)$b['distance_km']; if($ad!=$bd) return $ad<=>$bd; }
        return strcmp($a['name'],$b['name']);
    });
    return $rows;
}

// (Recipient CRUD removed — recipients are now members; admin manages them
//  through the unified Manage Members page: add_donor / get_donor_by_id /
//  update_donor / delete_donor / get_donors, all backed by the members table.)

// Verify a plaintext password against a stored value, transparently upgrading
// legacy plaintext rows to a bcrypt hash on the first successful login.
// Returns true if the password matches (BL-12 / BL-13).
private function verify_password($plain, $stored, $table, $pk_col, $pk_val) {
    // Modern path: stored value is a password_hash() result.
    $info = password_get_info($stored);
    if ($info['algo']) {
        return password_verify($plain, $stored);
    }
    // Legacy path: stored value is plaintext from before hashing existed.
    // Constant-time compare, then migrate the row to a hash.
    if (hash_equals((string)$stored, (string)$plain)) {
        $new_hash = password_hash($plain, PASSWORD_DEFAULT);
        $pk_val = intval($pk_val);
        if ($stmt = mysqli_prepare($this->connection, "UPDATE `$table` SET `password` = ? WHERE `$pk_col` = ?")) {
            mysqli_stmt_bind_param($stmt, "si", $new_hash, $pk_val);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        return true;
    }
    return false;
}

public function user_login() {

  if (!isset($_POST['btn_user_login'])) return;

  $role = $_POST['role'] ?? '';

  if ($role === 'member') {
    // Member login by email or member ID + password (prepared — BL-13). Emails
    // always contain "@" and member IDs can't, so the two never collide.
    $login    = trim($_POST['member_login'] ?? '');
    $password = $_POST['member_password'] ?? '';
    $col      = strpos($login, '@') !== false ? 'email' : 'member_code';
    if ($col === 'email') $login = strtolower($login);
    if ($col === 'member_code') $login = strtoupper($login);

    $user = null;
    if ($stmt = mysqli_prepare($this->connection, "SELECT * FROM members WHERE $col = ? LIMIT 1")) {
      mysqli_stmt_bind_param($stmt, "s", $login);
      mysqli_stmt_execute($stmt);
      $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
    }

    if ($user && $this->verify_password($password, $user['password'], 'members', 'member_id', $user['member_id'])) {
      session_regenerate_id(true);
      $_SESSION['Active'] = 'Active';
      $_SESSION['role'] = 'member';
      $_SESSION['user_id'] = $user['member_id'];
      $_SESSION['member_id'] = $user['member_id'];
      $_SESSION['name'] = $user['name'];
      $_SESSION['member_code'] = $user['member_code'];
      $_SESSION['email'] = $user['email'];
      header('Location: member_dashboard.php');
      exit;
    }
    $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid email/member ID or password!</div>');
  }

  elseif ($role === 'officer') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $user = null;
    if ($stmt = mysqli_prepare($this->connection, "SELECT * FROM hospital_workers WHERE email = ? LIMIT 1")) {
      mysqli_stmt_bind_param($stmt, "s", $email);
      mysqli_stmt_execute($stmt);
      $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
    }

    if ($user && $this->verify_password($password, $user['password'], 'hospital_workers', 'worker_id', $user['worker_id'])) {
      session_regenerate_id(true);
      $_SESSION['Active'] = 'Active';
      $_SESSION['role'] = 'officer';
      $_SESSION['user_id'] = $user['worker_id'];
      $_SESSION['officer_id'] = $user['worker_id'];
      $_SESSION['name'] = $user['name'];
      $_SESSION['email'] = $user['email'];
      header('Location: officer_dashboard.php');
      exit;
    }
    $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid Email or Password!</div>');
  }

  else {
    $this->set_message('<div class="alert alert-danger text-center" id="msg">Choose whether you are a Member or a Hospital Officer.</div>');
  }
}


    public function set_message($msg){
        if (!empty($msg)) {
            $_SESSION['Message'] = $msg;
        }
    }

    public function display_message(){
        if (isset($_SESSION['Message'])) {
            echo $_SESSION['Message'];
            unset($_SESSION['Message']);
        }
    }

// Admin deletes a member. A member with donations on record is kept, because
// deleting them would cascade-delete the donation ledger; mark them
// unavailable instead.
public function delete_donor() {
    global $db;

    if (isset($_POST['btn_delete_donor']) || isset($_POST['btn_delete_member'])) {
        bl_csrf_check();
        $member_id = intval($_POST['member_id'] ?? ($_POST['donor_id'] ?? 0));

        $res = mysqli_query($db->connection, "SELECT COUNT(*) AS n FROM donations WHERE donor_id = $member_id OR recipient_id = $member_id");
        $n = $res ? (int)mysqli_fetch_assoc($res)['n'] : 0;
        if ($n > 0) {
            $this->set_message('<div class="alert alert-warning">This member has ' . $n . ' donation record(s), so they can\'t be deleted without erasing the donation history. Edit them and untick "Available" instead.</div>');
            header('Location: manage_members.php');
            exit;
        }
        mysqli_query($db->connection, "DELETE FROM notifications WHERE recipient_role = 'member' AND recipient_id = $member_id");
        if (mysqli_query($db->connection, "DELETE FROM members WHERE member_id = $member_id")) {
            $this->set_message('<div class="alert alert-success">Member deleted successfully.</div>');
        } else {
            $this->set_message('<div class="alert alert-danger">Failed to delete member.</div>');
        }
        header('Location: manage_members.php');
        exit;
    }
}




    // Admin Login
    public function admin_login(){
        if (!isset($_POST['btn_admin_login'])) return;

        // No escaping here: app_login uses a prepared statement, and escaping
        // the password would break any password containing ' " or \.
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($this->app_login($username, $password)) {
            $_SESSION['username'] = $username;
            $_SESSION['name'] = 'Admin';
            header('Location: dashboard.php');
            exit;
        }
        $this->set_message('<div class="alert alert-danger text-center" id="msg">Invalid Login Credentials!</div>');
    }

    // Fetch member details by ID
public function get_donor_by_id($member_id) {
    global $db;
    $member_id = intval($member_id);
    $query = "SELECT * FROM members WHERE member_id = '$member_id'";
    $result = mysqli_query($db->connection, $query);
    return mysqli_fetch_assoc($result);
}

// Update a member (admin edit). Re-geocodes only when the address changes;
// a blank password field keeps the current password.
public function update_donor($member_id) {
    global $db;

    if (!isset($_POST['btn_update_donor']) && !isset($_POST['btn_update_member'])) return;
    $conn = $db->connection;
    $member_id = intval($member_id);
    $current = $this->get_donor_by_id($member_id);
    if (!$current) return;

    list($f, $errors) = bl_validate_member_input($_POST, $conn, $member_id, false);
    if ($f['member_code'] === '') $errors[] = 'Member ID is required.';
    if ($errors) {
        $this->set_message(bl_errors_html($errors));
        return;
    }

    $lat = $current['latitude']; $lng = $current['longitude']; $geo_note = '';
    if ($f['address'] !== (string)$current['address']) {
        list($lat, $lng) = bl_geocode($f['address']);
        if ($f['address'] !== '' && $lat === null) $geo_note = ' The new address could not be located on the map.';
    }
    $address   = $f['address'] !== '' ? $f['address'] : null;
    $available = isset($_POST['is_available']) ? 1 : 0;

    $stmt = mysqli_prepare($conn, "UPDATE members SET name=?, email=?, phone=?, member_code=?, blood_group=?, address=?, latitude=?, longitude=?, is_available=? WHERE member_id=?");
    mysqli_stmt_bind_param($stmt, "ssssssddii", $f['name'], $f['email'], $f['phone'], $f['member_code'], $f['blood_group'], $address, $lat, $lng, $available, $member_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($f['password'] !== '') {
        $hash = password_hash($f['password'], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE members SET password=? WHERE member_id=?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $member_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $this->set_message('<div class="alert alert-success text-center">Member updated.' . htmlspecialchars($geo_note) . '</div>');
    header('Location: manage_members.php');
    exit;
}


    public function get_donors() {
        global $db;
        $query = "SELECT * FROM members ORDER BY member_id DESC";
        $result = mysqli_query($db->connection, $query);
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }


// ===================== EMERGENCY NOTICES =====================

    public function get_notice_by_id($notice_id) {
        global $db;
        $stmt = mysqli_prepare($db->connection, "SELECT * FROM notices WHERE notice_id = ? LIMIT 1");
        $notice_id = intval($notice_id);
        mysqli_stmt_bind_param($stmt, "i", $notice_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    public function delete_notice($notice_id) {
        global $db;
        $stmt = mysqli_prepare($db->connection, "DELETE FROM notices WHERE notice_id = ?");
        $notice_id = intval($notice_id);
        mysqli_stmt_bind_param($stmt, "i", $notice_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

        //Admin Login
     protected function app_login($a, $b){

        // Prepared statement + exact match. The old query used LIKE on both
        // USERNAME and PASSWORD, so a password of "%" matched every account
        // and any wildcard leaked in (BL-12 / BL-13). Look the user up by
        // username only, then verify the password (with legacy-plaintext
        // migration) in PHP.
        $data = null;
        if ($stmt = mysqli_prepare($this->connection,
                "SELECT * FROM login WHERE USERNAME = ? AND PW_STATUS = 'Active' LIMIT 1")) {
            mysqli_stmt_bind_param($stmt, "s", $a);
            mysqli_stmt_execute($stmt);
            $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }

        if ($data && $this->verify_password($b, $data['PASSWORD'], 'login', 'user_id', $data['user_id'])) {
               session_regenerate_id(true);
               $_SESSION['user'] = $data["user_id"];
               $_SESSION['user_id'] = $data["user_id"];
               $_SESSION['Active'] = 'Active';
               $_SESSION['role'] = 'admin';
               return true;
        }
        return false;

    }

    // ===================== BLOOD DONATION MATCHING (56-DAY RULE) =====================

    const DONATION_ELIGIBILITY_DAYS = 56;

    // Recipient blood group => compatible donor blood groups
    public function get_compatible_donor_types($recipient_blood_group) {
        $map = [
            'A+'  => ['A+', 'A-', 'O+', 'O-'],
            'A-'  => ['A-', 'O-'],
            'B+'  => ['B+', 'B-', 'O+', 'O-'],
            'B-'  => ['B-', 'O-'],
            'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'AB-' => ['A-', 'B-', 'AB-', 'O-'],
            'O+'  => ['O+', 'O-'],
            'O-'  => ['O-'],
        ];
        $recipient_blood_group = strtoupper(trim($recipient_blood_group));
        return isset($map[$recipient_blood_group]) ? $map[$recipient_blood_group] : [];
    }

    // Compatibility score: exact match best, universal O- next, same ABO next.
    // 0 = incompatible. Mirrors the ranking a clinician would prefer.
    public function compatibility_score($donor_bg, $recipient_bg) {
        $donor_bg = strtoupper(trim($donor_bg)); $recipient_bg = strtoupper(trim($recipient_bg));
        if (!in_array($donor_bg, $this->get_compatible_donor_types($recipient_bg), true)) return 0;
        if ($donor_bg === $recipient_bg) return 100;
        if ($donor_bg === 'O-') return 90;
        if (rtrim($donor_bg, '+-') === rtrim($recipient_bg, '+-')) return 80;
        return 70;
    }

    // Blood-type rarity weight (1 common .. 10 rarest) — used to prioritise.
    public function blood_type_rarity($bg) {
        $r = ['AB-'=>10,'B-'=>9,'AB+'=>8,'A-'=>7,'O-'=>6,'B+'=>5,'A+'=>4,'O+'=>3];
        return $r[strtoupper(trim($bg))] ?? 5;
    }

    // Emergency priority (0..100) from severity, blood-type rarity and time left.
    public function compute_priority($severity, $blood_group, $hours_left = null) {
        $sev = ['critical'=>60,'severe'=>40,'moderate'=>20,'low'=>5][strtolower((string)$severity)] ?? 20;
        $rarity = $this->blood_type_rarity($blood_group) * 2;              // 6..20
        $time = 0;
        if (is_numeric($hours_left)) {
            $time = $hours_left <= 3 ? 20 : ($hours_left <= 12 ? 12 : ($hours_left <= 48 ? 6 : 2));
        }
        return min(100, $sev + $rarity + $time);
    }

    // Days since the last donation (negative if the stored date is in the future).
    private function days_since_donation($last_donation_date) {
        $last = new DateTime($last_donation_date);
        $today = new DateTime('today');
        return (int)$last->diff($today)->format('%r%a');
    }

    // Is a donor eligible right now? (56 days since last donation, or never donated)
    public function is_donor_eligible($last_donation_date) {
        if (empty($last_donation_date)) return true;
        return $this->days_since_donation($last_donation_date) >= self::DONATION_ELIGIBILITY_DAYS;
    }

    // Days remaining before a donor becomes eligible again (0 if already eligible)
    public function days_until_eligible($last_donation_date) {
        if (empty($last_donation_date)) return 0;
        return max(0, self::DONATION_ELIGIBILITY_DAYS - $this->days_since_donation($last_donation_date));
    }

    // Find eligible, compatible donors for a given recipient blood group
    // Eligible, compatible donors for a recipient blood group.
    //
    // The whole reason this system has geo-location: blood is time-critical and
    // the donor has to physically reach the hospital, so when the request's
    // coordinates are known we compute each donor's distance (Haversine, in km)
    // and order NEAREST FIRST — that is the clinically useful ordering for an
    // officer working a critical request. Donors without coordinates are still
    // returned (they're valid donors), just sorted after the located ones.
    //
    // With no coordinates passed, behaviour is unchanged: order by longest time
    // since last donation.
    public function find_matching_donors($recipient_blood_group, $lat = null, $lng = null, $radius_km = null, $exclude_member_id = 0) {
        global $db;
        $exclude_sql = $exclude_member_id ? " AND member_id <> " . intval($exclude_member_id) : "";

        $compatible_types = $this->get_compatible_donor_types($recipient_blood_group);
        if (empty($compatible_types)) return [];

        $safe_types = array_map(function($t) use ($db) {
            return "'" . mysqli_real_escape_string($db->connection, $t) . "'";
        }, $compatible_types);
        $in_clause = implode(',', $safe_types);

        $has_geo = is_numeric($lat) && is_numeric($lng);
        if ($has_geo) {
            $lat = (float)$lat; $lng = (float)$lng;
            // 6371 = Earth radius in km.
            $distance_expr = "(6371 * ACOS(
                LEAST(1.0, COS(RADIANS($lat)) * COS(RADIANS(latitude)) *
                COS(RADIANS(longitude) - RADIANS($lng)) +
                SIN(RADIANS($lat)) * SIN(RADIANS(latitude)))))";
            $radius_clause = ($radius_km !== null && is_numeric($radius_km))
                ? " AND $distance_expr <= " . (float)$radius_km : "";
            $query = "
                SELECT member_id AS donor_id, name, member_code, email, phone, blood_group, last_donation_date, is_available,
                       latitude, longitude,
                       CASE WHEN last_donation_date IS NULL THEN 9999
                            ELSE DATEDIFF(CURDATE(), last_donation_date) END AS days_since_last_donation,
                       CASE WHEN latitude IS NULL OR longitude IS NULL THEN NULL
                            ELSE ROUND($distance_expr, 1) END AS distance_km
                FROM members
                WHERE blood_group IN ($in_clause)
                  AND (last_donation_date IS NULL
                       OR DATEDIFF(CURDATE(), last_donation_date) >= " . self::DONATION_ELIGIBILITY_DAYS . ")
                  $exclude_sql
                  $radius_clause
            ";
        } else {
            $query = "
                SELECT member_id AS donor_id, name, member_code, email, phone, blood_group, last_donation_date, is_available,
                       latitude, longitude,
                       CASE WHEN last_donation_date IS NULL THEN 9999
                            ELSE DATEDIFF(CURDATE(), last_donation_date) END AS days_since_last_donation,
                       NULL AS distance_km
                FROM members
                WHERE blood_group IN ($in_clause) $exclude_sql
                HAVING last_donation_date IS NULL
                       OR days_since_last_donation >= " . self::DONATION_ELIGIBILITY_DAYS . "
            ";
        }
        $result = mysqli_query($db->connection, $query);
        $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

        // Annotate each donor with a compatibility score + rarity, then rank:
        // available first, then best compatibility, then nearest, then longest since donating.
        foreach ($rows as &$r) {
            $r['compatibility_score'] = $this->compatibility_score($r['blood_group'], $recipient_blood_group);
            $r['rarity'] = $this->blood_type_rarity($r['blood_group']);
        }
        unset($r);
        usort($rows, function($a, $b) use ($has_geo) {
            if ($a['is_available'] != $b['is_available']) return $b['is_available'] <=> $a['is_available'];
            if ($a['compatibility_score'] != $b['compatibility_score']) return $b['compatibility_score'] <=> $a['compatibility_score'];
            if ($has_geo) {
                $ad = is_null($a['distance_km']) ? INF : (float)$a['distance_km'];
                $bd = is_null($b['distance_km']) ? INF : (float)$b['distance_km'];
                if ($ad != $bd) return $ad <=> $bd;
            }
            return $b['days_since_last_donation'] <=> $a['days_since_last_donation'];
        });
        return $rows;
    }

    // Units already donated against a request.
    public function units_donated_for_request($request_id) {
        global $db;
        $request_id = intval($request_id);
        $res = mysqli_query($db->connection, "SELECT COALESCE(SUM(units),0) AS n FROM donations WHERE request_id = $request_id AND status = 'Completed'");
        return $res ? (int)mysqli_fetch_assoc($res)['n'] : 0;
    }

    // Records a donation confirmed by an officer, in one transaction.
    //
    // With a $request_id (a match from match_donor.php) the blood goes straight
    // to that patient, so the bank's stock is not changed; the request becomes
    // Fulfilled once the units donated against it reach units_needed.
    // Without one (a walk-in donation) the units are added to the officer's
    // bank stock and that bank's low-stock alert is re-evaluated.
    //
    // Everything that matters is re-read from the database — the donor's blood
    // group, the request's group, recipient and status — never taken from the form.
    public function process_donation($donor_id, $request_id, $officer_id, $units) {
        global $db;
        $conn = $db->connection;

        $donor_id   = intval($donor_id);
        $request_id = $request_id ? intval($request_id) : null;
        $officer_id = intval($officer_id);
        $units      = intval($units);
        $fail = function ($msg) use ($conn) { mysqli_rollback($conn); return ['success' => false, 'message' => $msg]; };

        if ($units < 1 || $units > BL_MAX_UNITS_PER_DONATION) {
            return ['success' => false, 'message' => 'A single donation is 1–' . BL_MAX_UNITS_PER_DONATION . ' unit(s).'];
        }

        $bres = mysqli_query($conn, "SELECT blood_bank_id FROM hospital_workers WHERE worker_id = $officer_id");
        $brow = $bres ? mysqli_fetch_assoc($bres) : null;
        $bank_id = $brow && $brow['blood_bank_id'] ? intval($brow['blood_bank_id']) : null;
        if (!$bank_id) {
            return ['success' => false, 'message' => 'Your officer account is not linked to a blood bank. Ask an administrator to assign one.'];
        }

        mysqli_begin_transaction($conn);
        try {
            // Lock the donor row so two simultaneous confirmations can't both pass the 56-day check.
            $res = mysqli_query($conn, "SELECT member_id, blood_group, last_donation_date, is_available FROM members WHERE member_id = $donor_id FOR UPDATE");
            $donor = $res ? mysqli_fetch_assoc($res) : null;
            if (!$donor) return $fail('Donor not found.');
            if (!in_array($donor['blood_group'], BL_BLOOD_GROUPS, true)) {
                return $fail('This member has no valid blood group on record. Update their profile first.');
            }
            if (!$this->is_donor_eligible($donor['last_donation_date'])) {
                $wait = $this->days_until_eligible($donor['last_donation_date']);
                return $fail("Donor is not yet eligible. $wait day(s) remaining of the 56-day window.");
            }

            $request = null; $recipient_id = null; $new_status = null;
            if ($request_id) {
                $res = mysqli_query($conn, "SELECT * FROM blood_requests WHERE request_id = $request_id FOR UPDATE");
                $request = $res ? mysqli_fetch_assoc($res) : null;
                if (!$request) return $fail('Blood request not found.');
                if (!in_array($request['status'], ['Pending', 'Approved'], true)) {
                    return $fail('This request is already ' . $request['status'] . '.');
                }
                if (!$donor['is_available']) return $fail('This member has marked themselves unavailable to donate.');
                $recipient_id = $request['recipient_id'] ? intval($request['recipient_id']) : null;
                if ($recipient_id === $donor_id) return $fail('A member cannot donate to their own request.');
                if ($this->compatibility_score($donor['blood_group'], $request['blood_group']) === 0) {
                    return $fail($donor['blood_group'] . ' blood is not compatible with a ' . $request['blood_group'] . ' recipient.');
                }
                $already   = $this->units_donated_for_request($request_id);
                $remaining = (int)$request['units_needed'] - $already;
                if ($units > $remaining) return $fail("Only $remaining more unit(s) are needed for this request.");
                $new_status = ($already + $units >= (int)$request['units_needed']) ? 'Fulfilled' : 'Approved';
            }

            $today = date('Y-m-d');
            $bg    = $donor['blood_group'];
            $stmt = mysqli_prepare($conn, "INSERT INTO donations (donor_id, recipient_id, request_id, officer_id, blood_bank_id, blood_group, units, donation_date, status)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Completed')");
            mysqli_stmt_bind_param($stmt, "iiiiisis", $donor_id, $recipient_id, $request_id, $officer_id, $bank_id, $bg, $units, $today);
            mysqli_stmt_execute($stmt);
            $donation_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            mysqli_query($conn, "UPDATE members SET last_donation_date = '$today' WHERE member_id = $donor_id");

            if ($request_id) {
                mysqli_query($conn, "UPDATE blood_requests SET status = '$new_status' WHERE request_id = $request_id");
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO blood_stock (blood_bank_id, blood_type, units_available) VALUES (?, ?, ?)
                                               ON DUPLICATE KEY UPDATE units_available = units_available + VALUES(units_available)");
                mysqli_stmt_bind_param($stmt, "isi", $bank_id, $bg, $units);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                bl_sync_stock_alert($conn, $bank_id, $bg);
            }

            mysqli_commit($conn);

            if ($request_id) {
                bl_notify($conn, 'member', $donor_id, 'donation', 'Thank you — your donation was recorded',
                    "$units unit(s) of $bg for " . $request['patient_name'] . " at " . $request['hospital_name'] . ". You can donate again in 56 days.", 'my_donations.php');
                if ($recipient_id) {
                    bl_notify($conn, 'member', $recipient_id, 'request_status', 'Your blood request is ' . $new_status,
                        "$units unit(s) of $bg donated for " . $request['patient_name'] . ($new_status === 'Fulfilled' ? ' — the request is now fully covered.' : ' — more units are still needed.'), 'request_history.php');
                }
            } else {
                bl_notify($conn, 'member', $donor_id, 'donation', 'Thank you — your donation was recorded',
                    "$units unit(s) of $bg added to the blood bank's stock. You can donate again in 56 days.", 'my_donations.php');
            }

            $msg = $request_id
                ? "Donation recorded ($units unit(s) of $bg). Request is now $new_status."
                : "Walk-in donation recorded: $units unit(s) of $bg added to your bank's stock.";
            return ['success' => true, 'message' => $msg, 'donation_id' => $donation_id];

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            error_log('[BloodLink] process_donation: ' . $e->getMessage());
            return ['success' => false, 'message' => 'The donation could not be recorded. Please try again.'];
        }
    }

    // Donation history for a donor: "You donated X to Y"
    public function get_donor_history($donor_id) {
        global $db;
        $donor_id = intval($donor_id);
        $query = "
            SELECT d.donation_id, d.blood_group, d.units, d.donation_date, d.status, d.request_id,
                   r.member_id AS recipient_id, r.name AS recipient_name,
                   br.patient_name, br.hospital_name, bb.name AS bank_name
            FROM donations d
            LEFT JOIN members r ON r.member_id = d.recipient_id
            LEFT JOIN blood_requests br ON br.request_id = d.request_id
            LEFT JOIN blood_banks bb ON bb.bank_id = d.blood_bank_id
            WHERE d.donor_id = '$donor_id'
            ORDER BY d.donation_date DESC, d.donation_id DESC
        ";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // ===================== BLOOD BANKS (facilities) =====================

    // All banks, optionally with aggregate stock, for admin lists / dropdowns.
    public function get_blood_banks() {
        global $db;
        $res = mysqli_query($db->connection,
            "SELECT bb.*,
                    COALESCE(SUM(bs.units_available),0) AS total_units,
                    COUNT(DISTINCT st.worker_id) AS officer_count
             FROM blood_banks bb
             LEFT JOIN blood_stock bs ON bs.blood_bank_id = bb.bank_id
             LEFT JOIN hospital_workers st ON st.blood_bank_id = bb.bank_id
             GROUP BY bb.bank_id
             ORDER BY bb.name ASC");
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    public function get_bank_by_id($id) {
        global $db;
        $id = intval($id);
        $res = mysqli_query($db->connection, "SELECT * FROM blood_banks WHERE bank_id = $id LIMIT 1");
        return $res ? mysqli_fetch_assoc($res) : null;
    }

    // Add a bank (geocodes the address so it appears on the locator).
    public function add_blood_bank() {
        global $db;
        if (!isset($_POST['btn_add_bank'])) return;
        bl_csrf_check();
        $name    = $db->check(trim($_POST['name'] ?? ''));
        $address = $db->check(trim($_POST['address'] ?? ''));
        $phone   = $db->check(trim($_POST['contact_phone'] ?? ''));
        $addr_raw = trim($_POST['address'] ?? '');
        if ($name === '' || $addr_raw === '') {
            $this->set_message('<div class="alert alert-danger">Bank name and address are required.</div>');
            return;
        }
        if ($this->bank_name_taken(trim($_POST['name']))) {
            $this->set_message('<div class="alert alert-danger">A blood bank with that name already exists.</div>');
            return;
        }
        list($lat, $lng) = bl_geocode($addr_raw);
        $lat_sql = ($lat !== null) ? "'" . floatval($lat) . "'" : 'NULL';
        $lng_sql = ($lng !== null) ? "'" . floatval($lng) . "'" : 'NULL';
        $q = "INSERT INTO blood_banks (name, address, contact_phone, latitude, longitude)
              VALUES ('$name', '$address', '$phone', $lat_sql, $lng_sql)";
        if (mysqli_query($db->connection, $q)) {
            $new_id = mysqli_insert_id($db->connection);
            // Give the new bank an empty stock row per blood type so it's manageable immediately.
            foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg) {
                mysqli_query($db->connection, "INSERT IGNORE INTO blood_stock (blood_bank_id, blood_type, units_available) VALUES ($new_id, '$bg', 0)");
            }
            $this->set_message('<div class="alert alert-success text-center">Blood bank added.</div>');
            echo '<script>setTimeout(() => window.location.href = "manage_blood_banks.php", 1200);</script>';
        } else {
            $this->set_message('<div class="alert alert-danger">Failed to add bank.</div>');
        }
    }

    private function bank_name_taken($name, $exclude_id = 0) {
        global $db;
        $stmt = mysqli_prepare($db->connection, "SELECT bank_id FROM blood_banks WHERE name = ? AND bank_id <> ? LIMIT 1");
        $exclude_id = intval($exclude_id);
        mysqli_stmt_bind_param($stmt, "si", $name, $exclude_id);
        mysqli_stmt_execute($stmt);
        $taken = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $taken;
    }

    public function update_blood_bank($id) {
        global $db;
        if (!isset($_POST['btn_update_bank'])) return;
        bl_csrf_check();
        $id      = intval($id);
        $name    = $db->check(trim($_POST['name'] ?? ''));
        $address = $db->check(trim($_POST['address'] ?? ''));
        $phone   = $db->check(trim($_POST['contact_phone'] ?? ''));
        $addr_raw = trim($_POST['address'] ?? '');
        if ($name === '' || $addr_raw === '') {
            $this->set_message('<div class="alert alert-danger">Bank name and address are required.</div>');
            return;
        }
        if ($this->bank_name_taken(trim($_POST['name']), $id)) {
            $this->set_message('<div class="alert alert-danger">Another blood bank already has that name.</div>');
            return;
        }
        list($lat, $lng) = bl_geocode($addr_raw);
        $lat_sql = ($lat !== null) ? "'" . floatval($lat) . "'" : 'latitude';
        $lng_sql = ($lng !== null) ? "'" . floatval($lng) . "'" : 'longitude';
        $q = "UPDATE blood_banks SET name='$name', address='$address', contact_phone='$phone',
                 latitude=$lat_sql, longitude=$lng_sql WHERE bank_id=$id";
        if (mysqli_query($db->connection, $q)) {
            $this->set_message('<div class="alert alert-success text-center">Blood bank updated.</div>');
            echo '<script>setTimeout(() => window.location.href = "manage_blood_banks.php", 1200);</script>';
        } else {
            $this->set_message('<div class="alert alert-danger">Failed to update bank.</div>');
        }
    }

    // Donation history for a recipient: "You received X from Y"
    public function get_recipient_history($recipient_id) {
        global $db;
        $recipient_id = intval($recipient_id);
        $query = "
            SELECT d.donation_id, d.blood_group, d.units, d.donation_date, d.status,
                   s.member_id AS donor_id, s.name AS donor_name,
                   br.patient_name, br.hospital_name
            FROM donations d
            LEFT JOIN members s ON s.member_id = d.donor_id
            LEFT JOIN blood_requests br ON br.request_id = d.request_id
            WHERE d.recipient_id = '$recipient_id'
            ORDER BY d.donation_date DESC
        ";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // Open (Pending/Approved) blood requests, most urgent first, for the officer's queue
    public function get_open_blood_requests() {
        global $db;
        $query = "SELECT * FROM blood_requests WHERE status IN ('Pending','Approved') ORDER BY
                    FIELD(urgency_level,'Critical Emergency','Urgent','Normal'), created_at ASC";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    // Open requests for the Emergency Board. Priority is recomputed on every
    // load from the time actually left before each deadline, so a request
    // climbs the board as its deadline approaches; overdue requests rank as
    // most time-critical. The stored priority_score is refreshed to match.
    public function get_emergency_requests() {
        global $db;
        $res = mysqli_query($db->connection, "SELECT * FROM blood_requests WHERE status IN ('Pending','Approved')");
        $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
        $sevFromUrgency = ['Critical Emergency' => 'critical', 'Urgent' => 'severe', 'Normal' => 'moderate'];
        foreach ($rows as &$r) {
            $hours_left = null;
            if (!empty($r['required_within_hours'])) {
                $elapsed = (time() - strtotime($r['created_at'])) / 3600;
                $hours_left = max(0, (int)$r['required_within_hours'] - $elapsed);
            }
            $sev = $r['severity'] ?: ($sevFromUrgency[$r['urgency_level']] ?? 'moderate');
            $score = $this->compute_priority($sev, $r['blood_group'], $hours_left);
            if ($score !== (int)$r['priority_score']) {
                mysqli_query($db->connection, "UPDATE blood_requests SET priority_score = $score WHERE request_id = " . (int)$r['request_id']);
            }
            $r['priority_score'] = $score;
        }
        unset($r);
        $urg = ['Critical Emergency' => 0, 'Urgent' => 1, 'Normal' => 2];
        usort($rows, function ($a, $b) use ($urg) {
            if ($a['priority_score'] != $b['priority_score']) return $b['priority_score'] <=> $a['priority_score'];
            $ua = $urg[$a['urgency_level']] ?? 3; $ub = $urg[$b['urgency_level']] ?? 3;
            if ($ua != $ub) return $ua <=> $ub;
            return strcmp($a['created_at'], $b['created_at']);
        });
        return $rows;
    }

    // Get a single blood request by ID
    public function get_blood_request($request_id) {
        global $db;
        $request_id = intval($request_id);
        $query = "SELECT * FROM blood_requests WHERE request_id = '$request_id'";
        $result = mysqli_query($db->connection, $query);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    // ===================== END BLOOD DONATION MATCHING =====================

}


// ===================== ACCESS-CONTROL HELPERS (BL-25) =====================
// Call these at the very top of a page, before any HTML output, so the
// redirect header can be sent. They give every protected page a consistent
// guard instead of the ad-hoc (and sometimes missing) checks scattered around.

if (!function_exists('bl_require_login')) {
    function bl_require_login() {
        if (empty($_SESSION['role']) || empty($_SESSION['Active'])) {
            header('Location: user-login.php');
            exit;
        }
    }
}

if (!function_exists('bl_require_role')) {
    function bl_require_role($roles) {
        $roles = (array)$roles;
        if ((empty($_SESSION['role']) || empty($_SESSION['Active'])) && $roles === ['admin']) {
            header('Location: login.php');   // admin-only pages: the admin portal
            exit;
        }
        bl_require_login();
        if (!in_array($_SESSION['role'], $roles, true)) {
            // Logged in but wrong role — back to their own dashboard.
            header('Location: ' . bl_home_for_role($_SESSION['role']));
            exit;
        }
    }
}

// ===================== CSRF PROTECTION (BL-14) =====================
// bl_csrf_field() prints a hidden input for forms; bl_csrf_check() verifies it
// on POST. Token lives in the session for the whole login.

if (!function_exists('bl_csrf_token')) {
    function bl_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('bl_csrf_field')) {
    function bl_csrf_field() {
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(bl_csrf_token()) . '">';
    }
}

if (!function_exists('bl_csrf_check')) {
    function bl_csrf_check() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $sent = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
            http_response_code(419);
            die('Invalid or expired form token. Please go back and try again.');
        }
    }
}

// ===================== GEOCODING (Phase 3, BL-05 / BL-15) =====================
// Address -> [lat, lng] via OpenStreetMap Nominatim. Free, no API key. This
// replaces the hardcoded Google Geocoding call. Nominatim's usage policy caps
// callers at ~1 request/second and REQUIRES a descriptive User-Agent — so this
// is only ever called once, at write time (registration / request creation),
// never in a loop on page load. Returns [null, null] on any failure so callers
// can store NULL coordinates and carry on.
if (!function_exists('bl_geocode')) {
    function bl_geocode($address) {
        $address = trim((string)$address);
        if ($address === '') return [null, null];

        // Bias results to Nigeria; callers pass a plain address.
        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ng&q='
             . urlencode($address . ', Nigeria');

        $ctx = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: BloodLink/1.0 (blood bank management system)\r\nAccept: application/json\r\n",
            'timeout' => 6,
        ]]);

        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) return [null, null];

        $data = json_decode($resp, true);
        if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
            return [(float)$data[0]['lat'], (float)$data[0]['lon']];
        }
        return [null, null];
    }
}

// ===================== SHARED VALIDATION / HELPERS =====================

if (!defined('BL_BLOOD_GROUPS')) {
    define('BL_BLOOD_GROUPS', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
    define('BL_SCHEDULES', ['anytime', 'weekdays', 'weekends', 'mornings', 'afternoons', 'evenings', 'emergencies']);
    define('BL_CONTACT_PREFS', ['both', 'phone', 'sms', 'emergency-only']);
    define('BL_MAX_UNITS_PER_DONATION', 2);   // one whole-blood or double red-cell donation
}

// Validates member input for registration, admin add/edit and profile edits.
// Returns [$fields, $errors]; $fields hold trimmed, normalised RAW values (bind
// them with prepared statements, never interpolate). Pass $exclude_id when
// editing so the member's own email / member ID don't count as duplicates.
if (!function_exists('bl_validate_member_input')) {
    function bl_validate_member_input(array $in, $conn, $exclude_id = 0, $password_required = true) {
        $f = [
            'name'        => trim((string)($in['name'] ?? '')),
            'email'       => strtolower(trim((string)($in['email'] ?? ''))),
            'phone'       => preg_replace('/[\s-]+/', '', trim((string)($in['phone'] ?? ''))),
            'blood_group' => strtoupper(trim((string)($in['blood_group'] ?? ''))),
            'member_code' => strtoupper(trim((string)($in['member_code'] ?? ''))),
            'address'     => trim((string)($in['address'] ?? ($in['member_address'] ?? ''))),
            'password'    => (string)($in['password'] ?? ''),
        ];
        $e = [];
        if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100) $e[] = 'Full name is required (2–100 characters).';
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $e[] = 'Enter a valid email address.';
        if (!preg_match('/^\+?[0-9]{7,15}$/', $f['phone'])) $e[] = 'Enter a valid phone number (7–15 digits).';
        if (!in_array($f['blood_group'], BL_BLOOD_GROUPS, true)) $e[] = 'Select a valid blood group.';
        if ($f['member_code'] !== '' && !preg_match('/^[A-Z0-9-]{3,30}$/', $f['member_code'])) {
            $e[] = 'Member ID may only contain letters, numbers and dashes (3–30 characters).';
        }
        if ($password_required || $f['password'] !== '') {
            if (strlen($f['password']) < 8 || !preg_match('/[A-Za-z]/', $f['password']) || !preg_match('/[0-9]/', $f['password'])) {
                $e[] = 'Password must be at least 8 characters and include a letter and a number.';
            } elseif (array_key_exists('password_confirm', $in) && $in['password_confirm'] !== $f['password']) {
                $e[] = 'Passwords do not match.';
            }
        }
        $exclude_id = intval($exclude_id);
        if (!$e) {
            $stmt = mysqli_prepare($conn, "SELECT member_id FROM members WHERE email = ? AND member_id <> ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "si", $f['email'], $exclude_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) $e[] = 'That email is already registered.';
            mysqli_stmt_close($stmt);
        }
        if (!$e && $f['member_code'] !== '' && !bl_member_code_free($conn, $f['member_code'], $exclude_id)) {
            $e[] = 'That Member ID is already taken — choose another or leave it blank.';
        }
        return [$f, $e];
    }
}

if (!function_exists('bl_member_code_free')) {
    function bl_member_code_free($conn, $code, $exclude_id = 0) {
        $stmt = mysqli_prepare($conn, "SELECT member_id FROM members WHERE member_code = ? AND member_id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $code, $exclude_id);
        mysqli_stmt_execute($stmt);
        $taken = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return !$taken;
    }
}

if (!function_exists('bl_generate_member_code')) {
    function bl_generate_member_code($conn) {
        do {
            $code = 'BL-' . strtoupper(bin2hex(random_bytes(3)));
        } while (!bl_member_code_free($conn, $code));
        return $code;
    }
}

// Inserts a validated member. Geocodes the address (NULL coords on failure).
// Returns [$member_id, $member_code, $geocoded].
if (!function_exists('bl_create_member')) {
    function bl_create_member($conn, array $f) {
        $code = $f['member_code'] !== '' ? $f['member_code'] : bl_generate_member_code($conn);
        list($lat, $lng) = bl_geocode($f['address']);
        $hash = password_hash($f['password'], PASSWORD_DEFAULT);
        $address = $f['address'] !== '' ? $f['address'] : null;
        $stmt = mysqli_prepare($conn, "INSERT INTO members (name, email, phone, member_code, blood_group, address, latitude, longitude, password)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssdds", $f['name'], $f['email'], $f['phone'], $code, $f['blood_group'], $address, $lat, $lng, $hash);
        mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        return [$id, $code, $lat !== null];
    }
}

if (!function_exists('bl_errors_html')) {
    function bl_errors_html(array $errors) {
        return '<div class="alert alert-danger"><ul class="mb-0 ps-3"><li>'
             . implode('</li><li>', array_map('htmlspecialchars', $errors)) . '</li></ul></div>';
    }
}

// Raise or resolve a bank's low-stock alert for one blood type to match its current level.
if (!function_exists('bl_sync_stock_alert')) {
    function bl_sync_stock_alert($conn, $bank_id, $blood_type) {
        $bank_id = intval($bank_id);
        $stmt = mysqli_prepare($conn, "SELECT units_available, low_stock_threshold FROM blood_stock WHERE blood_bank_id = ? AND blood_type = ?");
        mysqli_stmt_bind_param($stmt, "is", $bank_id, $blood_type);
        mysqli_stmt_execute($stmt);
        $s = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$s) return;
        if ((int)$s['units_available'] < (int)$s['low_stock_threshold']) {
            $stmt = mysqli_prepare($conn, "INSERT INTO stock_alerts (blood_bank_id, blood_type, units_at_alert, threshold, status)
                SELECT ?, ?, ?, ?, 'active' FROM DUAL
                WHERE NOT EXISTS (SELECT 1 FROM stock_alerts WHERE blood_bank_id = ? AND blood_type = ? AND status = 'active')");
            $u = (int)$s['units_available']; $t = (int)$s['low_stock_threshold'];
            mysqli_stmt_bind_param($stmt, "isiiis", $bank_id, $blood_type, $u, $t, $bank_id, $blood_type);
            mysqli_stmt_execute($stmt);
            $raised = mysqli_stmt_affected_rows($stmt) > 0;
            mysqli_stmt_close($stmt);
            if ($raised) {
                $b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM blood_banks WHERE bank_id = $bank_id"));
                $title = "Low stock: $blood_type at " . ($b['name'] ?? 'a blood bank');
                $body  = "$u unit(s) left — below the threshold of $t.";
                bl_notify($conn, 'officer', bl_officer_ids($conn, $bank_id), 'low_stock', $title, $body, 'officer_dashboard.php');
                bl_notify($conn, 'admin', bl_admin_ids($conn), 'low_stock', $title, $body, "manage_blood_stock.php?bank=$bank_id");
            }
            return;
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE stock_alerts SET status = 'resolved', resolved_at = NOW()
                                           WHERE blood_bank_id = ? AND blood_type = ? AND status = 'active'");
            mysqli_stmt_bind_param($stmt, "is", $bank_id, $blood_type);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Each role's home page.
if (!function_exists('bl_home_for_role')) {
    function bl_home_for_role($role) {
        return ['admin' => 'dashboard.php', 'officer' => 'officer_dashboard.php', 'member' => 'member_dashboard.php'][$role] ?? 'index.php';
    }
}

// Neutralises spreadsheet formulas in exported cells (=, +, -, @ at the start).
if (!function_exists('bl_csv_cell')) {
    function bl_csv_cell($v) {
        $v = (string)$v;
        return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
    }
}

// Validates and inserts a hospital officer from an admin form. Returns [ok, html message].
if (!function_exists('bl_create_officer')) {
    function bl_create_officer($conn, array $in) {
        $name     = trim((string)($in['name'] ?? ''));
        $email    = strtolower(trim((string)($in['email'] ?? '')));
        $phone    = preg_replace('/[\s-]+/', '', trim((string)($in['phone'] ?? '')));
        $position = trim((string)($in['position'] ?? '')) ?: 'Blood Bank Officer';
        $bank_id  = intval($in['blood_bank_id'] ?? 0);
        $password = (string)($in['password'] ?? '');

        $e = [];
        if (mb_strlen($name) < 2) $e[] = 'Officer name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e[] = 'Enter a valid email address.';
        if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) $e[] = 'Enter a valid phone number (7–15 digits).';
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $e[] = 'Password must be at least 8 characters and include a letter and a number.';
        }
        $res = mysqli_query($conn, "SELECT bank_id FROM blood_banks WHERE bank_id = $bank_id");
        if (!$bank_id || !$res || !mysqli_fetch_assoc($res)) $e[] = 'Choose the blood bank this officer works at.';
        if (!$e) {
            $stmt = mysqli_prepare($conn, "SELECT worker_id FROM hospital_workers WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) $e[] = 'An officer with that email already exists.';
            mysqli_stmt_close($stmt);
        }
        if ($e) return [false, bl_errors_html($e)];

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO hospital_workers (name, phone, email, position, blood_bank_id, password) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssis", $name, $phone, $email, $position, $bank_id, $hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return [true, '<div class="alert alert-success text-center">Hospital Officer ' . htmlspecialchars($name) . ' added.</div>'];
    }
}

// ===================== NOTIFICATIONS (the bell) =====================
// One row per recipient. recipient_role + recipient_id identify the user:
// admin → login.user_id, officer → hospital_workers.worker_id, member → members.member_id.

// The signed-in user's [role, id], or [null, 0] when logged out.
if (!function_exists('bl_current_user')) {
    function bl_current_user() {
        $role = $_SESSION['role'] ?? null;
        if ($role === 'admin')   return ['admin',   intval($_SESSION['user_id'] ?? ($_SESSION['user'] ?? 0))];
        if ($role === 'officer') return ['officer', intval($_SESSION['officer_id'] ?? 0)];
        if ($role === 'member')  return ['member',  intval($_SESSION['member_id'] ?? 0)];
        return [null, 0];
    }
}

if (!function_exists('bl_notify')) {
    function bl_notify($conn, $role, $ids, $type, $title, $body = null, $link = null) {
        $stmt = mysqli_prepare($conn, "INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ((array)$ids as $id) {
            $id = intval($id);
            if (!$id) continue;
            mysqli_stmt_bind_param($stmt, "sissss", $role, $id, $type, $title, $body, $link);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// Ids of every active admin / every officer (optionally one bank's).
if (!function_exists('bl_admin_ids')) {
    function bl_admin_ids($conn) {
        $r = mysqli_query($conn, "SELECT user_id FROM login WHERE PW_STATUS = 'Active'");
        return $r ? array_column(mysqli_fetch_all($r, MYSQLI_ASSOC), 'user_id') : [];
    }
}
if (!function_exists('bl_officer_ids')) {
    function bl_officer_ids($conn, $bank_id = null) {
        $where = $bank_id ? 'WHERE blood_bank_id = ' . intval($bank_id) : '';
        $r = mysqli_query($conn, "SELECT worker_id FROM hospital_workers $where");
        return $r ? array_column(mysqli_fetch_all($r, MYSQLI_ASSOC), 'worker_id') : [];
    }
}

if (!function_exists('bl_unread_count')) {
    function bl_unread_count($conn, $role, $id) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM notifications WHERE recipient_role = ? AND recipient_id = ? AND is_read = 0");
        mysqli_stmt_bind_param($stmt, "si", $role, $id);
        mysqli_stmt_execute($stmt);
        $n = (int)mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
        mysqli_stmt_close($stmt);
        return $n;
    }
}

if (!function_exists('bl_notifications')) {
    function bl_notifications($conn, $role, $id, $limit = 8) {
        $limit = max(1, intval($limit));
        $stmt = mysqli_prepare($conn, "SELECT notification_id, type, title, body, link, is_read, created_at FROM notifications
                                       WHERE recipient_role = ? AND recipient_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT $limit");
        mysqli_stmt_bind_param($stmt, "si", $role, $id);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        return $rows;
    }
}

if (!function_exists('bl_time_ago')) {
    function bl_time_ago($ts) {
        $d = time() - strtotime($ts);
        if ($d < 60) return 'just now';
        if ($d < 3600) return floor($d / 60) . ' min ago';
        if ($d < 86400) return floor($d / 3600) . ' h ago';
        if ($d < 604800) return floor($d / 86400) . ' d ago';
        return date('d M Y', strtotime($ts));
    }
}

// Notification links are always pages inside this app.
if (!function_exists('bl_safe_link')) {
    function bl_safe_link($link) {
        return (is_string($link) && preg_match('/^[a-z0-9_\-]+\.php(\?[A-Za-z0-9_=&%\-]*)?$/', $link)) ? $link : null;
    }
}

// Members who can donate to a request for $blood_group right now (compatible,
// outside the 56-day window, available), excluding the requester.
if (!function_exists('bl_eligible_donor_ids')) {
    function bl_eligible_donor_ids($conn, $blood_group, $exclude_member_id = 0) {
        $ops = new operations();
        $types = $ops->get_compatible_donor_types($blood_group);
        if (!$types) return [];
        $in = "'" . implode("','", array_map(function ($t) use ($conn) { return mysqli_real_escape_string($conn, $t); }, $types)) . "'";
        $r = mysqli_query($conn, "SELECT member_id FROM members
                                  WHERE blood_group IN ($in) AND is_available = 1 AND member_id <> " . intval($exclude_member_id) . "
                                    AND (last_donation_date IS NULL OR DATEDIFF(CURDATE(), last_donation_date) >= 56)");
        return $r ? array_column(mysqli_fetch_all($r, MYSQLI_ASSOC), 'member_id') : [];
    }
}

if (!function_exists('bl_notification_icon')) {
    function bl_notification_icon($type) {
        return ['request_new' => 'fa-hand-holding-medical', 'request_status' => 'fa-clipboard-check', 'donation' => 'fa-tint',
                'emergency' => 'fa-triangle-exclamation', 'low_stock' => 'fa-flask', 'member_new' => 'fa-user-plus',
                'contact' => 'fa-envelope', 'alert' => 'fa-bell'][$type] ?? 'fa-bell';
    }
}
