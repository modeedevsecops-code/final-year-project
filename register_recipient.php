<?php
include_once 'config/functions.php';
$dbb = new operations();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_register_recipient'])) {
  global $db;
  $conn = $db->connection;

  $name = mysqli_real_escape_string($conn, trim($_POST['name']));
  $email = mysqli_real_escape_string($conn, trim($_POST['email']));
  $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
  $address = mysqli_real_escape_string($conn, trim($_POST['address']));

  // Hash the password before storing — never store plaintext passwords
  $password_hashed = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

  // Check duplicate email
  $dup = mysqli_query($conn, "SELECT recipient_id FROM recipients WHERE email='$email' LIMIT 1");
  if ($dup && mysqli_num_rows($dup) > 0) {
    $msg = '<div class="alert alert-danger text-center">Registration failed. Email is already registered!</div>';
  } else {
    // Geocoding disabled for now — lat/lng left NULL, added back later
    $q = "INSERT INTO recipients (name, email, phone, address, latitude, longitude, password)
          VALUES ('$name', '$email', '$phone', '$address', NULL, NULL, '$password_hashed')";

    if (mysqli_query($conn, $q)) {
      $msg = '<div class="alert alert-success text-center">Registration successful! You can now <a href="user-login.php">login</a> to request blood.</div>';
    } else {
      $msg = '<div class="alert alert-danger text-center">Registration error: ' . mysqli_error($conn) . '</div>';
    }
  }
}
?>

<style>
  .recip-wrap {
    max-width: 620px;
    margin: 0 auto;
    padding: 20px 16px 60px;
  }

  .recip-wrap .recip-header {
    text-align: center;
    padding: 36px 24px 30px;
    background-image: linear-gradient(135deg, #c41e30 0%, #7d0e1c 100%) !important;
    background-color: #8b0f1f !important;
    border-radius: 16px;
    margin-bottom: -40px;
    position: relative;
    z-index: 1;
    box-shadow: 0 12px 28px rgba(139, 15, 31, 0.32);
  }

  .recip-wrap .recip-header .icon-circle {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.16);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
  }

  .recip-wrap .recip-header .drop-icon {
    width: 26px;
    height: 26px;
  }

  .recip-wrap .recip-header .drop-icon path {
    fill: #ffffff !important;
  }

  .recip-wrap .recip-header h2 {
    font-weight: 700 !important;
    color: #ffffff !important;
    margin-bottom: 10px !important;
    letter-spacing: -0.3px;
    font-size: 1.7rem !important;
  }

  .recip-wrap .recip-header p {
    color: rgba(255,255,255,0.92) !important;
    font-size: 0.92rem;
    max-width: 440px;
    margin: 0 auto;
    line-height: 1.55;
  }

  .recip-wrap .recip-card {
    background: #fff;
    border: 1px solid #f0eded;
    border-radius: 16px;
    padding: 44px 32px 32px;
    margin-top: 40px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.07);
    position: relative;
    z-index: 2;
  }

  .recip-wrap .recip-group {
    margin-bottom: 20px;
  }

  .recip-wrap .recip-group label {
    font-weight: 600 !important;
    font-size: 0.86rem;
    color: #3a3a3a !important;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px !important;
  }

  .recip-wrap .recip-group label svg {
    width: 15px;
    height: 15px;
    color: #b91c2c;
    flex-shrink: 0;
  }

  .recip-wrap .recip-group .form-control {
    border: 1.5px solid #e6e2e2 !important;
    border-radius: 10px !important;
    padding: 11px 14px !important;
    font-size: 0.95rem;
    background: #fafafa !important;
    box-shadow: none !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
  }

  .recip-wrap .recip-group .form-control:focus {
    border-color: #b91c2c !important;
    box-shadow: 0 0 0 3px rgba(185, 28, 44, 0.12) !important;
    background: #fff !important;
    outline: none;
  }

  .recip-wrap .recip-group small {
    display: block;
    margin-top: 5px;
    color: #8a8a8a;
    font-size: 0.78rem;
  }

  .recip-wrap .recip-submit {
    width: 100%;
    background-image: linear-gradient(135deg, #c41e30 0%, #931420 100%) !important;
    border: none !important;
    color: #fff !important;
    font-weight: 600 !important;
    padding: 13px !important;
    border-radius: 10px !important;
    font-size: 1rem;
    letter-spacing: 0.2px;
    box-shadow: 0 6px 16px rgba(185, 28, 44, 0.28);
    transition: transform 0.12s ease, box-shadow 0.12s ease;
  }

  .recip-wrap .recip-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(185, 28, 44, 0.35);
    color: #fff !important;
  }

  .recip-wrap .recip-footer {
    text-align: center;
    margin-top: 22px;
    color: #7a7a7a;
    font-size: 0.9rem;
  }

  .recip-wrap .recip-footer a {
    color: #b91c2c;
    font-weight: 600;
    text-decoration: none;
  }

  .recip-wrap .recip-footer a:hover {
    text-decoration: underline;
  }

  .recip-msg-wrap {
    max-width: 620px;
    margin: 0 auto 16px;
    position: relative;
    z-index: 3;
  }
</style>

<div class="recip-wrap">

  <div class="recip-header">
    <div class="icon-circle">
      <svg class="drop-icon" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C12 2 5 11.5 5 15.5C5 19.09 8.13 22 12 22C15.87 22 19 19.09 19 15.5C19 11.5 12 2 12 2Z"/>
      </svg>
    </div>
    <h2>Recipient Registration</h2>
    <p>Register as a Recipient (patient or family member) to request blood and get matched with nearby eligible donors in real time.</p>
  </div>

  <?php if ($msg): ?>
    <div class="recip-msg-wrap"><?php echo $msg; ?></div>
  <?php endif; ?>

  <div class="recip-card">
    <form action="" method="post">

      <div class="recip-group">
        <label>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Full Name
        </label>
        <input type="text" class="form-control" name="name" placeholder="Enter your full name" required>
      </div>

      <div class="recip-group">
        <label>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 6l-10 7L2 6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
          Email Address
        </label>
        <input type="email" class="form-control" name="email" placeholder="you@example.com" required>
      </div>

      <div class="recip-group">
        <label>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Phone Number
        </label>
        <input type="text" class="form-control" name="phone" placeholder="080xxxxxxxx" required>
      </div>

      <div class="recip-group">
        <label>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Address / City
        </label>
        <input type="text" class="form-control" name="address" placeholder="e.g. Malali, Kaduna North" required>
        <small>Used to find donors near you when you submit a request.</small>
      </div>

      <div class="recip-group">
        <label>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Choose Portal Password
        </label>
        <input type="password" class="form-control" name="password" placeholder="Create a password for logging in" required>
      </div>

      <button type="submit" class="recip-submit" name="btn_register_recipient">
        Register as Recipient
      </button>
    </form>
  </div>

  <p class="recip-footer">
    Already registered? <a href="user-login.php">Login here</a>.
  </p>

</div>