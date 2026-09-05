<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not a donor (student role)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include 'config/db.php';

$student_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Fallback: look up by reg_no if user_id not set
if (!$student_id && !empty($_SESSION['reg_no'])) {
    $reg_no = mysqli_real_escape_string($db->connection, $_SESSION['reg_no']);
    $res = mysqli_query($db->connection, "SELECT student_id FROM students WHERE reg_no = '$reg_no'");
    if ($res && mysqli_num_rows($res)) {
        $r = mysqli_fetch_assoc($res);
        $student_id = intval($r['student_id']);
    }
}

$student = null;
if ($student_id) {
    $stmt = mysqli_query($db->connection, "SELECT student_id, name, reg_no FROM students WHERE student_id = $student_id");
    if ($stmt) $student = mysqli_fetch_assoc($stmt);
}

$userName = $student['name'] ?? $_SESSION['user_name'] ?? 'Test User';
$donorId  = $student['reg_no'] ?? $_SESSION['reg_no'] ?? 'N/A';
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
            <p>Donor ID: <strong><?php echo htmlspecialchars($donorId); ?></strong> &nbsp;|&nbsp; Access your donation form, donation history, assigned officer, and nearby blood banks.</p>
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
                <div class="quick-icon"><i class="fas fa-user-md"></i></div>
                <h3>My Hospital Officer</h3>
                <p>View your assigned hospital officer and their contact details.</p>
                <a href="my_officer.php" class="btn btn-outline-danger mt-auto">View Officer</a>
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