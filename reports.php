<?php
include 'inc/header.php';
include 'config/db.php';

// Admin-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

global $db;
$conn = $db->connection;

// ─────────────────────────────────────────────────
// Filters
// ─────────────────────────────────────────────────
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

$df = $date_from ? mysqli_real_escape_string($conn, $date_from) : '';
$dt = $date_to ? mysqli_real_escape_string($conn, $date_to) : '';

// ─────────────────────────────────────────────────
// CSV Export – Donations
// ─────────────────────────────────────────────────
if ($export === 'donations_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=blood_donations_report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#', 'Donation Date', 'Donor Name', 'Donor ID', 'Blood Group', 'Hospital Officer', 'Hospital / Blood Bank', 'Donation Notes', 'Status', 'Officer Comment', 'Submitted At']);

    $q = "SELECT lb.log_id, lb.entry_date,
                 s.name AS donor_name, s.reg_no AS donor_id, s.year_of_study AS blood_group,
                 st.staff_name AS officer_name, p.title AS hospital,
                 lb.activities, lb.status, lb.supervisor_comment, lb.created_at
          FROM logbook_entries lb
          LEFT JOIN students  s  ON s.student_id   = lb.student_id
          LEFT JOIN projects  p  ON p.assigned_student = s.student_id
          LEFT JOIN staff     st ON st.staff_id     = p.assigned_supervisor
          WHERE 1=1";
    if ($df)
        $q .= " AND DATE(lb.created_at) >= '$df'";
    if ($dt)
        $q .= " AND DATE(lb.created_at) <= '$dt'";
    $q .= " ORDER BY lb.created_at DESC LIMIT 5000";

    $res = mysqli_query($conn, $q);
    $n = 1;
    while ($r = mysqli_fetch_assoc($res)) {
        fputcsv($out, [
            $n++,
            $r['entry_date'],
            $r['donor_name'],
            $r['donor_id'],
            $r['blood_group'],
            $r['officer_name'],
            $r['hospital'],
            $r['activities'],
            $r['status'],
            $r['supervisor_comment'],
            $r['created_at']
        ]);
    }
    fclose($out);
    exit();
}

// ─────────────────────────────────────────────────
// KPI Counters
// ─────────────────────────────────────────────────
function safe_count($conn, $sql)
{
    $res = mysqli_query($conn, $sql);
    if (!$res)
        return 0;
    $row = mysqli_fetch_assoc($res);
    return intval($row['cnt'] ?? 0);
}

$total_donors = safe_count($conn, "SELECT COUNT(*) AS cnt FROM students");
$total_officers = safe_count($conn, "SELECT COUNT(*) AS cnt FROM staff");
$total_donations = safe_count($conn, "SELECT COUNT(*) AS cnt FROM logbook_entries");
$total_alerts = safe_count($conn, "SELECT COUNT(*) AS cnt FROM notices");

// Blood requests table may or may not exist
$chk = mysqli_query($conn, "SHOW TABLES LIKE 'blood_requests'");
$total_requests = (mysqli_num_rows($chk) > 0)
    ? safe_count($conn, "SELECT COUNT(*) AS cnt FROM blood_requests")
    : 0;

// Pending donations needing review
$pending_count = safe_count($conn, "SELECT COUNT(*) AS cnt FROM logbook_entries WHERE status='pending'");
$approved_count = safe_count($conn, "SELECT COUNT(*) AS cnt FROM logbook_entries WHERE status='approved'");
$rejected_count = safe_count($conn, "SELECT COUNT(*) AS cnt FROM logbook_entries WHERE status='rejected'");

// ─────────────────────────────────────────────────
// Blood Group Distribution (from students.year_of_study = blood group)
// ─────────────────────────────────────────────────
$bg_res = mysqli_query(
    $conn,
    "SELECT year_of_study AS blood_group, COUNT(*) AS cnt
     FROM students
     GROUP BY year_of_study
     ORDER BY cnt DESC"
);
$blood_groups = [];
if ($bg_res) {
    while ($r = mysqli_fetch_assoc($bg_res)) {
        if ($r['blood_group'])
            $blood_groups[] = $r;
    }
}

// ─────────────────────────────────────────────────
// Top Donors (most donations)
// ─────────────────────────────────────────────────
$top_donors_res = mysqli_query(
    $conn,
    "SELECT s.student_id, s.name, s.reg_no AS donor_id, s.year_of_study AS blood_group,
            COUNT(lb.log_id) AS donation_count
     FROM students s
     LEFT JOIN logbook_entries lb ON lb.student_id = s.student_id
     GROUP BY s.student_id
     ORDER BY donation_count DESC
     LIMIT 10"
);

