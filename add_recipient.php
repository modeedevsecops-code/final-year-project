<?php
// Include necessary files and database connection
include 'inc/header.php';
include 'config/db.php';

// Admin-only access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$dbb = new operations();
$dbb->add_recipient(); // Call the function to handle recipient addition
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Add Blood Recipient | BloodLink Admin</title>
</head>
<body>
    <!-- Main Content -->
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?><br><br>

        <section class="py-5">
            <div class="container bg-light p-4 rounded shadow-sm" style="max-width: 650px;">
                <h3 class="mb-4 text-danger fw-bold">🩸 Add New Blood Recipient</h3>
                <?php echo $dbb->display_message(); ?>

                <form action="add_recipient.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name:</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address:</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number:</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Blood Group Needed:</label>
                        <select name="blood_group" class="form-control" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Units Required:</label>
                        <input type="number" name="units_required" class="form-control" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hospital Name:</label>
                        <input type="text" name="hospital_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason / Medical Notes:</label>
                        <textarea name="reason" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" name="add_recipient_btn" class="btn btn-danger w-100">Save Recipient</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>