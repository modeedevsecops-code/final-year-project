<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
require_once 'inc/header.php';

// Only admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>BloodLink | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-5">

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card h-100 border-danger text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-tint fa-2x"></i></div>
                <h5 class="fw-bold">Blood Donors</h5>
                <a href="manage_donors.php" class="btn btn-danger mt-3">Manage Donors</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-danger text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-user-md fa-2x"></i></div>
                <h5 class="fw-bold">Hospital Officers</h5>
                <a href="manage_officers.php" class="btn btn-danger mt-3">Manage Officers</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-danger text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-bell fa-2x"></i></div>
                <h5 class="fw-bold">Emergency Alerts</h5>
                <a href="notices.php" class="btn btn-danger mt-3">Manage Alerts</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-primary text-center p-4">
                <div class="mb-3 text-primary"><i class="fas fa-map-marker-alt fa-2x"></i></div>
                <h5 class="fw-bold">Geo-Location Map</h5>
                <a href="geo_map.php" class="btn btn-primary mt-3">View Map</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-danger text-center p-4">
                <div class="mb-3 text-danger"><i class="fas fa-hand-holding-medical fa-2x"></i></div>
                <h5 class="fw-bold">Blood Requests</h5>
                <a href="blood_requests.php" class="btn btn-danger mt-3">View Requests</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-success text-center p-4">
                <div class="mb-3 text-success"><i class="fas fa-chart-line fa-2x"></i></div>
                <h5 class="fw-bold">Reports &amp; Analytics</h5>
                <a href="reports.php" class="btn btn-success mt-3">View Report</a>
            </div>
        </div>

    </div>
</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>