// ─────────────────────────────────────────────────
// Top Hospital Officers (most donors assigned)
// ─────────────────────────────────────────────────
$top_officers_res = mysqli_query(
    $conn,
    "SELECT st.staff_id, st.staff_name, st.position,
            COUNT(p.project_id) AS donors_assigned
     FROM staff st
     LEFT JOIN projects p ON p.assigned_supervisor = st.staff_id
     GROUP BY st.staff_id
     ORDER BY donors_assigned DESC
     LIMIT 10"
);

// ─────────────────────────────────────────────────
// Recent Blood Donations (filtered)
// ─────────────────────────────────────────────────
$recent_q = "SELECT lb.log_id, lb.entry_date,
                    s.name AS donor_name, s.reg_no AS donor_id, s.year_of_study AS blood_group,
                    st.staff_name AS officer_name,
                    p.title AS hospital,
                    lb.activities, lb.status, lb.created_at
             FROM logbook_entries lb
             LEFT JOIN students  s  ON s.student_id       = lb.student_id
             LEFT JOIN projects  p  ON p.assigned_student = s.student_id
             LEFT JOIN staff     st ON st.staff_id        = p.assigned_supervisor
             WHERE 1=1";
if ($df)
    $recent_q .= " AND DATE(lb.created_at) >= '$df'";
if ($dt)
    $recent_q .= " AND DATE(lb.created_at) <= '$dt'";
$recent_q .= " ORDER BY lb.created_at DESC LIMIT 100";
$recent_res = mysqli_query($conn, $recent_q);

// ─────────────────────────────────────────────────
// Recent Emergency Alerts
// ─────────────────────────────────────────────────
$alerts_res = mysqli_query(
    $conn,
    "SELECT n.notice_id, n.title, n.message, n.created_at,
            COALESCE(s.staff_name, 'BloodLink Admin') AS posted_by
     FROM notices n
     LEFT JOIN staff s ON s.staff_id = n.supervisor_id
     ORDER BY n.created_at DESC
     LIMIT 10"
);
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <title>Reports &amp; Analytics | BloodLink Blood Bank</title>
</head>

