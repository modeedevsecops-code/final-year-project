<?php
require_once 'config/db.php';
require_once 'inc/header.php';

// Donor-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: user-login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$dbb = new operations();

// Handle donation submission
if (isset($_POST['submit_logbook'])) {
    $date       = $_POST['entry_date'];
    $activities = $_POST['activities'];

    if ($dbb->add_logbook_entry($student_id, $date, $activities)) {
        $success = "Blood donation record submitted successfully.";
    } else {
        $error = "Failed to submit donation record.";
    }
}

// Fetch existing donation records
$entries = $dbb->get_student_logbook($student_id);
?>

<!DOCTYPE html>
<html lang="en-US">
<body>

<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-5">

    <h3 class="mb-4 text-center">Blood Donation Form</h3>
    <p class="text-center text-muted">
      Submit your blood donation record accurately. Your record will be reviewed by your assigned hospital officer.
    </p>

    <!-- Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Donation Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Donation Date</label>
                    <input type="date"
                           name="entry_date"
                           class="form-control"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Donation Details / Health Notes</label>
                    <textarea name="activities"
                              class="form-control"
                              rows="5"
                              placeholder="e.g. Blood type O+, donated 450ml at St. Luke Hospital. No adverse reactions noted..."
                              required></textarea>
                </div>

                <button type="submit"
                        name="submit_logbook"
                        class="btn btn-danger">
                    Submit Donation Record
                </button>

            </form>
        </div>
    </div>

    <!-- Donation History -->
    <h5 class="mb-3">My Donation History</h5>

    <?php if (mysqli_num_rows($entries) === 0): ?>
        <div class="alert alert-info">No donation records submitted yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Donation Details / Health Notes</th>
                        <th>Status</th>
                        <th>Officer Comment</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($entries)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['entry_date']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['activities'])); ?></td>
                        <td>
                            <span class="badge bg-<?php
                                echo $row['status'] === 'approved' ? 'success' :
                                     ($row['status'] === 'rejected' ? 'danger' : 'warning');
                            ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $row['supervisor_comment']
                                ? htmlspecialchars($row['supervisor_comment'])
                                : '—'; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>
