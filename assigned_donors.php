<?php
// session_start();
require_once 'config/db.php';
require_once 'inc/header.php';

// Redirect non-officers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
  header('Location: login.php');
  exit;
}

// Hospital officer id from login flow
$supervisor_id = intval($_SESSION['supervisor_id']);

$dbb = new operations();
$students = $dbb->get_assigned_students($supervisor_id);
?>

<!DOCTYPE html>
<html lang="en-US">

<body>
  <?php include 'inc/navbar.php'; ?>
  <div class="container py-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0">My Assigned Donors</h3>
      <a href="officer_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
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
                        <?php echo htmlspecialchars(isset($row['year_of_study']) ? $row['year_of_study'] : 'N/A'); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td>
                      <a href="officer_chat.php?student_id=<?php echo urlencode($row['student_id']); ?>"
                        class="btn btn-sm btn-success">Chat</a>
                      <a href="officer_comments.php?student_id=<?php echo urlencode($row['student_id']); ?>"
                        class="btn btn-sm btn-danger">Review Donations</a>
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