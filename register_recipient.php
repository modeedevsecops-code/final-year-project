<?php
include 'config/functions.php';
require_once 'config/geocode.php';
$dbb = new operations();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_register_recipient'])) {
  global $db;
  $conn = $db->connection;

  $name = mysqli_real_escape_string($conn, trim($_POST['name']));
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
  $password = mysqli_real_escape_string($conn, trim($_POST['password']));
  $address_raw = trim($_POST['address']);
  $address = mysqli_real_escape_string($conn, $address_raw);

  // Check duplicate email
  $dup = mysqli_query($conn, "SELECT recipient_id FROM recipients WHERE email='$email' LIMIT 1");
  if ($dup && mysqli_num_rows($dup) > 0) {
    $msg = '<div class="alert alert-danger text-center">Registration failed. Email is already registered!</div>';
  } else {
    // Real geocoding: convert the typed address into actual coordinates
    $coords = geocode_address($conn, $address_raw);
    $lat = $coords ? $coords['lat'] : null;
    $lon = $coords ? $coords['lon'] : null;
    $lat_sql = $lat !== null ? "'" . mysqli_real_escape_string($conn, $lat) . "'" : "NULL";
    $lon_sql = $lon !== null ? "'" . mysqli_real_escape_string($conn, $lon) . "'" : "NULL";

    $q = "INSERT INTO recipients (name, email, phone, address, latitude, longitude, password)
          VALUES ('$name', '$email', '$phone', '$address', $lat_sql, $lon_sql, '$password')";

    if (mysqli_query($conn, $q)) {
      if ($coords) {
        $msg = '<div class="alert alert-success text-center">Registration successful! You can now <a href="user-login.php">login</a> to request blood.</div>';
      } else {
        $msg = '<div class="alert alert-warning text-center">Registered, but we could not pinpoint your address on the map — you can still request blood, just add a more specific hospital location when you submit a request. You can now <a href="user-login.php">login</a>.</div>';
      }
    } else {
      $msg = '<div class="alert alert-danger text-center">Registration error: ' . mysqli_error($conn) . '</div>';
    }
  }
}
?>

<div class="text-center mb-4">
  <h2>Recipient Registration</h2>
  <p class="text-muted">
    Register as a Recipient (patient or family member) to request blood and get matched
    with nearby eligible donors in real time.
  </p>
</div>

<?php echo $msg; ?>

<div class="card shadow-sm">
  <div class="card-body p-4">
    <form action="" method="post">
      <div class="mb-3">
        <label class="form-label fw-bold">Full Name</label>
        <input type="text" class="form-control" name="name" placeholder="Enter your full name" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Email Address</label>
        <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Phone Number</label>
        <input type="text" class="form-control" name="phone" placeholder="080xxxxxxxx" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-bold">Address / City</label>
        <input type="text" class="form-control" name="address"
          placeholder="e.g. Malali, Kaduna North" required>
        <small class="text-muted">Used to find donors near you when you submit a request.</small>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Choose Portal Password</label>
        <input type="password" class="form-control" name="password"
          placeholder="Create a password for logging in" required>
      </div>

      <button type="submit" class="btn btn-danger w-100" name="btn_register_recipient">
        Register as Recipient
      </button>
    </form>
  </div>
</div>

<p class="text-center mt-3 text-muted">
  Already registered? <a href="user-login.php">Login here</a>.
</p>