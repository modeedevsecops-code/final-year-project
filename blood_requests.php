<?php
include 'inc/header.php';
include 'config/db.php';

// Only admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$db_conn = $db->connection;

// Handle new blood request form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_add_request'])) {
    $patient_name = mysqli_real_escape_string($db_conn, $_POST['patient_name']);
    $blood_group = mysqli_real_escape_string($db_conn, $_POST['blood_group']);
    $units_needed = intval($_POST['units_needed']);
    $hospital_name = mysqli_real_escape_string($db_conn, $_POST['hospital_name']);
    $location = mysqli_real_escape_string($db_conn, $_POST['location']);
    $urgency_level = mysqli_real_escape_string($db_conn, $_POST['urgency_level']);
    $requested_by = mysqli_real_escape_string($db_conn, $_POST['requested_by']);
    $contact_phone = mysqli_real_escape_string($db_conn, $_POST['contact_phone']);

    // Check if table exists, if not create dynamically for smooth experience
    $create_table = "CREATE TABLE IF NOT EXISTS `blood_requests` (
      `request_id` int(11) NOT NULL AUTO_INCREMENT,
      `patient_name` varchar(100) NOT NULL,
      `blood_group` varchar(10) NOT NULL,
      `units_needed` int(11) NOT NULL DEFAULT 1,
      `hospital_name` varchar(150) NOT NULL,
      `location` varchar(255) NOT NULL,
      `urgency_level` enum('Normal','Urgent','Critical Emergency') NOT NULL DEFAULT 'Urgent',
      `status` enum('Pending','Approved','Fulfilled','Cancelled') NOT NULL DEFAULT 'Pending',
      `requested_by` varchar(100) DEFAULT NULL,
      `contact_phone` varchar(20) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($db_conn, $create_table);

    $insert_sql = "INSERT INTO `blood_requests` (`patient_name`, `blood_group`, `units_needed`, `hospital_name`, `location`, `urgency_level`, `status`, `requested_by`, `contact_phone`) 
                   VALUES ('$patient_name', '$blood_group', '$units_needed', '$hospital_name', '$location', '$urgency_level', 'Pending', '$requested_by', '$contact_phone')";

    if (mysqli_query($db_conn, $insert_sql)) {
        $msg = "Blood Request created successfully!";
    } else {
        $error = "Failed to create blood request: " . mysqli_error($db_conn);
    }
}

// Handle Status Updates
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $req_id = intval($_GET['id']);
    $st = mysqli_real_escape_string($db_conn, $_GET['status']);
    mysqli_query($db_conn, "UPDATE `blood_requests` SET `status`='$st' WHERE `request_id`=$req_id");
    header("Location: blood_requests.php");
    exit();
}

