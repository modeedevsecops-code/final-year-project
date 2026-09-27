<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not a donor (donor role)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

$donor_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Fallback: look up by donor_code if user_id not set
if (!$donor_id && !empty($_SESSION['donor_code'])) {
    $donor_code = mysqli_real_escape_string($db->connection, $_SESSION['donor_code']);
    $res = mysqli_query($db->connection, "SELECT donor_id FROM donors WHERE donor_code = '$donor_code'");
    if ($res && mysqli_num_rows($res)) {
        $r = mysqli_fetch_assoc($res);
        $donor_id = intval($r['donor_id']);
    }
}

$ops = new operations();
if ($donor_id) { $ops->update_donor_availability($donor_id); } // handles the POST

$donor = null;
if ($donor_id) {
    $stmt = mysqli_query($db->connection, "SELECT donor_id, name, donor_code, is_available, availability_schedule, contact_preference FROM donors WHERE donor_id = $donor_id");
    if ($stmt) $donor = mysqli_fetch_assoc($stmt);
}

$userName = $donor['name'] ?? $_SESSION['user_name'] ?? 'Test User';
$donorId  = $donor['donor_code'] ?? $_SESSION['donor_code'] ?? 'N/A';
$isAvail  = (int)($donor['is_available'] ?? 1);
$sched    = $donor['availability_schedule'] ?? 'anytime';
$contact  = $donor['contact_preference'] ?? 'both';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

  <?php include 'inc/header.php'; ?>
  <body>

    <main class="main" id="top">

      <?php include 'inc/navbar.php'; ?>

      <!-- ============================================-->
      <!-- Donor Dashboard Section -->
      <!-- ============================================-->
      <div class="main-content">
        <div class="container-fluid">

          <!-- Welcome Banner -->
          <div class="welcome-banner">
            <span class="badge-pill">DASHBOARD OVERVIEW</span>
            <h1>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
            <p>Donor ID: <strong><?php echo htmlspecialchars($donorId); ?></strong> &nbsp;|&nbsp; Access your donation form, see emergency alerts, and find nearby blood banks.</p>
          </div>

          <!-- Availability -->
          <div class="quick-card" style="text-align:left; margin-bottom:20px;">
            <?php $ops->display_message(); ?>
            <h3 style="margin:0 0 4px;"><i class="fas fa-hand-holding-heart" style="color:#cc0000;"></i> My Availability</h3>
            <p style="color:#6c757d; font-size:.9rem;">Let blood banks know when you can donate. Available donors surface first when an officer searches for a match.</p>
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
                <p>Submit a new blood donation record at your nearest blood bank.</p>
                <a href="donation_form.php" class="btn btn-danger mt-auto">Donate Now</a>
              </div>
            </div>


            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-bell"></i></div>
                <h3>Emergency Alerts</h3>
                <p>View urgent blood requests posted by hospital officers near you.</p>
                <a href="emergency_alerts.php" class="btn btn-danger mt-auto">View Alerts</a>
              </div>
            </div>

            <div class="col-md-6 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Nearby Blood Banks</h3>
                <p>Find the closest blood bank or hospital to your location on the map.</p>
                <a href="nearby_banks.php" class="btn btn-outline-danger mt-auto">View Map</a>
              </div>
            </div>

            <div class="col-md-6 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-sign-out-alt"></i></div>
                <h3>Logout</h3>
                <p>Sign out of your BloodLink donor account securely.</p>
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