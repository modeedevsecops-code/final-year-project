<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'inc/header.php';

// Redirect if not an officer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: login.php");
    exit();
}

$officer_name = $_SESSION['user_name'] ?? 'Hospital Officer';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>BloodLink | Officer Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/dashboard_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>BloodLink Officer</h2>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="officer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="assigned_donors.php"><i class="fas fa-users"></i> Assigned Donors</a></li>
            <li><a href="blood_requests.php"><i class="fas fa-exclamation-circle"></i> Blood Requests</a></li>
            <li><a href="add_donor.php"><i class="fas fa-user-plus"></i> Add Donor Record</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="user-profile">
                <span class="user-name"><?php echo htmlspecialchars($officer_name); ?></span>
                <div class="avatar"><?php echo strtoupper(substr($officer_name, 0, 2)); ?></div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            <!-- Hero Banner -->
            <div class="welcome-banner">
                <div class="banner-text">
                    <span class="badge">OFFICER PORTAL</span>
                    <h1>Welcome back, <?php echo htmlspecialchars($officer_name); ?>!</h1>
                    <p>Monitor assigned donors, review incoming blood requests, and coordinate inventory.</p>
                </div>
                <a href="blood_requests.php" class="btn-primary">Manage Requests</a>
            </div>

            <!-- Action Cards Grid -->
            <div class="cards-grid">
                <a href="assigned_donors.php" class="dashboard-card" style="text-decoration: none;">
                    <i class="fas fa-users"></i>
                    <h3>Assigned Donors</h3>
                    <p>View and manage the donors assigned to your hospital or blood bank branch.</p>
                </a>
                <a href="blood_requests.php" class="dashboard-card" style="text-decoration: none;">
                    <i class="fas fa-tint"></i>
                    <h3>Incoming Requests</h3>
                    <p>Review pending blood requests and coordinate emergency notifications.</p>
                </a>
                <a href="add_donor.php" class="dashboard-card" style="text-decoration: none;">
                    <i class="fas fa-user-plus"></i>
                    <h3>Add Donor Record</h3>
                    <p>Register new donor profiles and log walk-in donations manually.</p>
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>