<?php
session_start();
require_once 'config/db.php';
require_once 'inc/header.php';

// Redirect non-admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$officer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dbb = new operations();

// Fetch officer details
$officer = null;
if ($officer_id) {
    global $db;
    $res = mysqli_query($db->connection, "SELECT * FROM staff WHERE staff_id = $officer_id LIMIT 1");
    if ($res) {
        $officer = mysqli_fetch_assoc($res);
    }
}

// Fetch assigned donors
$donors = $dbb->get_assigned_students($officer_id);
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>Assigned Donors | BloodLink Admin</title>
</head>
<body>
  <?php include 'inc/navbar.php'; ?>
  <div class="container py-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-1 fw-bold text-danger">🩸 Assigned Donors</h3>
        <p class="text-muted small mb-0">
          Viewing blood donors assigned to officer: <strong><?php echo $officer ? htmlspecialchars($officer['staff_name']) : 'Unknown'; ?></strong>
        </p>
      </div>
      <a href="manage_officers.php" class="btn btn-outline-secondary">Back to Officers</a>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <?php if (empty($donors)): ?>
          <div class="alert alert-info text-center py-4 mb-0">No donors assigned to this hospital officer yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Donor Name</th>
                  <th>Donor ID / Reg No</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Blood Group</th>
                  <th>Hospital Location</th>
                  <th>Donation Schedule</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; foreach ($donors as $row): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><code><?php echo htmlspecialchars($row['reg_no']); ?></code></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td>
                      <span class="badge bg-danger">
                        <?php echo htmlspecialchars($row['year_of_study']); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><small><?php echo htmlspecialchars($row['methodology'] ?? '—'); ?></small></td>
                    <td>
                      <span class="badge bg-<?php echo ($row['status'] === 'Completed') ? 'success' : 'warning'; ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                      </span>
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

  <?php include 'inc/main_js.php'; ?>
</body>
</html>