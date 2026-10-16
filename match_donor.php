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

// Donations already recorded against this request.
$given = [];
if ($request) {
    $res = mysqli_query($db->connection, "SELECT d.units, d.blood_group, d.donation_date, m.name FROM donations d
                                          LEFT JOIN members m ON m.member_id = d.donor_id
                                          WHERE d.request_id = " . (int)$request_id . " ORDER BY d.donation_id");
    $given = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}
$deadline_ts = ($request && !empty($request['required_within_hours'])) ? strtotime($request['created_at']) + (int)$request['required_within_hours'] * 3600 : null;
$urgency_badge = ['Critical Emergency' => 'bl-badge-red', 'Urgent' => 'bl-badge-amber', 'Normal' => 'bl-badge-blue'];
$status_badge  = ['Pending' => 'bl-badge-amber', 'Approved' => 'bl-badge-blue', 'Fulfilled' => 'bl-badge-green', 'Cancelled' => 'bl-badge-grey'];
$available_count = count(array_filter($matching_donors, function ($d) { return !empty($d['is_available']); }));
// Which listed donors have already passed today's health screening.
foreach ($matching_donors as &$d) { $d['screened'] = (bool)bl_screening_for_donation($db->connection, $d['donor_id']); }
unset($d);
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
      <span class="badge-pill">MATCH A DONOR</span>
      <h1><?= $request ? 'Request #' . (int)$request['request_id'] . ' — ' . htmlspecialchars($request['patient_name']) : 'Match a Donor' ?></h1>
      <p>Pick a compatible, eligible donor, screen them, and confirm the units they give. BloodLink re-checks compatibility, the 56-day rule, availability and today's health screening before recording it.</p>
    </div>

    <?php if ($flash): ?>
      <div class="bl-notice bl-notice-success"><i class="fas fa-circle-check"></i><div><?= htmlspecialchars($flash) ?></div></div>
    <?php endif; ?>
    <?php if ($feedback && !$feedback['success']): ?>
      <div class="bl-notice bl-notice-error"><i class="fas fa-triangle-exclamation"></i><div><?= htmlspecialchars($feedback['message']) ?></div></div>
    <?php endif; ?>

    <?php if (!$request): ?>
      <section class="bl-card"><div class="bl-empty"><i class="fas fa-magnifying-glass"></i><strong>Blood request not found</strong><a href="blood_requests.php">Back to Blood Requests</a></div></section>
    <?php else: ?>

    <!-- Request summary -->
    <section class="bl-card">
      <div style="display:flex; gap:1.5rem; align-items:flex-start; flex-wrap:wrap;">
        <div style="text-align:center;">
          <div class="bl-group bl-group-lg"><?= htmlspecialchars($request['blood_group']) ?></div>
          <div class="bl-eyebrow" style="margin-top:.45rem;">Needed</div>
        </div>

        <div style="flex:1; min-width:240px;">
          <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.6rem;">
            <span class="bl-badge <?= $urgency_badge[$request['urgency_level']] ?? 'bl-badge-grey' ?>"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($request['urgency_level']) ?></span>
            <span class="bl-badge <?= $status_badge[$request['status']] ?? 'bl-badge-grey' ?>"><span class="bl-dot"></span> <?= htmlspecialchars($request['status']) ?></span>
            <span class="bl-badge bl-badge-grey">Priority <?= (int)$request['priority_score'] ?></span>
            <?php if ($deadline_ts && $is_open): ?>
              <span class="bl-badge bl-badge-red" id="deadline" data-deadline="<?= (int)$deadline_ts ?>"><i class="fas fa-clock"></i> <span>—</span></span>
            <?php endif; ?>
          </div>
          <h2 style="font-size:1.25rem; font-weight:700; margin:0 0 .2rem;"><?= htmlspecialchars($request['patient_name']) ?></h2>
          <p style="color:#6b7280; margin:0 0 1rem; font-size:.9rem;">
            <i class="fas fa-hospital" style="color:#cc0000;"></i> <?= htmlspecialchars($request['hospital_name']) ?> · <?= htmlspecialchars($request['location']) ?>
            <?php if (!empty($request['contact_phone'])): ?> &nbsp;·&nbsp; <i class="fas fa-phone"></i> <?= htmlspecialchars($request['contact_phone']) ?><?php endif; ?>
          </p>

          <div style="max-width:520px;">
            <?php $pct = $request['units_needed'] ? min(100, round(100 * $donated / $request['units_needed'])) : 0; ?>
            <div class="bl-progress<?= $remaining === 0 ? ' green' : '' ?>"><span style="width:<?= $pct ?>%;"></span></div>
            <div class="bl-progress-meta">
              <span><strong style="color:#1c1c1e;"><?= $donated ?></strong> of <?= (int)$request['units_needed'] ?> unit<?= $request['units_needed'] == 1 ? '' : 's' ?> donated</span>
              <span><?= $remaining ? $remaining . ' still needed' : 'Fully covered' ?></span>
            </div>
          </div>
        </div>

        <div style="min-width:200px;">
          <div class="bl-eyebrow" style="margin-bottom:.5rem;">Donations so far</div>
          <?php if (empty($given)): ?>
            <p style="color:#999; font-size:.85rem; margin:0;">None yet.</p>
          <?php else: foreach ($given as $g): ?>
            <div style="display:flex; align-items:center; gap:.5rem; font-size:.85rem; margin-bottom:.4rem;">
              <span class="bl-group bl-group-sm"><?= htmlspecialchars($g['blood_group']) ?></span>
              <span><strong><?= htmlspecialchars($g['name'] ?? 'Deleted member') ?></strong><br><span style="color:#6b7280;"><?= (int)$g['units'] ?> unit<?= $g['units'] == 1 ? '' : 's' ?> · <?= htmlspecialchars(date('j M', strtotime($g['donation_date']))) ?></span></span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </section>

    <?php if (!$is_open): ?>
      <div class="bl-notice bl-notice-info"><i class="fas fa-circle-info"></i><div>This request is <strong><?= htmlspecialchars($request['status']) ?></strong> — no further donations can be recorded against it. <a href="blood_requests.php">Back to Blood Requests</a></div></div>
    <?php else: ?>

    <!-- Donor list -->
    <section class="bl-card">
      <div class="bl-card-head">
        <div>
          <h2 class="bl-card-title"><i class="fas fa-users"></i> Eligible &amp; compatible donors</h2>
          <p class="bl-card-sub">56-day rule applied, deferred donors left out · ranked available first, then best match<?= $has_request_geo ? ', then nearest to the hospital' : '' ?>. The requester is never listed.</p>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
          <span class="bl-badge bl-badge-green"><?= $available_count ?> available</span>
          <span class="bl-badge bl-badge-grey"><?= count($matching_donors) ?> listed</span>
        </div>
      </div>

      <?php if (empty($matching_donors)): ?>
        <div class="bl-empty"><i class="fas fa-user-clock"></i><strong>No eligible, compatible donors right now</strong>Check back later, record a walk-in donation, or post an emergency alert to reach members.</div>
      <?php else: ?>
        <div class="bl-table-wrap">
          <table class="bl-table bl-table-cards bl-table-cards-xl">
            <thead>
              <tr>
                <th>Donor</th><th>Group</th><th>Match</th><th>Availability</th>
                <?php if ($has_request_geo): ?><th>Distance</th><?php endif; ?>
                <th>Last donation</th><th>Screening</th><th style="text-align:right;">Confirm</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($matching_donors as $donor):
                $avail = !empty($donor['is_available']);
                $score = (int)$donor['compatibility_score'];
                $initial = strtoupper(mb_substr(trim($donor['name']) ?: '?', 0, 1)); ?>
              <tr class="<?= $avail ? '' : 'is-muted' ?>">
                <td class="bl-td-full">
                  <div class="bl-person"><span class="bl-avatar"><?= htmlspecialchars($initial) ?></span>
                    <span><strong><?= htmlspecialchars($donor['name']) ?></strong><small><?= htmlspecialchars($donor['member_code'] ?? '') ?></small>
                      <span class="bl-contact" style="display:block; margin-top:.15rem;">
                        <a href="tel:<?= htmlspecialchars($donor['phone']) ?>"><i class="fas fa-phone"></i> <?= htmlspecialchars($donor['phone']) ?></a>
                        &nbsp;<a href="mailto:<?= htmlspecialchars($donor['email']) ?>" title="<?= htmlspecialchars($donor['email']) ?>"><i class="fas fa-envelope"></i> Email</a>
                      </span></span></div>
                </td>
                <td data-label="Group"><span class="bl-group bl-group-sm"><?= htmlspecialchars($donor['blood_group']) ?></span></td>
                <td data-label="Match">
                  <span class="bl-score" title="<?= $score === 100 ? 'Exact match' : ($score === 90 ? 'Universal donor (O−)' : ($score === 80 ? 'Same ABO group' : 'Compatible')) ?>">
                    <span class="bl-score-bar"><span style="width:<?= $score ?>%;"></span></span><?= $score ?></span>
                </td>
                <td data-label="Availability"><?= $avail
                    ? '<span class="bl-badge bl-badge-green"><span class="bl-dot"></span> Available</span>'
                    : '<span class="bl-badge bl-badge-grey"><span class="bl-dot"></span> Unavailable</span>' ?></td>
                <?php if ($has_request_geo): ?>
                  <td data-label="Distance"><?= isset($donor['distance_km']) && $donor['distance_km'] !== null
                      ? '<span><strong>' . htmlspecialchars($donor['distance_km']) . '</strong> km</span>'
                      : '<span style="color:#999;">No location</span>' ?></td>
                <?php endif; ?>
                <td data-label="Last donation"><?= $donor['last_donation_date']
                    ? '<span>' . htmlspecialchars(date('j M Y', strtotime($donor['last_donation_date']))) . '<span style="display:block; font-size:.75rem; color:#6b7280;">' . (int)$donor['days_since_last_donation'] . ' days ago</span></span>'
                    : '<span style="color:#6b7280;">Never</span>' ?></td>
                <td data-label="Screening"><?= $donor['screened']
                    ? '<span class="bl-badge bl-badge-green"><i class="fas fa-stethoscope"></i> Passed today</span>'
                    : '<span class="bl-badge bl-badge-grey">Not screened today</span>' ?></td>
                <td class="bl-td-full">
                  <?php if (!$avail): ?>
                    <div class="bl-confirm"><button type="button" class="bl-btn bl-btn-ghost bl-btn-sm" disabled title="This member has marked themselves unavailable">Unavailable</button></div>
                  <?php elseif (!$donor['screened']): ?>
                    <div class="bl-confirm"><a href="screen_donor.php?member_id=<?= (int)$donor['donor_id'] ?>&amp;request_id=<?= (int)$request_id ?>" class="bl-btn bl-btn-outline bl-btn-sm"><i class="fas fa-stethoscope"></i> Screen donor</a></div>
                  <?php else: ?>
                    <form method="POST" class="bl-confirm"
                          onsubmit="return confirm('Record ' + this.units.value + ' unit(s) from <?= htmlspecialchars(addslashes($donor['name']), ENT_QUOTES) ?> for this request?');">
                      <?php bl_csrf_field(); ?>
                      <input type="hidden" name="donor_id" value="<?= (int)$donor['donor_id'] ?>">
                      <label class="bl-units" title="Units (1–<?= $max_units ?>)">
                        <input type="number" name="units" min="1" max="<?= $max_units ?>" value="<?= $max_units ?>" required aria-label="Units">
                        <span>unit<?= $max_units == 1 ? '' : 's' ?></span>
                      </label>
                      <button type="submit" name="confirm_donation" class="bl-btn bl-btn-primary bl-btn-sm"><i class="fas fa-check"></i> Confirm</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="bl-card-sub" style="margin-top:.85rem;"><i class="fas fa-circle-info"></i> Match score: 100 exact group · 90 universal O− · 80 same ABO group · 70 other compatible group. Each donation is 1–<?= BL_MAX_UNITS_PER_DONATION ?> units.</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <a href="blood_requests.php" class="bl-btn bl-btn-outline"><i class="fas fa-arrow-left"></i> Back to Blood Requests</a>
    <?php endif; ?>

  </div>
</div>
</main>
<script>
// Live countdown to the request's deadline.
(function () {
  var el = document.getElementById('deadline'); if (!el) return;
  var end = parseInt(el.dataset.deadline, 10) * 1000, out = el.querySelector('span');
  function tick() {
    var s = Math.round((end - Date.now()) / 1000);
    if (s <= 0) { out.textContent = 'Deadline passed'; return; }
    var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60);
    out.textContent = 'Due in ' + (h ? h + ' h ' : '') + m + ' min';
  }
  tick(); setInterval(tick, 30000);
})();
</script>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
