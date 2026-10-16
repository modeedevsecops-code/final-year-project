<?php
// Officer donor screening at the bank. Find the member, go through the health
// questionnaire with them (pre-filled from their online self-check), record
// vital signs and the rapid tests for blood-borne infections, and BloodLink
// decides: eligible, deferred until a date, or permanently deferred. A donation
// can only be recorded after an eligible screening on the same day.
require_once 'config/db.php';
bl_require_role('officer');

$conn       = $db->connection;
$ops        = new operations();
$officer_id = intval($_SESSION['officer_id'] ?? 0);
$request_id = intval($_GET['request_id'] ?? $_POST['request_id'] ?? 0);
$errors     = [];

$bres = mysqli_query($conn, "SELECT bb.bank_id, bb.name FROM hospital_workers hw
                             LEFT JOIN blood_banks bb ON bb.bank_id = hw.blood_bank_id WHERE hw.worker_id = $officer_id");
$bank = $bres ? mysqli_fetch_assoc($bres) : null;

// Find the member: by id (from Match Donor) or by email / Member ID.
$member = null;
$lookup = trim($_GET['lookup'] ?? '');
if (!empty($_GET['member_id']) || !empty($_POST['member_id'])) {
    $member = $ops->get_donor_by_id(intval($_GET['member_id'] ?? $_POST['member_id']));
} elseif ($lookup !== '') {
    $col = strpos($lookup, '@') !== false ? 'email' : 'member_code';
    $val = $col === 'email' ? strtolower($lookup) : strtoupper($lookup);
    $stmt = mysqli_prepare($conn, "SELECT member_id FROM members WHERE $col = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $val);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($found) $member = $ops->get_donor_by_id($found['member_id']);
    else $errors[] = 'No member found with that email or Member ID.';
}
$mid = $member ? (int)$member['member_id'] : 0;

if ($member && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_screen'])) {
    bl_csrf_check();
    if (empty($bank['bank_id'])) {
        $errors[] = 'Your officer account is not linked to a blood bank. Ask an administrator to assign one.';
    } else {
        $in = ['answers' => (array)($_POST['answers'] ?? [])];
        foreach (['sex', 'genotype', 'weight_kg', 'hemoglobin_g_dl', 'bp_systolic', 'bp_diastolic', 'pulse_bpm', 'temperature_c', 'notes'] as $k) $in[$k] = trim($_POST[$k] ?? '');
        foreach (array_keys(BL_TTI_TESTS) as $k) $in[$k] = $_POST[$k] ?? '';
        [$sid, $outcome, $reasons, $until, $errors] = bl_save_screening($conn, $mid, $in, $officer_id, $bank['bank_id']);
        if (!$errors) {
            // Tell the member. Infection test results are never put in a notification.
            $tti = (bool)preg_match('/Reactive|Malaria parasites/', implode(' ', $reasons));
            if ($outcome === 'eligible') {
                bl_notify($conn, 'member', $mid, 'screening', 'You passed today\'s health screening',
                    'Thank you — you are cleared to donate today at ' . $bank['name'] . '.', 'screening.php');
            } elseif ($outcome === 'temporary_deferral') {
                bl_notify($conn, 'member', $mid, 'screening', 'You can donate again from ' . date('j M Y', strtotime($until)),
                    $tti ? 'Please speak to the team at ' . $bank['name'] . ' about your screening — they will explain in private.'
                         : 'Today\'s screening found: ' . implode('; ', $reasons) . '.', 'screening.php');
            } else {
                bl_notify($conn, 'member', $mid, 'screening', 'About your health screening',
                    'Please contact ' . $bank['name'] . ' for a confidential discussion of your screening result.', 'screening.php');
            }
            $_SESSION['flash_screen'] = ['outcome' => $outcome, 'reasons' => $reasons, 'until' => $until, 'name' => $member['name']];
            if ($outcome === 'eligible' && $request_id) {
                $_SESSION['flash_match'] = $member['name'] . ' passed today\'s health screening — you can now confirm the donation.';
                header('Location: match_donor.php?request_id=' . $request_id);
                exit;
            }
            header('Location: screen_donor.php?member_id=' . $mid . ($request_id ? '&request_id=' . $request_id : ''));
            exit;
        }
    }
}
$flash = $_SESSION['flash_screen'] ?? null;
unset($_SESSION['flash_screen']);

$deferral = $mid ? bl_active_deferral($conn, $mid) : null;
$cleared  = $mid ? bl_screening_for_donation($conn, $mid) : null;
$self     = $mid ? bl_latest_screening($conn, $mid, 'self') : null;
$eligible56 = $member ? $ops->is_donor_eligible($member['last_donation_date']) : true;

// Pre-fill: the posted values on an error, otherwise the member's self-check from the last 30 days.
if ($errors && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sf = ['sex' => $_POST['sex'] ?? '', 'genotype' => $_POST['genotype'] ?? '', 'answers' => (array)($_POST['answers'] ?? [])];
} elseif ($self && strtotime($self['screened_at']) > strtotime('-30 days')) {
    $sf = ['sex' => $self['sex'], 'genotype' => $self['genotype'], 'answers' => json_decode($self['answers'], true) ?: []];
} else {
    $sf = [];
}
$p = function ($k) { return htmlspecialchars($_POST[$k] ?? ''); };

// Recent screenings at this bank.
$recent = [];
if (!empty($bank['bank_id'])) {
    $res = mysqli_query($conn, "SELECT s.screening_id, s.screened_at, s.outcome, s.deferred_until, m.member_id, m.name, m.member_code, m.blood_group, hw.name AS officer_name
                                FROM donor_screenings s
                                JOIN members m ON m.member_id = s.member_id
                                LEFT JOIN hospital_workers hw ON hw.worker_id = s.officer_id
                                WHERE s.source = 'officer' AND s.blood_bank_id = " . (int)$bank['bank_id'] . "
                                ORDER BY s.screening_id DESC LIMIT 10");
    $recent = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}
$outcome_badge = ['eligible' => ['bl-badge-green', 'Eligible'], 'temporary_deferral' => ['bl-badge-amber', 'Deferred'], 'permanent_deferral' => ['bl-badge-red', 'Permanently deferred']];
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
      <span class="badge-pill">DONOR SCREENING</span>
      <h1><?= $member ? 'Screen ' . htmlspecialchars($member['name']) : 'Screen a donor' ?></h1>
      <p>Every donor is screened on the day before a donation is recorded: the health questionnaire, vital signs and the rapid tests for HIV, hepatitis B and C, syphilis and malaria. BloodLink applies the deferral rules and keeps deferred donors out of matching.</p>
    </div>

    <?php if (empty($bank['bank_id'])): ?>
      <div class="bl-notice bl-notice-warn"><i class="fas fa-triangle-exclamation"></i><div>Your officer account isn't linked to a blood bank yet. Ask an administrator to assign one before screening donors.</div></div>
    <?php endif; ?>

    <?php if ($flash): ?>
      <?php if ($flash['outcome'] === 'eligible'): ?>
        <div class="bl-outcome bl-outcome-ok"><i class="fas fa-circle-check"></i><div>
          <h3><?= htmlspecialchars($flash['name']) ?> is eligible to donate today</h3>
          <p>Record the donation from <a href="blood_requests.php">Blood Requests → Match Donor</a>, or as a <a href="record_donation.php?member=<?= urlencode($member['member_code'] ?? '') ?>">walk-in donation</a>.</p>
        </div></div>
      <?php elseif ($flash['outcome'] === 'temporary_deferral'): ?>
        <div class="bl-outcome bl-outcome-temp"><i class="fas fa-hourglass-half"></i><div>
          <h3><?= htmlspecialchars($flash['name']) ?> is deferred until <?= htmlspecialchars(date('j M Y', strtotime($flash['until']))) ?></h3>
          <ul><?php foreach ($flash['reasons'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul>
          <p style="margin-top:.4rem;">They have been notified and won't appear in donor matching until then.</p>
        </div></div>
      <?php else: ?>
        <div class="bl-outcome bl-outcome-perm"><i class="fas fa-hand"></i><div>
          <h3><?= htmlspecialchars($flash['name']) ?> is permanently deferred</h3>
          <ul><?php foreach ($flash['reasons'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul>
          <p style="margin-top:.4rem;">Refer the donor for confidential counselling and confirmatory testing. The notification sent to them does not mention test results.</p>
        </div></div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!$member): ?>
      <section class="bl-card">
        <h2 class="bl-card-title" style="margin-bottom:.85rem;"><i class="fas fa-magnifying-glass"></i> Find the donor</h2>
        <?= $errors ? bl_errors_html($errors) : '' ?>
        <form method="GET" class="bl-fields">
          <div class="bl-field" style="grid-column: span 2;">
            <label for="lookup">Member email or Member ID</label>
            <input type="text" id="lookup" name="lookup" required placeholder="e.g. zainab@bloodlink.test or BL-KD-001" value="<?= htmlspecialchars($lookup) ?>">
          </div>
          <div class="bl-field" style="align-self:end;"><button type="submit" class="bl-btn bl-btn-primary" style="width:100%;"><i class="fas fa-arrow-right"></i> Start screening</button></div>
        </form>
      </section>
    <?php else: ?>

      <section class="bl-card">
        <div style="display:flex; gap:1.25rem; align-items:center; flex-wrap:wrap;">
          <div class="bl-group bl-group-lg"><?= htmlspecialchars($member['blood_group'] ?? '—') ?></div>
          <div style="flex:1; min-width:220px;">
            <h2 style="font-size:1.2rem; font-weight:700; margin:0;"><?= htmlspecialchars($member['name']) ?></h2>
            <p class="bl-card-sub"><?= htmlspecialchars($member['member_code']) ?> · <?= htmlspecialchars($member['phone']) ?> · last donation <?= $member['last_donation_date'] ? htmlspecialchars(date('j M Y', strtotime($member['last_donation_date']))) : 'never' ?></p>
          </div>
          <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <?php if ($deferral): ?>
              <span class="bl-badge <?= $deferral['outcome'] === 'permanent_deferral' ? 'bl-badge-red' : 'bl-badge-amber' ?>"><span class="bl-dot"></span> <?= htmlspecialchars(bl_deferral_label($deferral)) ?></span>
            <?php elseif ($cleared): ?>
              <span class="bl-badge bl-badge-green"><span class="bl-dot"></span> Screened today — cleared</span>
            <?php endif; ?>
            <span class="bl-badge <?= $eligible56 ? 'bl-badge-green' : 'bl-badge-amber' ?>"><?= $eligible56 ? '56-day rule OK' : 'Inside the 56-day window' ?></span>
          </div>
        </div>
        <?php if ($deferral): ?>
          <div class="bl-notice bl-notice-warn" style="margin:1rem 0 0;"><i class="fas fa-triangle-exclamation"></i><div>
            <strong><?= $deferral['source'] === 'self' ? 'From the member\'s own self-check' : 'From a screening on ' . htmlspecialchars(date('j M Y', strtotime($deferral['screened_at']))) ?>:</strong>
            <?= htmlspecialchars($deferral['deferral_reasons'] ?? '') ?>.
            <?= $deferral['outcome'] === 'permanent_deferral' && $deferral['source'] === 'officer' ? 'A permanent deferral stays in place.' : 'A new screening that finds the donor eligible lifts it.' ?>
          </div></div>
        <?php endif; ?>
        <?php if (!$eligible56): ?>
          <div class="bl-notice bl-notice-info" style="margin:1rem 0 0;"><i class="fas fa-circle-info"></i><div>This member donated less than 56 days ago, so a donation can't be recorded yet even if they pass screening.</div></div>
        <?php endif; ?>
      </section>

      <form method="POST">
        <?php bl_csrf_field(); ?>
        <input type="hidden" name="member_id" value="<?= $mid ?>">
        <?php if ($request_id): ?><input type="hidden" name="request_id" value="<?= $request_id ?>"><?php endif; ?>
        <?= $errors ? bl_errors_html($errors) : '' ?>

        <div class="bl-grid bl-grid-main">
          <section class="bl-card" style="margin-bottom:0;">
            <div class="bl-card-head">
              <div>
                <h2 class="bl-card-title"><i class="fas fa-clipboard-question"></i> 1. Health questionnaire</h2>
                <p class="bl-card-sub"><?= ($self && $sf && !$errors) ? 'Pre-filled from the member\'s self-check on ' . htmlspecialchars(date('j M Y', strtotime($self['screened_at']))) . ' — confirm each answer with them.' : 'Ask the donor each question.' ?></p>
              </div>
            </div>
            <?php include 'inc/screening_questions.php'; ?>
          </section>

          <div>
            <section class="bl-card">
              <h2 class="bl-card-title" style="margin-bottom:.85rem;"><i class="fas fa-heart-pulse"></i> 2. Vital signs</h2>
              <div class="bl-fields" style="grid-template-columns:1fr 1fr;">
                <div class="bl-field"><label for="weight_kg">Weight <small>kg · min 50</small></label><input type="number" step="0.1" min="20" max="250" id="weight_kg" name="weight_kg" required value="<?= $p('weight_kg') ?>"></div>
                <div class="bl-field"><label for="hemoglobin_g_dl">Haemoglobin <small>g/dL · F 12.5, M 13</small></label><input type="number" step="0.1" min="3" max="25" id="hemoglobin_g_dl" name="hemoglobin_g_dl" required value="<?= $p('hemoglobin_g_dl') ?>"></div>
                <div class="bl-field"><label for="bp_systolic">BP systolic <small>100–180</small></label><input type="number" min="50" max="260" id="bp_systolic" name="bp_systolic" required value="<?= $p('bp_systolic') ?>"></div>
                <div class="bl-field"><label for="bp_diastolic">BP diastolic <small>60–100</small></label><input type="number" min="30" max="160" id="bp_diastolic" name="bp_diastolic" required value="<?= $p('bp_diastolic') ?>"></div>
                <div class="bl-field"><label for="pulse_bpm">Pulse <small>beats/min · 50–100</small></label><input type="number" min="20" max="220" id="pulse_bpm" name="pulse_bpm" required value="<?= $p('pulse_bpm') ?>"></div>
                <div class="bl-field"><label for="temperature_c">Temperature <small>°C · max 37.5</small></label><input type="number" step="0.1" min="30" max="45" id="temperature_c" name="temperature_c" required value="<?= $p('temperature_c') ?>"></div>
              </div>
            </section>
            <section class="bl-card" style="margin-bottom:0;">
              <h2 class="bl-card-title" style="margin-bottom:.35rem;"><i class="fas fa-vial"></i> 3. Rapid tests</h2>
              <p class="bl-card-sub" style="margin-bottom:.5rem;">Blood-borne infections that can pass to a patient through transfusion.</p>
              <?php foreach (BL_TTI_TESTS as $k => $label): $cur = $_POST[$k] ?? ''; ?>
                <div class="bl-q">
                  <div class="bl-q-text"><?= htmlspecialchars($label) ?></div>
                  <div class="bl-seg" role="radiogroup" aria-label="<?= htmlspecialchars($label) ?>">
                    <input type="radio" id="<?= $k ?>-neg" name="<?= $k ?>" value="negative" required <?= $cur === 'negative' ? 'checked' : '' ?>><label for="<?= $k ?>-neg">Negative</label>
                    <input type="radio" id="<?= $k ?>-pos" name="<?= $k ?>" value="positive" class="is-flag" <?= $cur === 'positive' ? 'checked' : '' ?>><label for="<?= $k ?>-pos">Reactive</label>
                  </div>
                </div>
              <?php endforeach; ?>
              <div class="bl-field" style="margin-top:.85rem;"><label for="notes">Notes <small>(optional, officers only)</small></label><textarea id="notes" name="notes" rows="2" maxlength="500"><?= $p('notes') ?></textarea></div>
            </section>
          </div>
        </div>

        <div class="bl-form-actions" style="margin-bottom:1.5rem;">
          <button type="submit" name="btn_screen" class="bl-btn bl-btn-primary"><i class="fas fa-stethoscope"></i> Save screening</button>
          <a href="screen_donor.php" class="bl-btn bl-btn-outline">Screen someone else</a>
          <?php if ($request_id): ?><a href="match_donor.php?request_id=<?= $request_id ?>" class="bl-btn bl-btn-ghost">Back to Match Donor</a><?php endif; ?>
        </div>
      </form>
    <?php endif; ?>

    <section class="bl-card">
      <div class="bl-card-head"><h2 class="bl-card-title"><i class="fas fa-list-check"></i> Recent screenings at <?= htmlspecialchars($bank['name'] ?? 'your bank') ?></h2></div>
      <?php if (empty($recent)): ?>
        <div class="bl-empty"><i class="fas fa-stethoscope"></i><strong>No screenings recorded here yet</strong></div>
      <?php else: ?>
        <div class="bl-table-wrap">
          <table class="bl-table bl-table-cards">
            <thead><tr><th>When</th><th>Donor</th><th>Group</th><th>Outcome</th><th>Screened by</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): [$cls, $lbl] = $outcome_badge[$r['outcome']]; ?>
              <tr>
                <td data-label="When"><?= htmlspecialchars(date('j M Y, H:i', strtotime($r['screened_at']))) ?></td>
                <td data-label="Donor"><a href="screen_donor.php?member_id=<?= (int)$r['member_id'] ?>"><strong><?= htmlspecialchars($r['name']) ?></strong></a> <span style="color:#888; font-size:.8rem;"><?= htmlspecialchars($r['member_code']) ?></span></td>
                <td data-label="Group"><span class="bl-group bl-group-sm"><?= htmlspecialchars($r['blood_group'] ?? '—') ?></span></td>
                <td data-label="Outcome"><span class="bl-badge <?= $cls ?>"><?= $lbl ?><?= $r['outcome'] === 'temporary_deferral' ? ' until ' . htmlspecialchars(date('j M', strtotime($r['deferred_until']))) : '' ?></span></td>
                <td data-label="Screened by"><?= htmlspecialchars($r['officer_name'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </div>
</div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
