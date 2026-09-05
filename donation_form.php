<?php
require_once 'config/db.php';
require_once 'inc/header.php';

// Donor-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: user-login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$ops = new operations();

// Reuse the real eligibility logic already used by match_donor.php
$check = mysqli_query($db->connection, "SELECT last_donation_date FROM students WHERE student_id = '" . intval($student_id) . "'");
$donor_row = $check ? mysqli_fetch_assoc($check) : null;

$is_eligible = $donor_row ? $ops->is_donor_eligible($donor_row['last_donation_date']) : false;
$days_remaining = $donor_row ? $ops->days_until_eligible($donor_row['last_donation_date']) : 0;
?>
<!DOCTYPE html>
<html lang="en-US">
<body>

<?php include 'inc/navbar.php'; ?>

<div class="container py-5 mt-5">

    <h3 class="mb-4 text-center">Donate Blood</h3>

    <div class="card mb-4">
        <div class="card-body text-center">

            <?php if ($is_eligible): ?>
                <div class="alert alert-success">
                    <h4 class="mb-1">You are currently eligible to donate</h4>
                    <p class="mb-0">A hospital officer will match you to a compatible blood request and confirm the donation when one comes in.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h4 class="mb-1">Not yet eligible</h4>
                    <p class="mb-0">Based on your last donation, you'll be eligible again in <strong><?php echo (int)$days_remaining; ?> day(s)</strong>. This follows the standard 56-day donation interval.</p>
                </div>
            <?php endif; ?>

            <p class="text-muted mt-3">
                You don't need to do anything else right now &mdash; donation records are created and verified
                by your assigned hospital officer once you're matched to a request. You can check your
                donation history any time from your <a href="donor_dashboard.php">Dashboard</a>.
            </p>

        </div>
    </div>

</div>

<?php include 'inc/main_js.php'; ?>
</body>
</html>