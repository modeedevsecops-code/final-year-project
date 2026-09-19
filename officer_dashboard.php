<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

// Officers only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$officer_name = $_SESSION['name'] ?? 'Hospital Officer';
include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="welcome-banner">
                <span class="badge-pill">OFFICER PORTAL</span>
                <h1>Welcome back, <?php echo htmlspecialchars($officer_name); ?>!</h1>
                <p>Monitor assigned donors, review incoming blood requests, and coordinate inventory.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-users"></i></div>
                        <h3>Donors</h3>
                        <p>Browse the donor pool with blood group and current eligibility.</p>
                        <a href="assigned_donors.php" class="btn btn-danger mt-auto">View Donors</a>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-hand-holding-medical"></i></div>
                        <h3>Blood Requests</h3>
                        <p>Review pending requests and match them to eligible, nearest donors.</p>
                        <a href="blood_requests.php" class="btn btn-danger mt-auto">Manage Requests</a>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-bell"></i></div>
                        <h3>Emergency Alerts</h3>
                        <p>Post urgent blood alerts and see system-generated emergencies.</p>
                        <a href="notices.php" class="btn btn-outline-danger mt-auto">View Alerts</a>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <h3>Geo-Map</h3>
                        <p>See donors and open requests on the live map.</p>
                        <a href="geo_map.php" class="btn btn-outline-danger mt-auto">Open Map</a>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-sign-out-alt"></i></div>
                        <h3>Logout</h3>
                        <p>Sign out of your BloodLink officer account securely.</p>
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
