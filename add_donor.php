<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Include necessary files and database connection
include 'inc/header.php';
include 'config/db.php';

// Admin-only access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$dbb = new operations();
$dbb->add_student(); // Call the function to handle donor addition
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Add Blood Donor | BloodLink Admin</title>
</head>
<body>
    <!-- Main Content -->
    <main class="main" id="top">
        <?php include 'inc/navbar.php'; ?><br><br>

        <section class="py-5">
            <div class="container bg-light p-4 rounded shadow-sm" style="max-width: 650px;">
                <h3 class="mb-4 text-danger fw-bold">🩸 Add New Blood Donor</h3>
                <?php $dbb->display_message() ?>
                
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Donor Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label fw-bold">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. 08012345678" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reg_no" class="form-label fw-bold">Donor ID / Reg No</label>
                            <input type="text" class="form-control" id="reg_no" name="reg_no" placeholder="e.g. BL-2026-001" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year_of_study" class="form-label fw-bold">Blood Group</label>
                            <select class="form-control" id="year_of_study" name="year_of_study" required>
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                         <label for="address" class="form-label fw-bold">Address / Area</label>
                         <input type="text" class="form-control" id="address" name="address" placeholder="e.g. Sabon Gari, Kaduna" required>
                   </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-bold">Portal Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create portal access password" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="btn_add_student" class="btn btn-danger px-4">Add Blood Donor</button>
                        <a href="manage_donors.php" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </section>

    </main>

    <!-- JavaScripts -->
    <?php include 'inc/main_js.php'; ?>
</body>
</html>
