<?php
include_once 'config/functions.php';
$dbb = new operations();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_register_member'])) {
  bl_csrf_check();      // BL-14
  global $db;
  $conn = $db->connection;

  $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
  $email       = mysqli_real_escape_string($conn, trim($_POST['email']));
  $phone       = mysqli_real_escape_string($conn, trim($_POST['phone']));
  $blood_group = mysqli_real_escape_string($conn, trim($_POST['blood_group']));
  $address_raw = trim($_POST['address'] ?? '');
  $address     = mysqli_real_escape_string($conn, $address_raw);
  $password    = trim($_POST['password']);

  // A member logs in by email, so the member_code is just a human-facing ID —
  // auto-generate one if the person didn't choose it.
  $member_code = mysqli_real_escape_string($conn, trim($_POST['member_code'] ?? ''));
  if ($member_code === '') {
    $member_code = 'BL-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
  }

  $dup = mysqli_query($conn, "SELECT member_id FROM members WHERE email='$email' LIMIT 1");
  if ($dup && mysqli_num_rows($dup) > 0) {
    $msg = '<div class="alert alert-danger text-center">Registration failed. Email is already registered!</div>';
  } else {
    // Geocode the address once via Nominatim so the member appears on the map.
    list($lat, $lng) = bl_geocode($address_raw);
    $lat_sql = ($lat !== null) ? "'" . floatval($lat) . "'" : 'NULL';
    $lng_sql = ($lng !== null) ? "'" . floatval($lng) . "'" : 'NULL';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $q = "INSERT INTO members (name, email, phone, member_code, blood_group, address, latitude, longitude, password)
              VALUES ('$name', '$email', '$phone', '$member_code', '$blood_group', '$address', $lat_sql, $lng_sql, '$hash')";
    if (mysqli_query($conn, $q)) {
      $msg = '<div class="alert alert-success text-center">Registration successful! Your Member ID is <strong>' . htmlspecialchars($member_code) . '</strong>. You can now <a href="user-login.php">login</a> with your email.</div>';
    } else {
      $msg = '<div class="alert alert-danger text-center">Registration error: ' . mysqli_error($conn) . '</div>';
    }
  }
}
?>

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
        <input type="text" class="form-control" name="name" placeholder="e.g. Aliyu Abubakar" required>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Email Address <span class="text-muted fw-normal">(used to log in)</span></label>
          <input type="email" class="form-control" name="email" placeholder="aliyu@example.com" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Phone Number</label>
          <input type="text" class="form-control" name="phone" placeholder="e.g. 08031234567" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Select Blood Group</label>
          <select class="form-control form-select" name="blood_group" required>
            <option value="" disabled selected>-- Choose --</option>
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
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Member ID <span class="text-muted fw-normal">(optional)</span></label>
          <input type="text" class="form-control" name="member_code" placeholder="Auto-generated if left blank">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Address / Location <span class="text-muted fw-normal">(helps match you to nearby emergencies)</span></label>
        <input type="text" class="form-control" name="address" placeholder="e.g. Barnawa, Kaduna">
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Choose Portal Password</label>
        <input type="password" class="form-control" name="password"
          placeholder="Create a password for logging in" required>
      </div>

      <button type="submit" name="btn_register_member" class="btn btn-danger w-100 py-2 fw-bold">
        Submit Registration
      </button>

      <div class="text-center mt-3 small">
        Already registered? <a href="user-login.php" class="text-danger fw-bold">Log in here</a>
      </div>
    </form>
  </div>
</div>
