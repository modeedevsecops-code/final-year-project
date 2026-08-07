<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';
require_once 'inc/header.php';

// Donors only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Fetch all emergency alerts, most recent first
global $db;
$result = mysqli_query($db->connection,
    "SELECT n.*, COALESCE(s.staff_name, 'BloodLink Admin') AS posted_by
     FROM notices n
     LEFT JOIN staff s ON s.staff_id = n.supervisor_id
     ORDER BY n.created_at DESC"
);
$notices = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notices[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>Emergency Alerts | BloodLink Blood Bank</title>
</head>
<body>
<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-5">
    <h3 class="mb-2 text-center fw-bold">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#cc0000" class="me-2" viewBox="0 0 16 16">
            <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
        </svg>
        Emergency Blood Alerts
    </h3>
    <p class="text-center text-muted mb-4">
        Urgent blood requests posted by hospital officers and administrators near you.
    </p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-danger">
                        <tr>
                            <th>#</th>
                            <th>Posted By</th>
                            <th>Alert Title</th>
                            <th>Message</th>
                            <th>Date Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($notices)): $i = 1; ?>
                            <?php foreach ($notices as $notice): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($notice['posted_by']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger me-1">URGENT</span>
                                        <?php echo htmlspecialchars($notice['title']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($notice['message']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($notice['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
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

<?php include 'inc/main_js.php'; ?>
</body>
</html>