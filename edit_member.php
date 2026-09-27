<?php
include 'inc/header.php';
include 'config/db.php';

// Admin-only access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$dbb = new operations();

$member = null;
if (isset($_GET['id'])) {
    $member_id = intval($_GET['id']);
    $member = $dbb->get_donor_by_id($member_id);
}

// If the form is submitted, update the member record
if (isset($_POST['btn_update_member'])) {
    bl_csrf_check();      // BL-14
    $dbb->update_donor($member_id);
}
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Edit Member | BloodLink Admin</title>
</head>
<body>
    <main class="main" id="top">
      <?php include 'inc/navbar.php'; ?><br><br>

      <section class="py-5">
        <div class="container bg-light p-4 rounded shadow-sm" style="max-width: 650px;">
          <h3 class="mb-4 text-danger fw-bold">🩸 Edit Member Details</h3>
          <?php $dbb->display_message() ?>

          <?php if (empty($member)): ?>
              <div class="alert alert-warning text-center">Member record not found.</div>
              <div class="text-center">
                  <a href="manage_members.php" class="btn btn-secondary btn-sm">Back to Manage Members</a>
              </div>
          <?php else: ?>
              <form action="" method="POST">
                  <?php bl_csrf_field(); // BL-14 ?>
                <div class="mb-3">
                  <label for="name" class="form-label fw-bold">Full Name</label>
                  <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label fw-bold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label fw-bold">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="member_code" class="form-label fw-bold">Member ID</label>
                    <input type="text" class="form-control" id="member_code" name="member_code" value="<?php echo htmlspecialchars($member['member_code']); ?>" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="blood_group" class="form-label fw-bold">Blood Group</label>
                    <select class="form-control" id="blood_group" name="blood_group" required>
                        <option value="">Select Blood Group</option>
                        <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $g): ?>
                          <option value="<?= $g ?>" <?php if (($member['blood_group'] ?? '') === $g) echo 'selected'; ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="address" class="form-label fw-bold">Address / Location <small class="text-muted">(leave blank to keep current)</small></label>
                  <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($member['address'] ?? ''); ?>" placeholder="Street, LGA, State">
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="btn_update_member" class="btn btn-danger px-4">Update Member</button>
                    <a href="manage_members.php" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
              </form>
          <?php endif; ?>
        </div>
      </section>

    </main>

    <?php include 'inc/main_js.php'; ?>
</body>
</html>
