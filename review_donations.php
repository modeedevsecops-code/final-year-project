<?php
// session_start();
require_once 'config/db.php';
require_once 'inc/header.php';

// Only supervisors / hospital officers allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}

$supervisor_id = intval($_SESSION['supervisor_id']);
$dbb = new operations();

// Handle review form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review_entry') {
    bl_csrf_check();      // BL-14
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $comment = isset($_POST['supervisor_comment']) ? trim($_POST['supervisor_comment']) : '';

    // basic validation
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        $error = "Invalid status selected.";
    } else {
        if ($dbb->review_logbook_entry($log_id, $status, $comment, $supervisor_id)) {
            $success = "Donation record updated successfully.";
        } else {
            $error = "Failed to update record. Try again.";
        }
    }
}

// If student_id is present, show that donor's entries only
$view_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$student_entries = [];
if ($view_student_id) {
    $student_entries = $dbb->get_student_logbook_for_supervisor($supervisor_id, $view_student_id);
}

// Always fetch latest entries across assigned donors for overview
$entries_overview = $dbb->get_supervisor_logbook_entries($supervisor_id);
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>Review Blood Donations | BloodLink Officer</title>
</head>
<body>
<?php include 'inc/navbar.php'; ?><br><br>

<div class="container py-5 mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 text-danger fw-bold">🩸 Review Blood Donations</h3>
        <a href="officer_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- status messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Overview of recent entries -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-danger text-white fw-bold">
            Recent Blood Donation Records (Assigned Donors)
        </div>
        <div class="card-body">
            <?php if (empty($entries_overview)): ?>
                <div class="alert alert-info mb-0">No blood donation records submitted by your assigned donors yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Donor Name &amp; ID</th>
                                <th>Blood Group / Hospital</th>
                                <th>Donation Details / Health Notes</th>
                                <th>Status</th>
                                <th>Officer Comment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; foreach ($entries_overview as $row): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['entry_date']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['student_name']); ?></strong><br />
                                    <small class="badge bg-secondary"><?php echo htmlspecialchars($row['reg_no']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                <td style="max-width:320px; white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($row['activities'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $row['status'] === 'approved' ? 'success' :
                                             ($row['status'] === 'rejected' ? 'danger' : 'warning');
                                    ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td style="max-width:200px; white-space:pre-wrap;"><?php echo $row['supervisor_comment'] ? nl2br(htmlspecialchars($row['supervisor_comment'])) : '—'; ?></td>
                                <td>
                                    <a href="review_donations.php?student_id=<?php echo urlencode($row['student_id']); ?>" class="btn btn-sm btn-primary mb-1">View Donor</a>

                                    <!-- quick action form: approve -->
                                    <form method="post" style="display:inline-block;">
                                        <?php bl_csrf_field(); // BL-14 ?>
                                        <input type="hidden" name="action" value="review_entry">
                                        <input type="hidden" name="log_id" value="<?php echo intval($row['log_id']); ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <input type="hidden" name="supervisor_comment" value="Approved & Verified.">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">Approve</button>
                                    </form>

                                    <!-- quick action: reject -->
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Reject this donation record?');">
                                        <?php bl_csrf_field(); // BL-14 ?>
                                        <input type="hidden" name="action" value="review_entry">
                                        <input type="hidden" name="log_id" value="<?php echo intval($row['log_id']); ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <input type="hidden" name="supervisor_comment" value="Rejected.">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Reject">Reject</button>
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

    <!-- If viewing a specific donor, show detailed entries -->
    <?php if ($view_student_id): ?>
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span>Donor Donation History &amp; Review</span>
                <a href="review_donations.php" class="btn btn-sm btn-outline-light">Back to Overview</a>
            </div>
            <div class="card-body">
                <?php if (empty($student_entries)): ?>
                    <div class="alert alert-info mb-0">No entries found for this donor.</div>
                <?php else: ?>
                    <?php foreach ($student_entries as $entry): ?>
                        <div class="mb-3 border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong class="text-danger fs-5"><?php echo htmlspecialchars($entry['student_name']); ?> (ID: <?php echo htmlspecialchars($entry['reg_no']); ?>)</strong>
                                    <br>
                                    <small class="text-muted">Donation Date: <?php echo htmlspecialchars($entry['entry_date']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php
                                        echo $entry['status'] === 'approved' ? 'success' :
                                             ($entry['status'] === 'rejected' ? 'danger' : 'warning');
                                    ?>"><?php echo ucfirst($entry['status']); ?></span>
                                    <br>
                                    <small class="text-muted">Submitted: <?php echo htmlspecialchars($entry['created_at']); ?></small>
                                </div>
                            </div>

                            <hr>

                            <div style="white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($entry['activities'])); ?></div>

                            <hr>

                            <form method="post" class="mt-2">
                                <?php bl_csrf_field(); // BL-14 ?>
                                <input type="hidden" name="action" value="review_entry">
                                <input type="hidden" name="log_id" value="<?php echo intval($entry['log_id']); ?>">

                                <div class="mb-2">
                                    <label class="form-label fw-bold">Officer Comment / Verification Notes</label>
                                    <textarea name="supervisor_comment" class="form-control" rows="3" placeholder="Add officer verification notes..."><?php echo ($entry['supervisor_comment']); ?></textarea>
                                </div>

                                <div class="mb-2 d-flex gap-2">
                                    <select name="status" class="form-select w-auto">
                                        <option value="pending" <?php echo $entry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $entry['status'] === 'approved' ? 'selected' : ''; ?>>Approve</option>
                                        <option value="rejected" <?php echo $entry['status'] === 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                    </select>

                                    <button type="submit" class="btn btn-danger">Save Review</button>
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
