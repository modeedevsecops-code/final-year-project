<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Members only (a member is a unified donor + recipient account).
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

$member_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$ops = new operations();
if ($member_id) { $ops->update_donor_availability($member_id); } // handles the availability POST

$member = null;
if ($member_id) {
    $stmt = mysqli_query($db->connection, "SELECT member_id, name, member_code, blood_group, is_available, availability_schedule, contact_preference, last_donation_date FROM members WHERE member_id = $member_id");
    if ($stmt) $member = mysqli_fetch_assoc($stmt);
}

$userName = $member['name'] ?? $_SESSION['name'] ?? 'Member';
$memberId = $member['member_code'] ?? $_SESSION['member_code'] ?? 'N/A';
$bloodGrp = $member['blood_group'] ?? '—';
$isAvail  = (int)($member['is_available'] ?? 1);
$sched    = $member['availability_schedule'] ?? 'anytime';
$contact  = $member['contact_preference'] ?? 'both';
$eligible = $ops->is_donor_eligible($member['last_donation_date'] ?? null);
$waitDays = $ops->days_until_eligible($member['last_donation_date'] ?? null);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

  <?php include 'inc/header.php'; ?>
  <body>

    <main class="main" id="top">

      <?php include 'inc/navbar.php'; ?>

      <!-- ============================================-->
      <!-- Member Dashboard Section -->
      <!-- ============================================-->
      <div class="main-content">
        <div class="container-fluid">

          <!-- Welcome Banner -->
          <div class="welcome-banner">
            <span class="badge-pill">MEMBER DASHBOARD</span>
            <h1>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
            <p>Member ID: <strong><?php echo htmlspecialchars($memberId); ?></strong> &nbsp;|&nbsp; Blood group: <strong><?php echo htmlspecialchars($bloodGrp); ?></strong> &nbsp;|&nbsp; Donate, request blood, and find nearby banks — all from one account.</p>
          </div>

          <!-- Availability -->
          <div class="quick-card" style="text-align:left; margin-bottom:20px;">
            <?php $ops->display_message(); ?>
            <h3 style="margin:0 0 4px;"><i class="fas fa-hand-holding-heart" style="color:#cc0000;"></i> My Donor Availability</h3>
            <p style="color:#6c757d; font-size:.9rem;">
              Let blood banks know when you can donate — available members surface first when an officer searches for a match.
              <?php if ($eligible): ?>
                <span style="color:#2c8a4a; font-weight:600;">You are currently eligible to donate.</span>
              <?php else: ?>
                <span style="color:#a9660a; font-weight:600;"><?php echo (int)$waitDays; ?> day(s) left in your 56-day window.</span>
              <?php endif; ?>
            </p>
            <form method="POST" class="row g-2 align-items-end">
              <?php bl_csrf_field(); ?>
              <div class="col-md-3">
                <label class="small fw-bold d-block">Status</label>
                <label class="small"><input type="checkbox" name="is_available" value="1" <?= $isAvail?'checked':'' ?>> Available to donate</label>
              </div>
              <div class="col-md-3">
                <label class="small fw-bold">Schedule</label>
                <select name="availability_schedule" class="form-control form-select form-select-sm">
                  <?php foreach (['anytime','weekdays','weekends','mornings','afternoons','evenings','emergencies'] as $s): ?>
                    <option value="<?= $s ?>" <?= $sched===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="small fw-bold">Contact preference</label>
                <select name="contact_preference" class="form-control form-select form-select-sm">
                  <?php foreach (['both','phone','sms','emergency-only'] as $c): ?>
                    <option value="<?= $c ?>" <?= $contact===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <button type="submit" name="btn_update_availability" class="btn btn-danger btn-sm w-100">Update Availability</button>
              </div>
            </form>
          </div>

          <!-- Quick Action Cards -->
          <div class="row g-4">

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-tint"></i></div>
                <h3>Donate Blood</h3>
                <p>Check your eligibility and record a donation at your nearest blood bank.</p>
                <a href="donation_form.php" class="btn btn-danger mt-auto">Donate Now</a>
              </div>
            </div>

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-hand-holding-medical"></i></div>
                <h3>Request Blood</h3>
                <p>File an emergency or scheduled blood request for a patient.</p>
                <a href="request_blood.php" class="btn btn-danger mt-auto">Request Now</a>
              </div>
            </div>

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-history"></i></div>
                <h3>Request History</h3>
                <p>Track the status and fulfilment of your previous blood requests.</p>
                <a href="request_history.php" class="btn btn-outline-danger mt-auto">View History</a>
              </div>
            </div>

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-bell"></i></div>
                <h3>Emergency Alerts</h3>
                <p>View urgent blood requests posted by hospital officers near you.</p>
                <a href="emergency_alerts.php" class="btn btn-outline-danger mt-auto">View Alerts</a>
              </div>
            </div>

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Nearby Blood Banks</h3>
                <p>Find the closest blood bank on the map and get driving directions.</p>
                <a href="geo_map.php" class="btn btn-outline-danger mt-auto">View Map</a>
              </div>
            </div>

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-sign-out-alt"></i></div>
                <h3>Logout</h3>
                <p>Sign out of your BloodLink member account securely.</p>
                <a href="logout.php" class="btn btn-outline-secondary mt-auto">Logout</a>
              </div>
            </div>

          </div>

        </div>
      </div>

    </main>

    <?php include 'inc/main_js.php'; ?>
  </body>
</html>
