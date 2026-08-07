<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'inc/header.php';

// Redirect if not a recipient/patient
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'recipient') {
    header("Location: login.php");
    exit();
}

$recipient_name = $_SESSION['user_name'] ?? 'Test Recipient';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>BloodLink | Recipient Dashboard</title>
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
            <h2>BloodLink</h2>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="recipient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="request_blood.php"><i class="fas fa-hand-holding-medical"></i> Request Blood</a></li>
            <li><a href="request_history.php"><i class="fas fa-history"></i> Request History</a></li>
            <li><a href="nearby_banks.php"><i class="fas fa-hospital"></i> Nearby Blood Banks</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="user-profile">
                <span class="user-name"><?php echo htmlspecialchars($recipient_name); ?></span>
                <div class="avatar"><?php echo strtoupper(substr($recipient_name, 0, 2)); ?></div>
            </div>
        </header>

        <!-- Content Body -->
        <div class="content-body">
            <!-- Hero Banner -->
            <div class="welcome-banner">
                <div class="banner-text">
                    <span class="badge">RECIPIENT DASHBOARD</span>
                    <h1>Welcome back, <?php echo htmlspecialchars($recipient_name); ?>!</h1>
                    <p>Manage your blood requests, check request statuses, and track emergency updates.</p>
                </div>
                <a href="request_blood.php" class="btn-primary">+ Request Blood</a>
            </div>

            <!-- Action Cards Grid -->
            <div class="cards-grid">
                <a href="request_blood.php" class="dashboard-card" style="text-decoration: none;">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Post Blood Request</h3>
                    <p>Create a new emergency or scheduled blood request for hospitals.</p>
                </a>
                <a href="request_history.php" class="dashboard-card" style="text-decoration: none;">
                    <i class="fas fa-history"></i>
                    <h3>Request History</h3>
                    <p>View the progress and fulfillment history of your previous blood requests.</p>
                </a>
                <a href="nearby_banks.php" class="dashboard-card" style="text-decoration: none;">
                    <i class="fas fa-hospital"></i>
                    <h3>Nearby Blood Banks</h3>
                    <p>Locate active blood banks and hospitals near your current location.</p>
                </a>
            </div>
        </div>
    </main>
</div>

</body>
</html>