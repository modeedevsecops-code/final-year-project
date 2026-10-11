<?php
require_once 'config/functions.php';  // this already starts the session, connects DB, and defines $db
$ops = new operations();

// --- Officer auth guard ---
bl_require_role('officer');
$officer_id = $_SESSION['user_id']; // worker_id, set during officer login

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$feedback = null;

// --- Handle donor confirmation submit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_donation'])) {
    bl_csrf_check(); // BL-14
    $feedback = $ops->process_donation((int)($_POST['donor_id'] ?? 0), $request_id, $officer_id, (int)($_POST['units'] ?? 0));
    if ($feedback['success']) {
        $_SESSION['flash_match'] = $feedback['message'];
        header("Location: match_donor.php?request_id=$request_id");
        exit;
    }
}
$flash = $_SESSION['flash_match'] ?? null;
unset($_SESSION['flash_match']);

// --- Load the request + matching donors via the operations class ---
// Pass the request's coordinates so donors come back ordered nearest-first —
// the point of the geo feature (blood is time-critical and donors must travel).
$request = $ops->get_blood_request($request_id);
$is_open = $request && in_array($request['status'], ['Pending', 'Approved'], true);
$donated = $request ? $ops->units_donated_for_request($request_id) : 0;
$remaining = $request ? max(0, (int)$request['units_needed'] - $donated) : 0;
$max_units = min($remaining, BL_MAX_UNITS_PER_DONATION);
$matching_donors = $is_open
    ? $ops->find_matching_donors($request['blood_group'], $request['latitude'] ?? null, $request['longitude'] ?? null, null, (int)$request['recipient_id'])
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

  <?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
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
      <p><strong>Units needed:</strong> <?= (int)$request['units_needed'] ?>
        <?php if ($donated): ?>(<?= $donated ?> donated, <?= $remaining ?> still needed)<?php endif; ?></p>
      <p><strong>Hospital:</strong> <?= htmlspecialchars($request['hospital_name']) ?> (<?= htmlspecialchars($request['location']) ?>)</p>
      <p><strong>Urgency:</strong> <?= htmlspecialchars($request['urgency_level']) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($request['status']) ?></p>
    </div>

    <?php if (!$is_open): ?>
      <div class="alert alert-info">This request is <?= htmlspecialchars($request['status']) ?> — no further donations can be recorded against it.</div>
    <?php else: ?>
    <h3>Eligible &amp; Compatible Donors (56-day rule applied)</h3>

    <?php if (empty($matching_donors)): ?>
      <p>No eligible, compatible donors found right now. Consider fulfilling from current stock, or check back later.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Blood Group</th>
            <th>Match</th>
            <th>Availability</th>
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
              <td><span class="badge-pill" style="background:#eef2f5;color:#3f6b7a;font-weight:600;"><?= (int)$donor['compatibility_score'] ?></span></td>
              <td><?= !empty($donor['is_available'])
                      ? '<span style="color:#2c8a4a;font-weight:600;">&#9679; Available</span>'
                      : '<span style="color:#999;">Unavailable</span>' ?></td>
              <?php if ($has_request_geo): ?>
                <td><?= isset($donor['distance_km']) && $donor['distance_km'] !== null
                        ? htmlspecialchars($donor['distance_km']) . ' km'
                        : '<span style="color:#999;">no location</span>' ?></td>
              <?php endif; ?>
              <td><?= $donor['last_donation_date'] ? htmlspecialchars($donor['last_donation_date']) : 'Never donated' ?></td>
              <td><?= $donor['days_since_last_donation'] >= 9999 ? '—' : (int)$donor['days_since_last_donation'] ?></td>
              <td><?= htmlspecialchars($donor['phone']) ?> / <?= htmlspecialchars($donor['email']) ?></td>
              <td>
                <?php if (empty($donor['is_available'])): ?>
                  <button type="button" class="btn btn-secondary" disabled title="This member has marked themselves unavailable">Unavailable</button>
                <?php else: ?>
                <form method="POST" onsubmit="return confirm('Confirm this donation match?');">
                  <?php bl_csrf_field(); // BL-14 ?>
                  <input type="hidden" name="donor_id" value="<?= (int)$donor['donor_id'] ?>">
                  <input type="number" name="units" min="1" max="<?= $max_units ?>" value="<?= $max_units ?>" style="width:60px;" required aria-label="Units">
                  <button type="submit" name="confirm_donation" class="btn btn-primary">Confirm Match</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <?php endif; ?>

  <?php endif; ?>
</div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>