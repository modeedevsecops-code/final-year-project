<?php
session_start();
require_once 'config/db.php';
require_once 'inc/header.php';

// Allow both admin AND hospital officers (supervisors) to post emergency alerts
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['role'] === 'admin');

// Determine the poster ID:
// Admin uses user_id from login table session; officer uses supervisor_id
if ($is_admin) {
    $poster_id = isset($_SESSION['user']) ? intval($_SESSION['user']) : 1; // admin user_id
} else {
    $poster_id = isset($_SESSION['supervisor_id']) ? intval($_SESSION['supervisor_id']) : 0;
}

$dbb = new operations();

// Handle form submission
$post_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_post_notice'])) {
    $title   = htmlspecialchars(trim($_POST['title']));
    $message = htmlspecialchars(trim($_POST['message']));

    if (!empty($title) && !empty($message)) {
        $dbb->post_notice($poster_id, $title, $message);
        $post_success = "Emergency alert posted successfully!";
    }
}

// Admin sees ALL alerts; officer sees only their own alerts
if ($is_admin) {
    // Fetch all notices across all officers
    global $db;
    $result = mysqli_query($db->connection,
        "SELECT n.*, COALESCE(s.staff_name, 'BloodLink Admin') AS posted_by
         FROM notices n
         LEFT JOIN staff s ON s.staff_id = n.supervisor_id
         ORDER BY n.created_at DESC"
    );
    $notices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = $row;
    }
} else {
    $notices = $dbb->get_supervisor_notices($poster_id);
    // Add a "posted_by" key for uniform rendering
    foreach ($notices as &$n) {
        $n['posted_by'] = 'You';
    }
    unset($n);
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>Emergency Alerts | BloodLink Blood Bank</title>
</head>
<body>
<?php include 'inc/navbar.php'; ?>

<div class="main-content">
<div class="container py-5">
    <h3 class="mb-2 text-center fw-bold">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#cc0000" class="me-2" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
        </svg>
        Emergency Blood Alert
    </h3>
    <p class="text-center text-muted mb-4">
        <?php echo $is_admin
            ? "As system admin, you can view and post emergency blood request alerts visible to all registered donors."
            : "Post an emergency blood request alert that will be visible to all registered donors.";
        ?>
    </p>

    <?php if (!empty($post_success)): ?>
        <div class="alert alert-success text-center"><?php echo $post_success; ?> ✅</div>
    <?php endif; ?>

    <!-- Post Alert Form -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white fw-bold">Post New Emergency Alert</div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Alert Title</label>
                    <input type="text" name="title" id="title" class="form-control"
                           placeholder="e.g. URGENT: O- Blood Needed at AKTH" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Alert Message</label>
                    <textarea name="message" id="message" class="form-control" rows="4"
                              placeholder="Describe the urgency, blood type needed, units required, contact details..." required></textarea>
                </div>
                <div class="text-center">
                    <button type="submit" name="btn_post_notice" class="btn btn-danger px-5">
                        Post Emergency Alert
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alerts Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3 text-danger fw-bold">
                <?php echo $is_admin ? "All Emergency Alerts (System-Wide)" : "My Emergency Alerts"; ?>
            </h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-danger">
                        <tr>
                            <th>#</th>
                            <?php if ($is_admin): ?><th>Posted By</th><?php endif; ?>
                            <th>Alert Title</th>
                            <th>Message</th>
                            <th>Date Posted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($notices)): $i = 1; ?>
                            <?php foreach ($notices as $notice): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <?php if ($is_admin): ?>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($notice['posted_by']); ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="badge bg-danger me-1">URGENT</span>
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($notice['message']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($notice['created_at'])); ?></td>
                                    <td>
                                        <a href="delete_notices.php?id=<?php echo $notice['notice_id']; ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this emergency alert?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $is_admin ? 6 : 5; ?>" class="text-center text-muted py-4">
                                    No emergency alerts posted yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>