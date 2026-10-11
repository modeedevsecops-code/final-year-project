<?php
require_once 'config/db.php';

// Officers only
bl_require_role('officer');

$officer_name = $_SESSION['name'] ?? 'Hospital Officer';
$conn = $db->connection;
$officer_id = intval($_SESSION['officer_id'] ?? 0);
$one = function ($sql) use ($conn) { $r = mysqli_query($conn, $sql); return $r ? mysqli_fetch_assoc($r) : null; };

$bank = $one("SELECT bb.* FROM hospital_workers hw JOIN blood_banks bb ON bb.bank_id = hw.blood_bank_id WHERE hw.worker_id = $officer_id");
$bank_id = (int)($bank['bank_id'] ?? 0);
$stock = [];
if ($bank_id) {
    $r = mysqli_query($conn, "SELECT blood_type, units_available, low_stock_threshold FROM blood_stock WHERE blood_bank_id = $bank_id
                              ORDER BY FIELD(blood_type,'A+','A-','B+','B-','AB+','AB-','O+','O-')");
    $stock = $r ? mysqli_fetch_all($r, MYSQLI_ASSOC) : [];
}
$open_requests   = (int)($one("SELECT COUNT(*) AS n FROM blood_requests WHERE status IN ('Pending','Approved')")['n'] ?? 0);
$critical_open   = (int)($one("SELECT COUNT(*) AS n FROM blood_requests WHERE status IN ('Pending','Approved') AND urgency_level = 'Critical Emergency'")['n'] ?? 0);
$my_month        = (int)($one("SELECT COALESCE(SUM(units),0) AS n FROM donations WHERE officer_id = $officer_id AND donation_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")['n'] ?? 0);
$low_count       = count(array_filter($stock, function ($x) { return (int)$x['units_available'] < (int)$x['low_stock_threshold']; }));
?>
<?php include 'inc/header.php'; ?>
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
                <p><?= $bank ? 'Blood bank: <strong>' . htmlspecialchars($bank['name']) . '</strong> &nbsp;|&nbsp; ' : '' ?>Match requests to donors, record donations, and keep an eye on your bank's stock.</p>
            </div>

            <?php if (!$bank): ?>
                <div class="alert alert-warning">Your account isn't linked to a blood bank yet, so you can't record donations. Ask an administrator to assign one under Hospital Officers.</div>
            <?php else: ?>
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-6"><div class="quick-card"><h3><?= $open_requests ?></h3><p>Open requests</p></div></div>
                <div class="col-md-3 col-6"><div class="quick-card"><h3 style="color:#cc0000;"><?= $critical_open ?></h3><p>Critical emergencies</p></div></div>
                <div class="col-md-3 col-6"><div class="quick-card"><h3><?= $low_count ?></h3><p>Low-stock groups at your bank</p></div></div>
                <div class="col-md-3 col-6"><div class="quick-card"><h3><?= $my_month ?></h3><p>Units you recorded this month</p></div></div>
            </div>

            <div class="quick-card" style="text-align:left; margin-bottom:24px;">
                <h3 style="margin-bottom:1rem;">Stock at <?= htmlspecialchars($bank['name']) ?></h3>
                <div class="row g-2">
                    <?php foreach ($stock as $st): $low = (int)$st['units_available'] < (int)$st['low_stock_threshold']; ?>
                        <div class="col-lg-3 col-md-4 col-6">
                            <div style="border:1px solid <?= $low ? '#f5c2c7' : '#eee' ?>; background:<?= $low ? '#fff5f5' : '#fff' ?>; border-radius:8px; padding:10px 12px;">
                                <strong style="color:#7a0000;"><?= htmlspecialchars($st['blood_type']) ?></strong>
                                <span style="float:right; font-weight:700; color:<?= $low ? '#d32f2f' : '#333' ?>;"><?= (int)$st['units_available'] ?> units</span>
                                <div style="font-size:.78rem; color:<?= $low ? '#d32f2f' : '#888' ?>;">
                                    <?= $low ? 'Below threshold (' . (int)$st['low_stock_threshold'] . ')' : 'Threshold ' . (int)$st['low_stock_threshold'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-4 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-users"></i></div>
                        <h3>Donors</h3>
                        <p>Browse the donor pool with blood group and 56-day eligibility.</p>
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
                        <p>Find blood banks on the map and get driving directions.</p>
                        <a href="geo_map.php" class="btn btn-outline-danger mt-auto">Open Map</a>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-tint"></i></div>
                        <h3>Record Donation</h3>
                        <p>Log a walk-in donation and add the units to your bank's stock.</p>
                        <a href="record_donation.php" class="btn btn-danger mt-auto">Record Donation</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
