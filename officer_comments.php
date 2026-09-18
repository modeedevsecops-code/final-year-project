<?php
require_once 'config/db.php';
require_once 'inc/header.php';

// Only hospital officers (supervisors) allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
  header('Location: login.php');
  exit;
}

$supervisor_id = intval($_SESSION['supervisor_id']);
$dbb = new operations();

$success = $error = '';

// Handle POST actions: review donation entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  bl_csrf_check();      // BL-14
  if (isset($_POST['action']) && $_POST['action'] === 'review_entry') {
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $comment = isset($_POST['supervisor_comment']) ? trim($_POST['supervisor_comment']) : '';

    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
      $error = "Invalid status for donation record.";
    } else {
      if ($dbb->review_logbook_entry($log_id, $status, $comment, $supervisor_id)) {
        $success = "Donation review status updated.";
      } else {
        $error = "Failed to update donation review status.";
      }
    }
  }
}

// Optional: view specific donor if provided
$view_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

// Fetch donation overviews
$entries_overview = $dbb->get_supervisor_logbook_entries($supervisor_id);

// If viewing a specific donor, fetch their records
$student_entries = $view_student_id ? $dbb->get_student_logbook_for_supervisor($supervisor_id, $view_student_id) : [];
?>

<!DOCTYPE html>
<html lang="en-US">

<head>
  <title>Donation Reviews &amp; Comments | BloodLink</title>
</head>

<body>
  <?php include 'inc/navbar.php'; ?>

  <div class="container py-5 mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-0 fw-bold text-danger">💬 Donation Reviews &amp; Comments</h3>
        <p class="text-muted small mb-0">Manage reviews and input feedback comments for blood donations submitted by
          assigned donors.</p>
      </div>
      <a href="officer_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-header bg-danger text-white fw-bold">
        Recent Donation Submissions
      </div>
      <div class="card-body">
        <?php if (empty($entries_overview)): ?>
          <div class="alert alert-info text-center py-4 mb-0">No blood donation records submitted by your assigned donors
            yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Donor Name</th>
                  <th>Hospital Location</th>
                  <th>Donation Details / Notes</th>
                  <th>Status</th>
                  <th>Officer Comment</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1;
                foreach ($entries_overview as $row): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['entry_date']); ?></td>
                    <td class="fw-bold">
                      <?php echo htmlspecialchars($row['student_name']); ?><br>
                      <small class="text-muted">ID: <?php echo htmlspecialchars($row['reg_no']); ?></small>
                    </td>
                    <td><small><?php echo htmlspecialchars($row['project_title']); ?></small></td>
                    <td style="max-width:320px; white-space:pre-wrap;">
                      <?php echo nl2br(htmlspecialchars($row['activities'])); ?></td>
                    <td>
                      <span class="badge bg-<?php
                      echo $row['status'] === 'approved' ? 'success' :
                        ($row['status'] === 'rejected' ? 'danger' : 'warning');
                      ?>">
                        <?php echo ucfirst($row['status']); ?>
                      </span>
                    </td>
                    <td style="max-width:200px; white-space:pre-wrap;">
                      <?php echo $row['supervisor_comment'] ? nl2br(htmlspecialchars($row['supervisor_comment'])) : '—'; ?>
                    </td>
                    <td>
                      <div class="d-flex flex-column gap-1">
                        <a href="officer_comments.php?student_id=<?php echo urlencode($row['student_id']); ?>"
                          class="btn btn-sm btn-outline-primary">Write Comment</a>

                        <form method="post" class="d-inline">
                          <?php bl_csrf_field(); // BL-14 ?>
                          <input type="hidden" name="action" value="review_entry">
                          <input type="hidden" name="log_id" value="<?php echo intval($row['log_id']); ?>">
                          <input type="hidden" name="status" value="approved">
                          <input type="hidden" name="supervisor_comment" value="Approved. Healthy donation details.">
                          <button type="submit" class="btn btn-sm btn-success w-100">Approve</button>
                        </form>

                        <form method="post" class="d-inline" onsubmit="return confirm('Reject this donation record?');">
                          <?php bl_csrf_field(); // BL-14 ?>
                          <input type="hidden" name="action" value="review_entry">
                          <input type="hidden" name="log_id" value="<?php echo intval($row['log_id']); ?>">
                          <input type="hidden" name="status" value="rejected">
                          <input type="hidden" name="supervisor_comment" value="Incomplete details.">
                          <button type="submit" class="btn btn-sm btn-danger w-100">Reject</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- If viewing specific student show detailed submissions and feedback editor -->
    <?php if ($view_student_id): ?>
      <div class="card mb-4 border-0 shadow">
        <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
          <span>Donation Feedback Detail Editor</span>
          <a href="officer_comments.php" class="btn btn-sm btn-outline-light">Back to Overview</a>
        </div>
        <div class="card-body">
          <?php if (empty($student_entries)): ?>
            <div class="alert alert-info mb-0">No donation records found for this donor.</div>
          <?php else: ?>
            <?php foreach ($student_entries as $entry): ?>
              <div class="mb-4 border rounded p-3 bg-light">
                <div class="row g-2 justify-content-between mb-3">
                  <div class="col-md-8">
                    <h6 class="fw-bold mb-0 text-danger"><?php echo htmlspecialchars($entry['student_name']); ?></h6>
                    <small class="text-muted">Donor ID: <?php echo htmlspecialchars($entry['reg_no']); ?> | Donation Date:
                      <?php echo htmlspecialchars($entry['entry_date']); ?></small>
                  </div>
                  <div class="col-md-4 text-md-end">
                    <span class="badge bg-<?php
                    echo $entry['status'] === 'approved' ? 'success' :
                      ($entry['status'] === 'rejected' ? 'danger' : 'warning');
                    ?>"><?php echo ucfirst($entry['status']); ?></span><br>
                    <small class="text-muted text-xs">Submitted:
                      <?php echo date('d M Y, H:i', strtotime($entry['created_at'])); ?></small>
                  </div>
                </div>

                <div class="p-3 bg-white border rounded mb-3" style="white-space:pre-wrap;">
                  <?php echo nl2br(htmlspecialchars($entry['activities'])); ?></div>

                <form method="post">
                  <?php bl_csrf_field(); // BL-14 ?>
                  <input type="hidden" name="action" value="review_entry">
                  <input type="hidden" name="log_id" value="<?php echo intval($entry['log_id']); ?>">

                  <div class="mb-3">
                    <label class="form-label fw-bold">Feedback / Comment</label>
                    <textarea name="supervisor_comment" class="form-control" rows="3"
                      placeholder="Add details or comments here..."><?php echo htmlspecialchars($entry['supervisor_comment'] ?? ''); ?></textarea>
                  </div>

                  <div class="d-flex gap-2">
                    <select name="status" class="form-select w-auto">
                      <option value="pending" <?php echo $entry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                      <option value="approved" <?php echo $entry['status'] === 'approved' ? 'selected' : ''; ?>>Approve</option>
                      <option value="rejected" <?php echo $entry['status'] === 'rejected' ? 'selected' : ''; ?>>Reject</option>
                    </select>

                    <button type="submit" class="btn btn-danger">Save Feedback</button>
                  </div>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <?php include 'inc/main_js.php'; ?>
</body>

</html>