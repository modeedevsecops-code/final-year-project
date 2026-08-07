<?php
session_start();
require_once 'config/db.php';
require_once 'inc/header.php';

// Only donors (students) can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: user-login.php");
    exit;
}

$student_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$dbb = new operations();

// Fetch ALL notices (not just from assigned officer) so donor sees every emergency alert
global $db;
$all_notices_res = mysqli_query($db->connection,
    "SELECT n.*, COALESCE(s.staff_name, 'BloodLink Admin') AS officer_name
     FROM notices n
     LEFT JOIN staff s ON s.staff_id = n.supervisor_id
     ORDER BY n.created_at DESC"
);
$notices = [];
if ($all_notices_res) {
    while ($row = mysqli_fetch_assoc($all_notices_res)) {
        $notices[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>Emergency Blood Alerts | BloodLink</title>
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
        Urgent blood requests posted by hospital officers and the BloodLink admin. Please respond if you are eligible.
    </p>

    <?php if (empty($notices)): ?>
        <div class="alert alert-info text-center">
            No emergency alerts have been posted yet. Check back later.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($notices as $n): ?>
                <div class="col-md-6">
                    <div class="card border-danger shadow-sm h-100">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">
                                🚨 <?php echo htmlspecialchars($n['title']); ?>
                            </span>
                            <small><?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></small>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                        </div>
                        <div class="card-footer text-muted small">
                            Posted by: <strong><?php echo htmlspecialchars($n['officer_name']); ?></strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>