// Read filter inputs
$blood_group_filter = isset($_GET['blood_group']) ? $_GET['blood_group'] : '';
$urgency_filter = isset($_GET['urgency']) ? $_GET['urgency'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build Query
$conds = ["1=1"];
if ($blood_group_filter)
    $conds[] = "blood_group = '" . mysqli_real_escape_string($db_conn, $blood_group_filter) . "'";
if ($urgency_filter)
    $conds[] = "urgency_level = '" . mysqli_real_escape_string($db_conn, $urgency_filter) . "'";
if ($status_filter)
    $conds[] = "status = '" . mysqli_real_escape_string($db_conn, $status_filter) . "'";

$where_clause = implode(' AND ', $conds);

// Query blood requests (Fallback if table not imported yet)
$check_tbl = mysqli_query($db_conn, "SHOW TABLES LIKE 'blood_requests'");
if (mysqli_num_rows($check_tbl) > 0) {
    $requests_res = mysqli_query($db_conn, "SELECT * FROM `blood_requests` WHERE $where_clause ORDER BY `created_at` DESC");
} else {
    $requests_res = false;
}
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
    <title>Blood Requests | BloodLink Management System</title>
</head>

<body>
    <?php include 'inc/navbar.php'; ?>

    <div class="container py-5 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
            <div>
                <h3 class="mb-0 text-danger fw-bold">🩸 Emergency &amp; Standard Blood Requests</h3>
                <p class="text-muted small mb-0">Manage hospital requests, donor matching, and urgency levels.</p>
            </div>
            <div>
                <button class="btn btn-danger btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addRequestModal">+
                    Create Blood Request</button>
                <a href="Dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
            </div>
        </div>

        <?php if (isset($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body bg-light rounded">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Blood Group</label>
                        <select name="blood_group" class="form-select form-select-sm">
                            <option value="">All Blood Groups</option>
                            <option value="A+" <?php if ($blood_group_filter === 'A+')
                                echo 'selected'; ?>>A+</option>
                            <option value="A-" <?php if ($blood_group_filter === 'A-')
                                echo 'selected'; ?>>A-</option>
                            <option value="B+" <?php if ($blood_group_filter === 'B+')
                                echo 'selected'; ?>>B+</option>
                            <option value="B-" <?php if ($blood_group_filter === 'B-')
                                echo 'selected'; ?>>B-</option>
                            <option value="O+" <?php if ($blood_group_filter === 'O+')
                                echo 'selected'; ?>>O+</option>
                            <option value="O-" <?php if ($blood_group_filter === 'O-')
                                echo 'selected'; ?>>O-</option>
                            <option value="AB+" <?php if ($blood_group_filter === 'AB+')
                                echo 'selected'; ?>>AB+</option>
                            <option value="AB-" <?php if ($blood_group_filter === 'AB-')
                                echo 'selected'; ?>>AB-</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Urgency Level</label>
                        <select name="urgency" class="form-select form-select-sm">
                            <option value="">All Urgency Levels</option>
                            <option value="Normal" <?php if ($urgency_filter === 'Normal')
                                echo 'selected'; ?>>Normal
                            </option>
                            <option value="Urgent" <?php if ($urgency_filter === 'Urgent')
                                echo 'selected'; ?>>Urgent
                            </option>
                            <option value="Critical Emergency" <?php if ($urgency_filter === 'Critical Emergency')
                                echo 'selected'; ?>>Critical Emergency</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fulfillment Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php if ($status_filter === 'Pending')
                                echo 'selected'; ?>>Pending
                            </option>
                            <option value="Approved" <?php if ($status_filter === 'Approved')
                                echo 'selected'; ?>>Approved
                            </option>
                            <option value="Fulfilled" <?php if ($status_filter === 'Fulfilled')
                                echo 'selected'; ?>>
                                Fulfilled</option>
                            <option value="Cancelled" <?php if ($status_filter === 'Cancelled')
                                echo 'selected'; ?>>
                                Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-sm w-100">Filter</button>
                        <a href="blood_requests.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Blood Requests Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <?php if (!$requests_res || mysqli_num_rows($requests_res) === 0): ?>
                    <div class="text-center py-5">
                        <h5 class="text-muted">No blood requests found.</h5>
                        <p class="small text-muted">Click "+ Create Blood Request" to log a new hospital request.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Patient Name</th>
                                    <th>Blood Group</th>
                                    <th>Units</th>
                                    <th>Hospital &amp; Location</th>
                                    <th>Urgency</th>
                                    <th>Requested By</th>
                                    <th>Status</th>
                                    <th>Date Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1;
                                while ($row = mysqli_fetch_assoc($requests_res)): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-danger fs-6"><?php echo htmlspecialchars($row['blood_group']); ?></span>
                                        </td>
                                        <td><span class="fw-bold"><?php echo $row['units_needed']; ?></span> Unit(s)</td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['hospital_name']); ?>
                                            </div>
                                            <small class="text-muted">📍
                                                <?php echo htmlspecialchars($row['location']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($row['urgency_level'] === 'Critical Emergency'): ?>
                                                <span class="badge bg-danger text-uppercase">🚨 Critical Emergency</span>
                                            <?php elseif ($row['urgency_level'] === 'Urgent'): ?>
                                                <span class="badge bg-warning text-dark">⚠️ Urgent</span>
                                            <?php else: ?>
                                                <span class="badge bg-info text-dark">Normal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['requested_by'] ?: 'System Admin'); ?></div>
                                            <small class="text-muted">📞
                                                <?php echo htmlspecialchars($row['contact_phone'] ?: 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'Fulfilled'): ?>
                                                <span class="badge bg-success">Fulfilled</span>
                                            <?php elseif ($row['status'] === 'Approved'): ?>
                                                <span class="badge bg-primary">Approved</span>
                                            <?php elseif ($row['status'] === 'Cancelled'): ?>
                                                <span class="badge bg-secondary">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown">
                                                    Update Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item"
                                                            href="?action=update_status&id=<?php echo $row['request_id']; ?>&status=Approved">Approve
                                                            Request</a></li>
                                                    <li><a class="dropdown-item"
                                                            href="?action=update_status&id=<?php echo $row['request_id']; ?>&status=Fulfilled">Mark
                                                            Fulfilled</a></li>
                                                    <li><a class="dropdown-item text-danger"
                                                            href="?action=update_status&id=<?php echo $row['request_id']; ?>&status=Cancelled">Cancel
                                                            Request</a></li>
                                                </ul>
                                            </div>
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

    <!-- Modal: Create Blood Request -->
    <div class="modal fade" id="addRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Create New Blood Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Patient Name</label>
                            <input type="text" name="patient_name" class="form-control"
                                placeholder="Full name of patient" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Blood Group Needed</label>
                                <select name="blood_group" class="form-select" required>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Units Needed</label>
                                <input type="number" name="units_needed" class="form-control" value="1" min="1" max="20"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Hospital Name</label>
                            <input type="text" name="hospital_name" class="form-control"
                                placeholder="e.g. Aminu Kano Teaching Hospital" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Hospital Location / City</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Zaria Road, Kano"
                                required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Urgency Level</label>
                                <select name="urgency_level" class="form-select" required>
                                    <option value="Normal">Normal</option>
                                    <option value="Urgent">Urgent</option>
                                    <option value="Critical Emergency">Critical Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Contact Phone</label>
                                <input type="text" name="contact_phone" class="form-control"
                                    placeholder="Doctor/Hospital phone" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Requested By (Doctor/Officer)</label>
                            <input type="text" name="requested_by" class="form-control" placeholder="e.g. Dr. Aliyu">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="btn_add_request" class="btn btn-danger">Create Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'inc/main_js.php'; ?>
</body>

</html>