<?php
require_once 'config/functions.php';  // this already starts the session, connects DB, and defines $db
$ops = new operations();

// --- Officer auth guard, matching your real login session keys ---
if (!isset($_SESSION['Active']) || $_SESSION['role'] !== 'supervisor') {
    header('Location: login.php');
    exit;
}
$officer_id = $_SESSION['user_id']; // staff_id, set during supervisor login

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$feedback = null;

// --- Handle donor confirmation submit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_donation'])) {
    bl_csrf_check(); // BL-14
    $donor_id     = (int)$_POST['donor_id'];
    $recipient_id = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
    $req_id       = (int)$_POST['request_id'];
    $blood_group  = $_POST['blood_group'];
    $units        = (int)$_POST['units'];

    $feedback = $ops->process_donation($donor_id, $recipient_id, $req_id, $officer_id, $blood_group, $units);

    if ($feedback['success']) {
        header("Location: match_donor.php?request_id=$req_id&done=1");
        exit;
    }
}

// --- Load the request + matching donors via the operations class ---
// Pass the request's coordinates so donors come back ordered nearest-first —
// the point of the geo feature (blood is time-critical and donors must travel).
$request = $ops->get_blood_request($request_id);
$matching_donors = $request
    ? $ops->find_matching_donors($request['blood_group'], $request['latitude'] ?? null, $request['longitude'] ?? null)
    : [];
$has_request_geo = $request && is_numeric($request['latitude'] ?? null) && is_numeric($request['longitude'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'inc/header.php'; // theme.css + Font Awesome + sidebar.css, like every other page ?>
<body>
<main class="main" id="top">
<?php include 'inc/navbar.php'; ?>

<div class="main-content">
  <div class="welcome-banner">
    <h2>Match a Donor</h2>
  </div>

  <?php if (isset($_GET['done'])): ?>
    <div class="alert alert-success">Donation recorded and request updated.</div>
  <?php endif; ?>

  <?php if ($feedback && !$feedback['success']): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($feedback['message']) ?></div>
  <?php endif; ?>

  <?php if (!$request): ?>
    <p>Blood request not found.</p>
  <?php else: ?>

    <div class="quick-card">
      <h3>Request #<?= (int)$request['request_id'] ?> &mdash; <?= htmlspecialchars($request['patient_name']) ?></h3>
      <p><strong>Blood group needed:</strong> <?= htmlspecialchars($request['blood_group']) ?></p>
      <p><strong>Units needed:</strong> <?= (int)$request['units_needed'] ?></p>
      <p><strong>Hospital:</strong> <?= htmlspecialchars($request['hospital_name']) ?> (<?= htmlspecialchars($request['location']) ?>)</p>
      <p><strong>Urgency:</strong> <?= htmlspecialchars($request['urgency_level']) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($request['status']) ?></p>
    </div>

    <h3>Eligible &amp; Compatible Donors (56-day rule applied)</h3>

    <?php if (empty($matching_donors)): ?>
      <p>No eligible, compatible donors found right now. Consider fulfilling from current stock, or check back later.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Blood Group</th>
            <?php if ($has_request_geo): ?><th>Distance</th><?php endif; ?>
            <th>Last Donation</th>
            <th>Days Since Last</th>
            <th>Contact</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($matching_donors as $donor): ?>
            <tr>
              <td><?= htmlspecialchars($donor['name']) ?></td>
              <td><?= htmlspecialchars($donor['blood_group']) ?></td>
              <?php if ($has_request_geo): ?>
                <td><?= isset($donor['distance_km']) && $donor['distance_km'] !== null
                        ? htmlspecialchars($donor['distance_km']) . ' km'
                        : '<span style="color:#999;">no location</span>' ?></td>
              <?php endif; ?>
              <td><?= $donor['last_donation_date'] ? htmlspecialchars($donor['last_donation_date']) : 'Never donated' ?></td>
              <td><?= $donor['days_since_last_donation'] >= 9999 ? '—' : (int)$donor['days_since_last_donation'] ?></td>
              <td><?= htmlspecialchars($donor['phone']) ?> / <?= htmlspecialchars($donor['email']) ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('Confirm this donation match?');">
                  <?php bl_csrf_field(); // BL-14 ?>
                  <input type="hidden" name="donor_id" value="<?= (int)$donor['student_id'] ?>">
                  <input type="hidden" name="recipient_id" value="<?= (int)($request['recipient_id'] ?? 0) ?>">
                  <input type="hidden" name="request_id" value="<?= (int)$request['request_id'] ?>">
                  <input type="hidden" name="blood_group" value="<?= htmlspecialchars($request['blood_group']) ?>">
                  <input type="number" name="units" min="1" max="<?= (int)$request['units_needed'] ?>" value="<?= (int)$request['units_needed'] ?>" style="width:60px;" required>
                  <button type="submit" name="confirm_donation" class="btn btn-primary">Confirm Match</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  <?php endif; ?>
</div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>