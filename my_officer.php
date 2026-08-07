<?php
require_once 'config/db.php';
require_once 'inc/header.php';

// Only donors can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$dbb = new operations();

// Fetch assigned hospital officer info
$supervisor = $dbb->get_student_supervisor($student_id);
?>

<!DOCTYPE html>
<html lang="en-US">
<body>
<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-5">
    <h3 class="mb-4 text-center">My Hospital Officer</h3>

    <?php if (!$supervisor): ?>
        <div class="alert alert-info text-center">No hospital officer has been assigned to you yet. Please contact the blood bank administrator.</div>
    <?php else: ?>
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($supervisor['staff_name']); ?></h5>
                <p class="card-text"><strong>Role / Position:</strong> <?php echo htmlspecialchars($supervisor['position']); ?></p>
                <p class="card-text"><strong>Email:</strong> <?php echo htmlspecialchars($supervisor['email']); ?></p>
                <p class="card-text"><strong>Phone:</strong> <?php echo htmlspecialchars($supervisor['phone']); ?></p>
                <p class="card-text"><strong>Hospital / Blood Bank:</strong> <?php echo htmlspecialchars($supervisor['project_title']); ?></p>
                <a href="donor_chat.php" class="btn btn-danger mt-2">Chat with Officer</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>
