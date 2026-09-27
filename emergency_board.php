<?php
// Emergency Board — pending/approved requests ranked by computed priority
// (severity + blood-type rarity + time pressure), with live countdown timers.
// Admin/officer facing.
require_once 'config/functions.php';
if (empty($_SESSION['Active']) || !in_array($_SESSION['role'] ?? '', ['admin','officer'], true)) {
    header('Location: login.php'); exit;
}
$ops      = new operations();
$requests = $ops->get_emergency_requests();
$isOfficer = ($_SESSION['role'] ?? '') === 'officer';

function eb_severity_badge($sev) {
    $map = [
        'critical' => ['#cc0000', '#fdeaea', 'Critical'],
        'severe'   => ['#a9660a', '#fbf3e6', 'Severe'],
        'moderate' => ['#3f6b7a', '#eef2f5', 'Moderate'],
        'low'      => ['#2c6a4a', '#eaf3ee', 'Low'],
    ];
    $s = strtolower((string)$sev);
    [$fg, $bg, $label] = $map[$s] ?? ['#6c757d', '#f1f1f1', ucfirst($s ?: 'N/A')];
    return '<span class="badge-pill" style="background:' . $bg . ';color:' . $fg . ';">' . $label . '</span>';
}

include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
  <?php include 'inc/navbar.php'; ?>
  <div class="main-content">
    <div class="welcome-banner">
      <span class="badge-pill">EMERGENCY BOARD</span>
      <h1>Emergency Board</h1>
      <p>Open requests ranked by priority — severity, blood-type rarity and time remaining. Highest first.</p>
    </div>

    <div class="quick-card" style="text-align:left;">
      <?php if (empty($requests)): ?>
        <div style="text-align:center; padding:1.5rem; color:#666;">
          <i class="fas fa-check-circle" style="color:#2f8f5b; font-size:1.6rem;"></i>
          <h3 style="margin:.5rem 0 0;">No open requests</h3>
          <p>Every request is currently fulfilled or cancelled.</p>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead><tr style="text-align:left; background:#f9f9f9;">
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Priority</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Patient</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Severity</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Time left</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Hospital</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Status</th>
            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Action</th>
          </tr></thead>
          <tbody>
          <?php foreach ($requests as $req):
              $score = (int)($req['priority_score'] ?? 0);
              // Colour the score chip by band.
              $scoreBg = $score >= 80 ? '#cc0000' : ($score >= 50 ? '#b8860b' : '#3f6b7a');
              // Deadline for the countdown = created_at + required_within_hours.
              $deadlineTs = '';
              if (!empty($req['required_within_hours']) && !empty($req['created_at'])) {
                  $deadlineTs = strtotime($req['created_at']) + ((int)$req['required_within_hours'] * 3600);
              }
          ?>
            <tr style="border-bottom:1px solid #f0f0f0;">
              <td style="padding:10px;"><span class="badge-pill" style="background:<?= $scoreBg ?>;color:#fff;font-weight:700;"><?= $score ?></span></td>
              <td style="padding:10px; font-weight:600;"><?= htmlspecialchars($req['patient_name']) ?></td>
              <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($req['blood_group']) ?></span></td>
              <td style="padding:10px;"><?= (int)$req['units_needed'] ?></td>
              <td style="padding:10px;"><?= eb_severity_badge($req['severity'] ?? '') ?></td>
              <td style="padding:10px; font-size:.9rem;">
                <?php if ($deadlineTs): ?>
                  <span class="eb-countdown" data-deadline="<?= (int)$deadlineTs ?>" style="font-weight:600;">—</span>
                <?php else: ?>
                  <span style="color:#999;">Not set</span>
                <?php endif; ?>
              </td>
              <td style="padding:10px;"><?= htmlspecialchars($req['hospital_name']) ?><br><small style="color:#888;"><?= htmlspecialchars($req['location']) ?></small></td>
              <td style="padding:10px;"><?= htmlspecialchars($req['status']) ?></td>
              <td style="padding:10px;">
                <?php if ($isOfficer): ?>
                  <a href="match_donor.php?request_id=<?= (int)$req['request_id'] ?>" class="btn btn-danger btn-sm">Match Donor</a>
                <?php else: ?>
                  <span style="color:#999; font-size:.85rem;">Officer matches</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<script>
// Live countdown to each request's deadline.
(function () {
  var els = document.querySelectorAll('.eb-countdown');
  function fmt(sec) {
    if (sec <= 0) return 'OVERDUE';
    var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
    if (h > 0) return h + 'h ' + m + 'm';
    if (m > 0) return m + 'm ' + s + 's';
    return s + 's';
  }
  function tick() {
    var now = Math.floor(Date.now() / 1000);
    els.forEach(function (el) {
      var dl = parseInt(el.getAttribute('data-deadline'), 10);
      var left = dl - now;
      el.textContent = fmt(left);
      el.style.color = left <= 0 ? '#cc0000' : (left <= 3600 ? '#b8860b' : '#2c6a4a');
    });
  }
  tick();
  setInterval(tick, 1000);
})();
</script>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
