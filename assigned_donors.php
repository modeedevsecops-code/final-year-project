<?php
require_once 'config/db.php';

// Admins and officers both reach this page from their sidebars. Officers see
// their own assigned donors; admins see every assignment. Was supervisor-only,
// which bounced admins to the login form.
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supervisor'], true)) {
  header('Location: login.php');
  exit;
}

$dbb = new operations();
$is_admin = ($_SESSION['role'] === 'admin');

if ($is_admin) {
    $students = $dbb->get_all_assignments();          // every donor↔officer assignment
    $backLink = 'dashboard.php';
} else {
    $supervisor_id = intval($_SESSION['supervisor_id']);
    $students = $dbb->get_assigned_students($supervisor_id);
    $backLink = 'officer_dashboard.php';
}
require_once 'inc/header.php';
?>

<!DOCTYPE html>
<html lang="en-US">

<body>
  <?php include 'inc/navbar.php'; ?>
  <div class="container py-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0"><?php echo $is_admin ? 'All Assigned Donors' : 'My Assigned Donors'; ?></h3>
      <a href="<?php echo $backLink; ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card">
      <div class="card-body">
        <?php if (empty($students)): ?>
          <div class="alert alert-info">No donors assigned to you yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Donor Name</th>
                  <th>Donor ID</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Blood Type</th>
                  <?php if ($is_admin): ?><th>Assigned Officer</th><?php endif; ?>
                  <th>Donation Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1;
                foreach ($students as $row): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td>
                      <span class="badge bg-danger">
                        <?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?>
                      </span>
                    </td>
                    <?php if ($is_admin): ?>
                      <td><?php echo htmlspecialchars($row['officer_name'] ?? '—'); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td>
                      <?php if (!$is_admin): ?>
                        <a href="officer_chat.php?student_id=<?php echo urlencode($row['student_id']); ?>"
                          class="btn btn-sm btn-success">Chat</a>
                        <a href="officer_comments.php?student_id=<?php echo urlencode($row['student_id']); ?>"
                          class="btn btn-sm btn-danger">Review Donations</a>
                      <?php else: ?>
                        <span style="color:#999; font-size:.85rem;">—</span>
                      <?php endif; ?>
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