<body>
    <?php include 'inc/navbar.php'; ?>

    <main class="main" id="top">
        <div class="container py-5 mt-4">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
                <div>
                    <h3 class="mb-0 fw-bold text-danger">📊 BloodLink Reports &amp; Analytics</h3>
                    <p class="text-muted small mb-0">Overview of blood donations, donor activity, and hospital
                        operations.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="Dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
                </div>
            </div>

            <!-- Date Filters + Export -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body bg-light rounded">
                    <form method="get" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">From Date</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">To Date</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-danger btn-sm w-100">Apply Filter</button>
                        </div>
                        <div class="col-md-3">
                            <a class="btn btn-outline-danger btn-sm w-100"
                                href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'donations_csv'])); ?>">
                                ⬇ Export CSV Report
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm text-center py-3 h-100">
                        <div class="card-body p-2">
                            <h3 class="text-danger fw-bold"><?php echo $total_donors; ?></h3>
                            <p class="mb-0 small text-muted">Donors</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm text-center py-3 h-100">
                        <div class="card-body p-2">
                            <h3 class="text-primary fw-bold"><?php echo $total_officers; ?></h3>
                            <p class="mb-0 small text-muted">Officers</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm text-center py-3 h-100">
                        <div class="card-body p-2">
                            <h3 class="text-success fw-bold"><?php echo $total_donations; ?></h3>
                            <p class="mb-0 small text-muted">Donations</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm text-center py-3 h-100">
                        <div class="card-body p-2">
                            <h3 class="text-warning fw-bold"><?php echo $pending_count; ?></h3>
                            <p class="mb-0 small text-muted">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm text-center py-3 h-100">
                        <div class="card-body p-2">
                            <h3 class="text-danger fw-bold"><?php echo $total_alerts; ?></h3>
                            <p class="mb-0 small text-muted">Alerts</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card border-0 shadow-sm text-center py-3 h-100">
                        <div class="card-body p-2">
                            <h3 class="text-info fw-bold"><?php echo $total_requests; ?></h3>
                            <p class="mb-0 small text-muted">Blood Requests</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donation Status Breakdown -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-dark text-white fw-bold">Donation Status Breakdown</div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>✅ Approved</span>
                                    <span class="badge bg-success fs-6"><?php echo $approved_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>⏳ Pending Review</span>
                                    <span class="badge bg-warning text-dark fs-6"><?php echo $pending_count; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>❌ Rejected</span>
                                    <span class="badge bg-danger fs-6"><?php echo $rejected_count; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Blood Group Distribution -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-dark text-white fw-bold">Donor Blood Groups</div>
                        <div class="card-body">
                            <?php if (!empty($blood_groups)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($blood_groups as $bg): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span
                                                class="fw-bold text-danger"><?php echo htmlspecialchars($bg['blood_group']); ?></span>
                                            <span class="badge bg-danger"><?php echo $bg['cnt']; ?> Donor(s)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No blood group data available.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Emergency Alerts -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-danger text-white fw-bold">🚨 Recent Emergency Alerts</div>
                        <div class="card-body p-2">
                            <?php if ($alerts_res && mysqli_num_rows($alerts_res) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($al = mysqli_fetch_assoc($alerts_res)): ?>
                                        <li class="list-group-item px-2 py-2">
                                            <div class="fw-bold small"><?php echo htmlspecialchars($al['title']); ?></div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($al['posted_by']); ?> •
                                                <?php echo date('d M Y', strtotime($al['created_at'])); ?>
                                            </small>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info mb-0 m-2">No emergency alerts yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Officers & Donors -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-dark text-white fw-bold">Top Hospital Officers (By Donors Assigned)
                        </div>
                        <div class="card-body">
                            <?php if ($top_officers_res && mysqli_num_rows($top_officers_res) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Officer Name</th>
                                                <th>Role / Hospital</th>
                                                <th>Donors</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $k = 1;
                                            while ($r = mysqli_fetch_assoc($top_officers_res)): ?>
                                                <tr>
                                                    <td><?php echo $k++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($r['staff_name']); ?></td>
                                                    <td><small
                                                            class="text-muted"><?php echo htmlspecialchars($r['position'] ?: '—'); ?></small>
                                                    </td>
                                                    <td><span
                                                            class="badge bg-danger"><?php echo $r['donors_assigned']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No officer data available.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-dark text-white fw-bold">Top Active Donors (By Donations Made)</div>
                        <div class="card-body">
                            <?php if ($top_donors_res && mysqli_num_rows($top_donors_res) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Donor Name</th>
                                                <th>Donor ID</th>
                                                <th>Blood Group</th>
                                                <th>Donations</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $m = 1;
                                            while ($r = mysqli_fetch_assoc($top_donors_res)): ?>
                                                <tr>
                                                    <td><?php echo $m++; ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($r['name']); ?></td>
                                                    <td><code><?php echo htmlspecialchars($r['donor_id']); ?></code></td>
                                                    <td><span
                                                            class="badge bg-danger"><?php echo htmlspecialchars($r['blood_group']); ?></span>
                                                    </td>
                                                    <td><span
                                                            class="badge bg-success"><?php echo $r['donation_count']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No donor activity data yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Donations Table -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white fw-bold">
                    Recent Blood Donation Records
                    <span class="float-end small fw-normal">Showing latest 100</span>
                </div>
                <div class="card-body">
                    <?php if (!$recent_res || mysqli_num_rows($recent_res) === 0): ?>
                        <div class="alert alert-info mb-0">
                            No donation records
                            found<?php echo ($date_from || $date_to) ? ' for the selected date range.' : '.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Donor Name</th>
                                        <th>Donor ID</th>
                                        <th>Blood Group</th>
                                        <th>Hospital Officer</th>
                                        <th>Hospital / Location</th>
                                        <th>Notes</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $n = 1;
                                    while ($row = mysqli_fetch_assoc($recent_res)): ?>
                                        <tr>
                                            <td><?php echo $n++; ?></td>
                                            <td><?php echo htmlspecialchars($row['entry_date']); ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($row['donor_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($row['donor_id']); ?></code></td>
                                            <td>
                                                <span
                                                    class="badge bg-danger"><?php echo htmlspecialchars($row['blood_group']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['officer_name'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($row['hospital'] ?: '—'); ?></td>
                                            <td style="max-width:220px; white-space:pre-wrap;">
                                                <?php echo nl2br(htmlspecialchars($row['activities'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                echo $row['status'] === 'approved' ? 'success'
                                                    : ($row['status'] === 'rejected' ? 'danger' : 'warning text-dark');
                                                ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <?php include 'inc/main_js.php'; ?>
</body>

</html>