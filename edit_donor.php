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

if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $student = $dbb->get_student_by_id($student_id); // Fetch the donor data by ID
}

// If the form is submitted, update the donor record
if (isset($_POST['btn_update_student'])) {
    $dbb->update_student($student_id); // Function to update donor details
}
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Edit Blood Donor | BloodLink Admin</title>
</head>
<body>
    <!-- Main Content -->
    <main class="main" id="top">
      <?php include 'inc/navbar.php'; ?><br><br>

      <section class="py-5">
        <div class="container bg-light p-4 rounded shadow-sm" style="max-width: 650px;">
          <h3 class="mb-4 text-danger fw-bold">🩸 Edit Blood Donor Details</h3>
          <?php $dbb->display_message() ?>

          <?php if (empty($student)): ?>
              <div class="alert alert-warning text-center">Donor record not found.</div>
              <div class="text-center">
                  <a href="manage_donors.php" class="btn btn-secondary btn-sm">Back to Manage Donors</a>
              </div>
          <?php else: ?>
              <form action="" method="POST">
                <div class="mb-3">
                  <label for="name" class="form-label fw-bold">Donor Full Name</label>
                  <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label fw-bold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label fw-bold">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="department" class="form-label fw-bold">Donor ID / Reg No</label>
                    <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($student['reg_no']); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="year_of_study" class="form-label fw-bold">Blood Group</label>
                    <select class="form-control" id="year_of_study" name="year_of_study" required>
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php if ($student['year_of_study'] == 'A+') echo 'selected'; ?>>A+</option>
                        <option value="A-" <?php if ($student['year_of_study'] == 'A-') echo 'selected'; ?>>A-</option>
                        <option value="B+" <?php if ($student['year_of_study'] == 'B+') echo 'selected'; ?>>B+</option>
                        <option value="B-" <?php if ($student['year_of_study'] == 'B-') echo 'selected'; ?>>B-</option>
                        <option value="O+" <?php if ($student['year_of_study'] == 'O+') echo 'selected'; ?>>O+</option>
                        <option value="O-" <?php if ($student['year_of_study'] == 'O-') echo 'selected'; ?>>O-</option>
                        <option value="AB+" <?php if ($student['year_of_study'] == 'AB+') echo 'selected'; ?>>AB+</option>
                        <option value="AB-" <?php if ($student['year_of_study'] == 'AB-') echo 'selected'; ?>>AB-</option>
                    </select>
                  </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="btn_update_student" class="btn btn-danger px-4">Update Donor</button>
                    <a href="manage_donors.php" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
              </form>
          <?php endif; ?>
        </div>
      </section>

    </main>

    <!-- JavaScripts -->
    <?php include 'inc/main_js.php'; ?>
</body>
</html>
