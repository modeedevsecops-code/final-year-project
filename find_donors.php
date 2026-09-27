<?php
// Find Donors — search the available, compatible donor pool near a location.
// Admin/officer facing (the brief's "find nearby donors").
require_once 'config/db.php';
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','officer'], true)) {
    header('Location: login.php'); exit;
}
$ops   = new operations();
$banks = $ops->get_blood_banks();

$bg      = strtoupper(trim($_GET['blood_group'] ?? ''));
$mode    = ($_GET['mode'] ?? 'compatible') === 'exact' ? 'exact' : 'compatible';
$availOnly = isset($_GET['available_only']) ? true : (!isset($_GET['blood_group'])); // default on
$originBank = intval($_GET['origin'] ?? 0);
$olat = $olng = null; $originName = '';
foreach ($banks as $b) { if ((int)$b['bank_id'] === $originBank) { $olat=$b['latitude']; $olng=$b['longitude']; $originName=$b['name']; } }

$results = ($bg !== '') ? $ops->search_donors($bg, $mode, $availOnly, $olat, $olng) : [];
$groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
  <?php include 'inc/navbar.php'; ?>
  <div class="main-content">
    <div class="welcome-banner">
      <span class="badge-pill">FIND DONORS</span>
      <h1>Find Available Donors</h1>
      <p>Search the donor pool by blood group and location. Ranked by eligibility, compatibility, then distance.</p>
    </div>

    <div class="quick-card" style="text-align:left; margin-bottom:18px;">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label fw-bold small">Blood group needed</label>
          <select name="blood_group" class="form-control form-select" required>
            <option value="">-- Select --</option>
            <?php foreach ($groups as $g): ?><option value="<?= $g ?>" <?= $bg===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">Match</label>
          <select name="mode" class="form-control form-select">
            <option value="compatible" <?= $mode==='compatible'?'selected':'' ?>>Compatible donors</option>
            <option value="exact" <?= $mode==='exact'?'selected':'' ?>>Exact group only</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">Distance from</label>
          <select name="origin" class="form-control form-select">
            <option value="0">— (no distance)</option>
            <?php foreach ($banks as $b): ?><option value="<?= (int)$b['bank_id'] ?>" <?= $originBank===(int)$b['bank_id']?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-bold small d-block">&nbsp;</label>
          <label class="small"><input type="checkbox" name="available_only" value="1" <?= $availOnly?'checked':'' ?>> Available only</label>
        </div>
        <div class="col-md-1">
          <button class="btn btn-danger w-100">Search</button>
        </div>
      </form>
    </div>

    <?php if ($bg !== ''): ?>
    <div class="quick-card" style="text-align:left;">
      <h3 style="margin:0 0 10px;">
        <?= count($results) ?> donor(s) — <?= $mode==='exact' ? htmlspecialchars($bg).' only' : 'compatible with '.htmlspecialchars($bg) ?>
        <?= $originName ? ' · from '.htmlspecialchars($originName) : '' ?>
      </h3>
      <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr style="background:#f9f9f9; text-align:left;">
          <th style="padding:10px; font-size:.85rem; color:#6c757d;">Donor</th>
          <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
          <th style="padding:10px; font-size:.85rem; color:#6c757d;">Match</th>
          <th style="padding:10px; font-size:.85rem; color:#6c757d;">Eligibility</th>
          <th style="padding:10px; font-size:.85rem; color:#6c757d;">Availability</th>
          <?php if ($originBank): ?><th style="padding:10px; font-size:.85rem; color:#6c757d;">Distance</th><?php endif; ?>
          <th style="padding:10px; font-size:.85rem; color:#6c757d;">Contact</th>
        </tr></thead>
        <tbody>
          <?php if (empty($results)): ?>
            <tr><td colspan="7" style="padding:16px; color:#888;">No donors found for that group<?= $availOnly?' (available only)':'' ?>.</td></tr>
          <?php else: foreach ($results as $d): ?>
            <tr style="border-bottom:1px solid #f0f0f0;">
              <td style="padding:10px; font-weight:600;"><?= htmlspecialchars($d['name']) ?></td>
              <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($d['blood_group']) ?></span></td>
              <td style="padding:10px;"><span class="badge-pill" style="background:#eef2f5;color:#3f6b7a;"><?= (int)$d['compatibility_score'] ?></span></td>
              <td style="padding:10px;">
                <?php if ($d['eligible']): ?><span class="badge-pill" style="background:#eaf3ee;color:#2c6a4a;">Eligible</span>
                <?php else: ?><span class="badge-pill" style="background:#fbf3e6;color:#a9660a;">In window</span><?php endif; ?>
              </td>
              <td style="padding:10px; font-size:.85rem;">
                <?php if ($d['is_available']): ?><span style="color:#2c8a4a; font-weight:600;">● Available</span><?php else: ?><span style="color:#999;">Unavailable</span><?php endif; ?>
                <br><span style="color:#888;"><?= htmlspecialchars($d['availability_schedule']) ?> · <?= htmlspecialchars($d['contact_preference']) ?></span>
              </td>
              <?php if ($originBank): ?><td style="padding:10px;"><?= $d['distance_km']!==null ? htmlspecialchars($d['distance_km']).' km' : '<span style="color:#999">—</span>' ?></td><?php endif; ?>
              <td style="padding:10px; font-size:.9rem;"><?= htmlspecialchars($d['phone']) ?><br><span style="color:#888;"><?= htmlspecialchars($d['email']) ?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
