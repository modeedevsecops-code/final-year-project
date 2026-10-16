<?php
// Member "Donate Blood" page: 56-day eligibility, donor status, open requests
// this member can help with, and how donating works.
require_once 'config/db.php';
bl_require_role('member');

$conn      = $db->connection;
$ops       = new operations();
$member_id = intval($_SESSION['member_id'] ?? 0);
$me        = $ops->get_donor_by_id($member_id);

$last       = $me['last_donation_date'] ?? null;
$eligible   = $ops->is_donor_eligible($last);
$days_left  = $ops->days_until_eligible($last);
$days_since = $last ? (int)(new DateTime($last))->diff(new DateTime('today'))->format('%r%a') : null;
$next_date  = $last ? date('j M Y', strtotime($last . ' +56 days')) : null;
$progress   = $last ? max(0, min(1, $days_since / 56)) : 1;

$totals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n, COALESCE(SUM(units),0) AS u FROM donations WHERE donor_id = $member_id"));

// Open requests this member's blood can go to (not their own), most urgent first.
$help = [];
if (!empty($me['blood_group'])) {
    $res = mysqli_query($conn, "SELECT br.*, (br.units_needed - COALESCE((SELECT SUM(d.units) FROM donations d WHERE d.request_id = br.request_id), 0)) AS remaining
                                FROM blood_requests br
                                WHERE br.status IN ('Pending','Approved') AND (br.recipient_id IS NULL OR br.recipient_id <> $member_id)
                                ORDER BY br.priority_score DESC, br.created_at ASC");
    while ($res && ($r = mysqli_fetch_assoc($res))) {
        if ($ops->compatibility_score($me['blood_group'], $r['blood_group']) === 0) continue;
        if ((int)$r['remaining'] < 1) continue;
        $r['km'] = null;
        if (is_numeric($me['latitude'] ?? null) && is_numeric($r['latitude'])) {
            $dLat = deg2rad($r['latitude'] - $me['latitude']); $dLng = deg2rad($r['longitude'] - $me['longitude']);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($me['latitude'])) * cos(deg2rad($r['latitude'])) * sin($dLng / 2) ** 2;
            $r['km'] = round(6371 * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
        }
        $help[] = $r;
    }
}
$deferral    = bl_active_deferral($conn, $member_id);
$self_check  = bl_latest_screening($conn, $member_id, 'self');
$urgency_badge = ['Critical Emergency' => 'bl-badge-red', 'Urgent' => 'bl-badge-amber', 'Normal' => 'bl-badge-blue'];
$ring_color = $eligible ? '#2c8a4a' : '#cc0000';
$circ = 2 * M_PI * 62;
?>
<?php include 'inc/header.php'; ?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
<?php include 'inc/navbar.php'; ?>

<div class="main-content">
  <div class="bl-page">

    <div class="welcome-banner">
      <span class="badge-pill">DONATE BLOOD</span>
      <h1>Your donor status</h1>
      <p>Check when you can give next and see open requests your blood type can help with. Officers confirm every donation at the blood bank.</p>
    </div>

    <div class="bl-grid bl-grid-main">

      <!-- Eligibility -->
      <section class="bl-card" style="margin-bottom:0;">
        <div class="bl-card-head">
          <h2 class="bl-card-title"><i class="fas fa-heart-pulse"></i> Eligibility</h2>
          <?php if ($eligible): ?>
            <span class="bl-badge bl-badge-green"><span class="bl-dot"></span> Eligible to donate</span>
          <?php else: ?>
            <span class="bl-badge bl-badge-red"><span class="bl-dot"></span> Recovering</span>
          <?php endif; ?>
        </div>

        <div style="display:flex; gap:1.75rem; align-items:center; flex-wrap:wrap;">
          <div class="bl-ring" aria-hidden="true">
            <svg width="150" height="150" viewBox="0 0 150 150">
              <circle cx="75" cy="75" r="62" fill="none" stroke="#f1e3e3" stroke-width="12"/>
              <circle cx="75" cy="75" r="62" fill="none" stroke="<?= $ring_color ?>" stroke-width="12" stroke-linecap="round"
                      stroke-dasharray="<?= round($circ, 1) ?>" stroke-dashoffset="<?= round($circ * (1 - $progress), 1) ?>"/>
            </svg>
            <div class="bl-ring-label">
              <?php if ($eligible): ?>
                <strong><i class="fas fa-check" style="color:#2c8a4a;"></i></strong><span>Ready when you are</span>
              <?php else: ?>
                <strong><?= (int)$days_left ?></strong><span>day<?= $days_left == 1 ? '' : 's' ?> until you can donate</span>
              <?php endif; ?>
            </div>
          </div>

          <div style="flex:1; min-width:220px;">
            <h3 style="font-size:1.15rem; font-weight:700; margin:0 0 .35rem;">
              <?= $eligible ? 'You can donate now' : 'Thank you for your last donation' ?>
            </h3>
            <p style="color:#6b7280; font-size:.9rem; margin:0 0 1rem;">
              <?php if (!$last): ?>
                You haven't donated with BloodLink yet. Keep your availability on and an officer will contact you when a compatible request comes in.
              <?php elseif ($eligible): ?>
                It's been <?= (int)$days_since ?> days since your last donation — past the 56-day recovery window.
              <?php else: ?>
                Your body needs 56 days to replace the red cells you gave. You'll be eligible again on <strong><?= htmlspecialchars($next_date) ?></strong>.
              <?php endif; ?>
            </p>
            <dl class="bl-dl">
              <dt>Blood group</dt><dd><span class="bl-group bl-group-sm"><?= htmlspecialchars($me['blood_group'] ?? '—') ?></span></dd>
              <dt>Last donation</dt><dd><?= $last ? htmlspecialchars(date('j M Y', strtotime($last))) : 'Never' ?></dd>
              <dt>Next eligible</dt><dd><?= $eligible ? 'Now' : htmlspecialchars($next_date) ?></dd>
            </dl>
          </div>
        </div>
      </section>

      <!-- Donor profile -->
      <section class="bl-card" style="margin-bottom:0;">
        <div class="bl-card-head">
          <h2 class="bl-card-title"><i class="fas fa-id-card"></i> Your donor profile</h2>
          <a href="member_dashboard.php" class="bl-btn bl-btn-outline bl-btn-sm">Change</a>
        </div>
        <dl class="bl-dl" style="margin-bottom:1.1rem;">
          <dt>Availability</dt>
          <dd><?= !empty($me['is_available'])
                  ? '<span class="bl-badge bl-badge-green"><span class="bl-dot"></span> Available</span>'
                  : '<span class="bl-badge bl-badge-grey"><span class="bl-dot"></span> Not available</span>' ?></dd>
          <dt>Schedule</dt><dd><?= htmlspecialchars(ucfirst($me['availability_schedule'] ?? 'anytime')) ?></dd>
          <dt>Contact by</dt><dd><?= htmlspecialchars(ucfirst($me['contact_preference'] ?? 'both')) ?></dd>
          <dt>Health check</dt>
          <dd><?php if ($deferral): ?><span class="bl-badge <?= $deferral['outcome'] === 'permanent_deferral' ? 'bl-badge-red' : 'bl-badge-amber' ?>"><span class="bl-dot"></span> <?= htmlspecialchars(bl_deferral_label($deferral)) ?></span>
              <?php elseif ($self_check): ?><span class="bl-badge bl-badge-green"><span class="bl-dot"></span> Done <?= htmlspecialchars(date('j M', strtotime($self_check['screened_at']))) ?></span> <a href="screening.php">Redo</a>
              <?php else: ?><a href="screening.php">Take the 1-minute check</a><?php endif; ?></dd>
          <dt>Location</dt><dd><?= is_numeric($me['latitude'] ?? null) ? htmlspecialchars($me['address'] ?? 'On the map') : '<a href="profile.php">Add your address</a>' ?></dd>
        </dl>
        <div class="bl-grid bl-grid-2" style="gap:.75rem;">
          <div class="bl-stat"><div class="bl-stat-icon"><i class="fas fa-tint"></i></div><div><div class="bl-stat-value"><?= (int)$totals['n'] ?></div><div class="bl-stat-label">Donations</div></div></div>
          <div class="bl-stat"><div class="bl-stat-icon"><i class="fas fa-flask"></i></div><div><div class="bl-stat-value"><?= (int)$totals['u'] ?></div><div class="bl-stat-label">Units given</div></div></div>
        </div>
        <?php if ($deferral): ?>
          <div class="bl-notice bl-notice-warn" style="margin:1rem 0 0;"><i class="fas fa-hourglass-half"></i><div>You're not being matched to requests right now because of your health screening. <a href="screening.php">See details</a></div></div>
        <?php endif; ?>
        <?php if (empty($me['is_available'])): ?>
          <div class="bl-notice bl-notice-warn" style="margin:1rem 0 0;"><i class="fas fa-circle-info"></i><div>Officers can't match you while you're marked unavailable. Turn availability on from your dashboard when you're ready.</div></div>
        <?php endif; ?>
      </section>
    </div>

    <!-- Requests you can help with -->
    <section class="bl-card" style="margin-top:1.25rem;">
      <div class="bl-card-head">
        <div>
          <h2 class="bl-card-title"><i class="fas fa-hand-holding-medical"></i> Requests you can help with</h2>
          <p class="bl-card-sub">Open requests your <?= htmlspecialchars($me['blood_group'] ?? '') ?> blood is compatible with<?= $eligible ? '' : ' — you can donate again from ' . htmlspecialchars($next_date) ?>.</p>
        </div>
        <span class="bl-badge bl-badge-grey"><?= count($help) ?> open</span>
      </div>

      <?php if (empty($help)): ?>
        <div class="bl-empty"><i class="fas fa-circle-check"></i><strong>No open requests need your blood type right now</strong>You'll get a notification when one comes in.</div>
      <?php else: ?>
        <div class="bl-table-wrap">
          <table class="bl-table bl-table-cards">
            <thead><tr><th>Needed</th><th>Urgency</th><th>Hospital</th><th>Units</th><th>Distance</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($help as $r): ?>
                <tr>
                  <td data-label="Needed"><span class="bl-group bl-group-sm"><?= htmlspecialchars($r['blood_group']) ?></span></td>
                  <td data-label="Urgency"><span>
                    <span class="bl-badge <?= $urgency_badge[$r['urgency_level']] ?? 'bl-badge-grey' ?>"><?= htmlspecialchars($r['urgency_level']) ?></span>
                    <?php if (!empty($r['required_within_hours'])): ?><span style="display:block; font-size:.75rem; color:#6b7280; margin-top:.25rem;">within <?= (int)$r['required_within_hours'] ?> h</span><?php endif; ?>
                  </span></td>
                  <td data-label="Hospital"><span class="bl-cell-end"><strong><?= htmlspecialchars($r['hospital_name']) ?></strong><span style="display:block; font-size:.78rem; color:#6b7280;"><?= htmlspecialchars($r['location']) ?></span></span></td>
                  <td data-label="Units"><?= (int)$r['remaining'] ?> still needed</td>
                  <td data-label="Distance"><?= $r['km'] !== null ? htmlspecialchars($r['km']) . ' km' : '<span style="color:#999;">—</span>' ?></td>
                  <td class="bl-td-full" style="text-align:right;"><a href="geo_map.php" class="bl-btn bl-btn-outline bl-btn-sm"><i class="fas fa-route"></i> Directions</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="bl-card-sub" style="margin-top:.75rem;"><i class="fas fa-phone"></i> To help, contact the hospital's blood bank — an officer will check your eligibility and record your donation.</p>
      <?php endif; ?>
    </section>

    <!-- How it works + before you donate -->
    <div class="bl-grid bl-grid-main">
      <section class="bl-card" style="margin-bottom:0;">
        <h2 class="bl-card-title" style="margin-bottom:1rem;"><i class="fas fa-route"></i> How donating works</h2>
        <ol class="bl-steps bl-steps-5">
          <li><strong>Stay available</strong><span>Keep your availability on so officers can find you when someone needs your blood type.</span></li>
          <li><strong>Get matched</strong><span>An officer matches you to a request and contacts you by your preferred method.</span></li>
          <li><strong>Get screened</strong><span>At the bank an officer checks your health, vital signs and blood tests. Do the <a href="screening.php">health check</a> first to know before you go.</span></li>
          <li><strong>Donate</strong><span>If you pass, you donate and the officer records it.</span></li>
          <li><strong>Recover for 56 days</strong><span>You'll see it under My Donations, and this page counts down to your next one.</span></li>
        </ol>
      </section>
      <section class="bl-card" style="margin-bottom:0;">
        <h2 class="bl-card-title" style="margin-bottom:.75rem;"><i class="fas fa-clipboard-check"></i> Before you donate</h2>
        <ul class="bl-checklist">
          <li><i class="fas fa-check-circle"></i>Eat a proper meal and drink plenty of water beforehand.</li>
          <li><i class="fas fa-check-circle"></i>Get a good night's sleep and avoid alcohol the day before.</li>
          <li><i class="fas fa-check-circle"></i>Bring your Member ID (<?= htmlspecialchars($me['member_code'] ?? '') ?>) and a photo ID.</li>
          <li><i class="fas fa-check-circle"></i>Tell the officer about any illness, medication or recent travel.</li>
        </ul>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-top:1rem;">
          <a href="screening.php" class="bl-btn bl-btn-primary bl-btn-sm"><i class="fas fa-stethoscope"></i> Take the health check</a>
          <a href="my_donations.php" class="bl-btn bl-btn-outline bl-btn-sm"><i class="fas fa-award"></i> View my donations</a>
        </div>
      </section>
    </div>

  </div>
</div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
