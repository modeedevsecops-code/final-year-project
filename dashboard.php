<?php
session_start();
require_once 'config/db.php'; // adjust path if your db include has a different name/location

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Helper: safe count query (won't crash if a table/query fails) ---
function safe_count($db, $sql) {
    $result = $db->connection->query($sql);
    return $result ? ($result->fetch_assoc()['c'] ?? 0) : 0;
}

// --- Summary stats (using real table names from donor_app.sql) ---
$total_donors     = safe_count($db, "SELECT COUNT(*) AS c FROM members");
$total_officers   = safe_count($db, "SELECT COUNT(*) AS c FROM hospital_workers");
$pending_requests = safe_count($db, "SELECT COUNT(*) AS c FROM blood_requests WHERE status = 'Pending'");

$active_alerts = safe_count($db, "SELECT COUNT(*) AS c FROM stock_alerts WHERE status = 'active'");

// --- Recent activity: last 5 blood requests ---
$recent = $db->connection->query("SELECT * FROM blood_requests ORDER BY created_at DESC LIMIT 5");
$recent_requests = $recent ? $recent->fetch_all(MYSQLI_ASSOC) : [];

// Current stock by blood group (summed across all banks) — for the chart.
$stock_by_group = [];
$order = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$sres = $db->connection->query(
    "SELECT blood_type, SUM(units_available) AS units FROM blood_stock GROUP BY blood_type"
);
if ($sres) { while ($row = $sres->fetch_assoc()) { $stock_by_group[$row['blood_type']] = (int)$row['units']; } }
$stock_labels = $order;
$stock_values = array_map(function ($g) use ($stock_by_group) { return $stock_by_group[$g] ?? 0; }, $order);

$adminName = $_SESSION['user_name'] ?? 'Admin';

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="main-content">

    <!-- Welcome Banner (same style as donor dashboard) -->
    <div class="welcome-banner">
        <span class="badge-pill">DASHBOARD OVERVIEW</span>
        <h1>Welcome back, <?= htmlspecialchars($adminName) ?>!</h1>
        <p>Monitor donors, hospital officers, blood requests, and stock levels from one place.</p>
    </div>

    <!-- Stat cards row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-users"></i></div>
                <h3><?= $total_donors ?></h3>
                <p>Total Members</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-user"></i></div>
                <h3><?= $total_officers ?></h3>
                <p>Hospital Officers</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="quick-card">
                <div class="quick-icon"><i class="fas fa-clock"></i></div>
                <h3><?= $pending_requests ?></h3>
                <p>Pending Requests</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="quick-card">
                <div class="quick-icon" style="<?= $active_alerts > 0 ? 'background:#fde2e2;color:#d32f2f;' : '' ?>">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 style="<?= $active_alerts > 0 ? 'color:#d32f2f;' : '' ?>"><?= $active_alerts ?></h3>
                <p>Low Stock Alerts</p>
            </div>
        </div>
    </div>

    <!-- Stock chart -->
    <div class="quick-card" style="text-align:left; margin-bottom:1.5rem;">
        <h3 style="margin-bottom:1rem;">Current Blood Stock by Group (all banks)</h3>
        <canvas id="stockChart" height="90"></canvas>
    </div>

    <!-- Recent activity -->
    <div class="quick-card" style="text-align:left;">
        <h3 style="margin-bottom:1rem;">Recent Blood Requests</h3>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f9f9f9; text-align:left;">
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Blood Group</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Patient</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Hospital</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Status</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_requests)): ?>
                <tr><td colspan="5" style="padding:10px; color:#888;">No recent requests.</td></tr>
                <?php else: foreach ($recent_requests as $r): ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($r['blood_group']) ?></span></td>
                    <td style="padding:10px;"><?= htmlspecialchars($r['patient_name']) ?></td>
                    <td style="padding:10px;"><?= htmlspecialchars($r['hospital_name']) ?></td>
                    <td style="padding:10px;"><?= htmlspecialchars($r['status']) ?></td>
                    <td style="padding:10px; color:#6c757d; font-size:0.9rem;"><?= htmlspecialchars($r['created_at']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<?php include 'inc/main_js.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById('stockChart');
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($stock_labels); ?>,
            datasets: [{ label: 'Units available', data: <?php echo json_encode($stock_values); ?>, backgroundColor: '#cc0000' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } },
                   scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>