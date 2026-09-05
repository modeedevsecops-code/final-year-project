<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not a recipient
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'recipient') {
    header("Location: login.php");
    exit();
}

$recipientName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Recipient';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

  <?php include 'inc/header.php'; ?>
  <body>

    <main class="main" id="top">

      <?php include 'inc/navbar.php'; ?>

      <!-- ============================================-->
      <!-- Recipient Dashboard Section -->
      <!-- ============================================-->
      <div class="main-content">
        <div class="container-fluid">

          <!-- Welcome Banner -->
          <div class="welcome-banner">
            <span class="badge-pill">RECIPIENT DASHBOARD</span>
            <h1>Welcome back, <?php echo htmlspecialchars($recipientName); ?>!</h1>
            <p>Manage your blood requests, check request statuses, and track emergency updates.</p>
          </div>

          <!-- Quick Action Cards -->
          <div class="row g-4">

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-hand-holding-medical"></i></div>
                <h3>Request Blood</h3>
                <p>Create a new emergency or scheduled blood request for hospitals.</p>
                <a href="request_blood.php" class="btn btn-danger mt-auto">Request Now</a>
              </div>
            </div>

            <div class="col-md-4 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-history"></i></div>
                <h3>Request History</h3>
                <p>View the progress and fulfillment history of your previous blood requests.</p>
                <a href="request_history.php" class="btn btn-outline-danger mt-auto">View History</a>
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

            <div class="col-md-6 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Nearby Blood Banks</h3>
                <p>Find the closest blood bank or hospital to your location on the map.</p>
                <a href="geo_map.php" class="btn btn-outline-danger mt-auto">View Map</a>
              </div>
            </div>

            <div class="col-md-6 col-sm-6">
              <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-sign-out-alt"></i></div>
                <h3>Logout</h3>
                <p>Sign out of your BloodLink recipient account securely.</p>
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