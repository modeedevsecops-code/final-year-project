<?php
require_once 'config/functions.php';  // starts session, connects DB, defines $db
$ops = new operations();

// Officers only
if (!isset($_SESSION['Active']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}

$officer_name = $_SESSION['name'] ?? 'Hospital Officer';
$requests = $ops->get_open_blood_requests(); // Pending + Approved, most urgent first
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>BloodLink | Blood Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/dashboard_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>BloodLink Officer</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="officer_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="assigned_donors.php"><i class="fas fa-users"></i> Assigned Donors</a></li>
            <li class="active"><a href="blood_requests.php"><i class="fas fa-exclamation-circle"></i> Blood Requests</a></li>
            <li><a href="add_donor.php"><i class="fas fa-user-plus"></i> Add Donor Record</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="top-navbar">
            <div class="user-profile">
                <span class="user-name"><?php echo htmlspecialchars($officer_name); ?></span>
                <div class="avatar"><?php echo strtoupper(substr($officer_name, 0, 2)); ?></div>
            </div>
        </header>

        <div class="content-body">
            <div class="welcome-banner">
                <div class="banner-text">
                    <span class="badge">OFFICER PORTAL</span>
                    <h1>Blood Requests</h1>
                    <p>Review pending and approved requests, most urgent first, and match them to eligible donors.</p>
                </div>
            </div>

            <?php if (empty($requests)): ?>
                <div class="dashboard-card" style="text-align:center;">
                    <i class="fas fa-check-circle"></i>
                    <h3>No open requests</h3>
                    <p>All blood requests are currently fulfilled or cancelled.</p>
                </div>
            <?php else: ?>
                <table class="table" style="width:100%; background:#fff; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:2px solid #eee;">
                            <th style="padding:10px;">Patient</th>
                            <th style="padding:10px;">Blood Group</th>
                            <th style="padding:10px;">Units</th>
                            <th style="padding:10px;">Hospital</th>
                            <th style="padding:10px;">Urgency</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px;"><?php echo htmlspecialchars($req['patient_name']); ?></td>
                                <td style="padding:10px;"><strong><?php echo htmlspecialchars($req['blood_group']); ?></strong></td>
                                <td style="padding:10px;"><?php echo (int)$req['units_needed']; ?></td>
                                <td style="padding:10px;">
                                    <?php echo htmlspecialchars($req['hospital_name']); ?><br>
                                    <small style="color:#888;"><?php echo htmlspecialchars($req['location']); ?></small>
                                </td>
                                <td style="padding:10px;">
                                    <?php
                                        $urgencyColor = $req['urgency_level'] === 'Critical Emergency' ? '#cc0000'
                                                      : ($req['urgency_level'] === 'Urgent' ? '#b8860b' : '#2f8f5b');
                                    ?>
                                    <span style="color:<?php echo $urgencyColor; ?>; font-weight:600;">
                                        <?php echo htmlspecialchars($req['urgency_level']); ?>
                                    </span>
                                </td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($req['status']); ?></td>
                                <td style="padding:10px;">
                                    <a href="match_donor.php?request_id=<?php echo (int)$req['request_id']; ?>" class="btn-primary" style="padding:6px 14px; font-size:0.85rem;">
                                        Match Donor
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>