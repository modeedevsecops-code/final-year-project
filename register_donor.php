<?php
include 'config/functions.php';
$dbb = new operations();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_register_donor'])) {
  global $db;
  $conn = $db->connection;

  $name = mysqli_real_escape_string($conn, trim($_POST['name']));
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
  $reg_no = mysqli_real_escape_string($conn, trim($_POST['reg_no']));
  $blood_group = mysqli_real_escape_string($conn, trim($_POST['blood_group']));
  $password = mysqli_real_escape_string($conn, trim($_POST['password']));

  // Check duplicate email
  $dup = mysqli_query($conn, "SELECT student_id FROM students WHERE email='$email' LIMIT 1");
  if (mysqli_num_rows($dup) > 0) {
    $msg = '<div class="alert alert-danger text-center">Registration failed. Email is already registered!</div>';
  } else {
    $q = "INSERT INTO students (name, email, phone, reg_no, year_of_study, password)
              VALUES ('$name', '$email', '$phone', '$reg_no', '$blood_group', '$password')";
    if (mysqli_query($conn, $q)) {
      $msg = '<div class="alert alert-success text-center">Donor registration successful! You can now <a href="user-login.php">login</a>.</div>';
    } else {
      $msg = '<div class="alert alert-danger text-center">Registration error: ' . mysqli_error($conn) . '</div>';
    }
  }
}
?>

<div class="card border-0 shadow-lg">
  <div class="card-header bg-danger text-white py-3">
    <h4 class="mb-0 text-white text-center">🩸 Blood Donor Self-Registration</h4>
  </div>
  <div class="card-body p-4 bg-light">
    <p class="text-center text-muted mb-4">
      Sign up today as a voluntary blood donor. Your coordinates will help match you to local emergency alerts.
    </p>

    <?php echo $msg; ?>

    <form action="" method="POST">
      <div class="mb-3">
        <label class="form-label fw-bold">Full Name</label>
        <input type="text" class="form-control" name="name" placeholder="e.g. Aliyu Abubakar" required>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-bold">Email Address</label>
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
          <label class="form-label fw-bold">Donor ID / Preferred Reg No</label>
          <input type="text" class="form-control" name="reg_no" placeholder="e.g. BL-KD-099" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-bold">Choose Portal Password</label>
        <input type="password" class="form-control" name="password"
          placeholder="Create a password for logging in" required>
      </div>

      <button type="submit" name="btn_register_donor" class="btn btn-danger w-100 py-2 fw-bold">
        Submit Registration
      </button>

      <div class="text-center mt-3 small">
        Already registered? <a href="user-login.php" class="text-danger fw-bold">Log in here</a>
      </div>
    </form>
  </div>
</div>