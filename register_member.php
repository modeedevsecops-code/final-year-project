<?php
// Fragment included by register.php — never served on its own.
if (basename($_SERVER['SCRIPT_FILENAME']) === 'register_member.php') { header('Location: register.php'); exit; }
include_once 'config/functions.php';
$dbb = new operations();

$msg = '';
$old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_register_member'])) {
  bl_csrf_check();      // BL-14
  global $db;
  $old = $_POST;
  list($f, $errors) = bl_validate_member_input($_POST, $db->connection);
  if ($errors) {
    $msg = bl_errors_html($errors);
  } else {
    list($new_id, $code, $geocoded) = bl_create_member($db->connection, $f);
    bl_notify($db->connection, 'admin', bl_admin_ids($db->connection), 'member_new', 'New member registered',
              $f['name'] . " ($code, " . $f['blood_group'] . ") joined BloodLink.", "edit_member.php?id=$new_id");
    bl_notify($db->connection, 'member', $new_id, 'member_new', 'Welcome to BloodLink',
              'Set your donor availability on your dashboard so officers can match you to emergencies.', 'member_dashboard.php');
    $_SESSION['registered'] = ['code' => $code, 'name' => $f['name'], 'geocoded' => $geocoded || $f['address'] === ''];
    header('Location: register.php?done=1');
    exit;
  }
}
$v = function ($k) use (&$old) { return htmlspecialchars((string)($old[$k] ?? '')); };
?>

<?php $reg = isset($_GET['done']) ? ($_SESSION['registered'] ?? null) : null; unset($_SESSION['registered']); ?>
<?php if ($reg): ?>
<div class="card border-0 shadow-lg">
  <div class="card-body p-4 text-center">
    <div style="font-size:2.4rem; color:#2c8a4a;"><i class="fas fa-circle-check"></i></div>
    <h4 class="fw-bold mb-2">Welcome to BloodLink, <?= htmlspecialchars($reg['name']) ?>!</h4>
    <p class="mb-1">Your Member ID is</p>
    <p style="font-size:1.6rem; font-weight:700; letter-spacing:.06em; color:#7a0000;"><?= htmlspecialchars($reg['code']) ?></p>
    <p class="text-muted">Log in with your email address or this Member ID and the password you chose.</p>
    <?php if (!$reg['geocoded']): ?>
      <div class="alert alert-warning small">We couldn't find your address on the map. Update it under <strong>My Profile</strong> after logging in so you can be matched to nearby emergencies.</div>
    <?php endif; ?>
    <a href="user-login.php?role=member" class="btn btn-danger px-5">Log in now</a>
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-lg">
  <div class="card-header bg-danger text-white py-3">
    <h4 class="mb-0 text-white text-center">🩸 Member Registration</h4>
  </div>
  <div class="card-body p-4 bg-light">
    <p class="text-center text-muted mb-4">
      One account to <strong>donate</strong> and <strong>request</strong> blood. Your coordinates help match you to nearby emergencies.
    </p>

    <?php echo $msg; ?>

    <form action="" method="POST">
      <?php bl_csrf_field(); // BL-14 ?>
      <div class="mb-3">
        <label class="form-label fw-bold">Full Name</label>
        <input type="text" class="form-control" name="name" placeholder="e.g. Aliyu Abubakar" value="<?= $v('name') ?>" required maxlength="100">
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Email Address <span class="text-muted fw-normal">(used to log in)</span></label>
          <input type="email" class="form-control" name="email" placeholder="aliyu@example.com" value="<?= $v('email') ?>" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Phone Number</label>
          <input type="text" class="form-control" name="phone" placeholder="e.g. 08031234567" value="<?= $v('phone') ?>" required pattern="\+?[0-9 \-]{7,20}">
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Select Blood Group</label>
          <select class="form-control form-select" name="blood_group" required>
            <option value="" disabled <?= empty($old['blood_group']) ? 'selected' : '' ?>>-- Choose --</option>
            <option value="A+" <?= ($old['blood_group'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
            <option value="A-" <?= ($old['blood_group'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
            <option value="B+" <?= ($old['blood_group'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
            <option value="B-" <?= ($old['blood_group'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
            <option value="O+" <?= ($old['blood_group'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
            <option value="O-" <?= ($old['blood_group'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
            <option value="AB+" <?= ($old['blood_group'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
            <option value="AB-" <?= ($old['blood_group'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Member ID <span class="text-muted fw-normal">(optional)</span></label>
          <input type="text" class="form-control" name="member_code" placeholder="Auto-generated if left blank" value="<?= $v('member_code') ?>" pattern="[A-Za-z0-9\-]{3,30}">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Address / Location <span class="text-muted fw-normal">(helps match you to nearby emergencies)</span></label>
        <input type="text" class="form-control" name="address" placeholder="e.g. Barnawa, Kaduna" value="<?= $v('address') ?>">
      </div>

      <div class="row">
        <div class="col-md-6 mb-4">
          <label class="form-label fw-bold">Choose Password</label>
          <input type="password" class="form-control" name="password" minlength="8"
            placeholder="At least 8 characters, letters + numbers" autocomplete="new-password" required>
        </div>
        <div class="col-md-6 mb-4">
          <label class="form-label fw-bold">Confirm Password</label>
          <input type="password" class="form-control" name="password_confirm" minlength="8"
            placeholder="Type it again" autocomplete="new-password" required>
        </div>
      </div>

      <button type="submit" name="btn_register_member" class="btn btn-danger w-100 py-2 fw-bold">
        Submit Registration
      </button>

      <div class="text-center mt-3 small">
        Already registered? <a href="user-login.php?role=member" class="text-danger fw-bold">Log in here</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
