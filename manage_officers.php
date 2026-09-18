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
$dbb = new operations();

// ─────────────────────────────────────────────────
// Handle: Add Hospital Officer (inline form)
// ─────────────────────────────────────────────────
$form_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_add_officer'])) {
    bl_csrf_check();      // BL-14
    $staff_name = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $position = mysqli_real_escape_string($conn, trim($_POST['position']));
    $hospital = mysqli_real_escape_string($conn, trim($_POST['hospital']));
    // Hash the officer password at creation (BL-12).
    $plain_password = trim($_POST['password']);
    $password = password_hash($plain_password, PASSWORD_DEFAULT);

    if ($staff_name && $email && $phone && $plain_password !== '') {
        // Store hospital in position field (repurposed) or add to staff_name
        $full_position = $position . ($hospital ? ' – ' . $hospital : '');
        $q = "INSERT INTO staff (staff_name, phone, email, position, password)
              VALUES ('$staff_name', '$phone', '$email', '$full_position', '$password')";
        if (mysqli_query($conn, $q)) {
            $form_msg = '<div class="alert alert-success">Hospital Officer added successfully!</div>';
        } else {
            $form_msg = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        $form_msg = '<div class="alert alert-warning">Please fill in all required fields.</div>';
    }
}

// ─────────────────────────────────────────────────
// Handle: Delete officer
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    bl_csrf_check(); // BL-14 — was a CSRF-able GET link
    $del_id = intval($_POST['delete_id']);
    if ($stmt = mysqli_prepare($conn, "DELETE FROM staff WHERE staff_id = ?")) {
        mysqli_stmt_bind_param($stmt, "i", $del_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: manage_officers.php?deleted=1');
    exit;
}

// ─────────────────────────────────────────────────
// Fetch all officers with donor count
// ─────────────────────────────────────────────────
$officers_res = mysqli_query(
    $conn,
    "SELECT s.staff_id, s.staff_name, s.email, s.phone, s.position, s.date_registered,
            COUNT(p.project_id) AS donor_count
     FROM staff s
     LEFT JOIN projects p ON s.staff_id = p.assigned_supervisor
     GROUP BY s.staff_id
     ORDER BY s.staff_name ASC"
);
$officers = [];
if ($officers_res) {
    while ($row = mysqli_fetch_assoc($officers_res)) {
        $officers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Manage Hospital Officers | BloodLink Admin</title>
</head>

<body>
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?>
        <br><br>

        <section class="py-4">
            <div class="container">

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
                    <div>
                        <h3 class="mb-0 fw-bold text-danger">🏥 Manage Hospital Officers</h3>
                        <p class="text-muted small mb-0">Add, view, and manage blood bank hospital officers assigned to
                            donors.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addOfficerModal">
                            + Add Hospital Officer
                        </button>
                        <a href="assign.php" class="btn btn-outline-success btn-sm">Assign Officer to Donor</a>
                        <a href="Dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
                    </div>
                </div>

                <?php if (!empty($form_msg))
                    echo $form_msg; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">Officer deleted successfully.</div>
                <?php endif; ?>

                <!-- Officers Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <?php if (empty($officers)): ?>
                            <div class="text-center py-5">
                                <h5 class="text-muted">No hospital officers found.</h5>
                                <p class="small text-muted">Click "+ Add Hospital Officer" to register one.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Officer Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Role / Hospital</th>
                                            <th>Registered</th>
                                            <th>Donors Assigned</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        foreach ($officers as $officer): ?>
                                            <tr>
                                                <td><?php echo $i++; ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($officer['staff_name']); ?></td>
                                                <td><?php echo htmlspecialchars($officer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($officer['phone']); ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($officer['position'] ?: 'Hospital Officer'); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d M Y', strtotime($officer['date_registered'])); ?></small>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $officer['donor_count'] > 0 ? 'success' : 'secondary'; ?>">
                                                        <?php echo $officer['donor_count']; ?> Donor(s)
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="officer_donors.php?id=<?php echo $officer['staff_id']; ?>"
                                                        class="btn btn-sm btn-outline-primary">View Donors</a>
                                                    <form method="POST" style="display:inline;"
                                                          onsubmit="return confirm('Delete this hospital officer? This cannot be undone.')">
                                                        <?php bl_csrf_field(); // BL-14 ?>
                                                        <input type="hidden" name="delete_id" value="<?php echo (int)$officer['staff_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </section>

        <!-- Modal: Add Hospital Officer -->
        <div class="modal fade" id="addOfficerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Add New Hospital Officer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <?php bl_csrf_field(); // BL-14 ?>
                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="staff_name" class="form-control"
                                    placeholder="e.g. Dr. Aliyu Bello" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="officer@hospital.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" placeholder="08012345678"
                                        required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Role / Position</label>
                                <input type="text" name="position" class="form-control"
                                    placeholder="e.g. Chief Medical Officer, Blood Bank Director">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Hospital / Blood Bank Name</label>
                                <input type="text" name="hospital" class="form-control"
                                    placeholder="e.g. Aminu Kano Teaching Hospital">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Login Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Create a login password for this officer" required>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="btn_add_officer" class="btn btn-danger">Add Hospital
                                Officer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>
    <?php include 'inc/main_js.php'; ?>
</body>

</html>