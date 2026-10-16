<?php
// Member pre-donation health check. The member answers the donor health
// questionnaire before visiting a bank; BloodLink tells them straight away
// whether something would stop them donating, and for how long. An officer
// repeats the questionnaire at the bank and adds vital signs and the
// blood-borne infection tests before any donation is recorded.
require_once 'config/db.php';
bl_require_role('member');

$conn      = $db->connection;
$ops       = new operations();
$member_id = intval($_SESSION['member_id'] ?? 0);
$me        = $ops->get_donor_by_id($member_id);
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_self_check'])) {
    bl_csrf_check();
    $in = ['sex' => $_POST['sex'] ?? '', 'genotype' => $_POST['genotype'] ?? 'unknown', 'answers' => (array)($_POST['answers'] ?? [])];
    [$sid, $outcome, $reasons, $until, $errors] = bl_save_screening($conn, $member_id, $in);
    if (!$errors) {
        header('Location: screening.php?done=1');
        exit;
    }
}

$latest_self = bl_latest_screening($conn, $member_id, 'self');
$latest_off  = bl_latest_screening($conn, $member_id, 'officer');
$deferral    = bl_active_deferral($conn, $member_id);
$eligible56  = $ops->is_donor_eligible($me['last_donation_date'] ?? null);

// Pre-fill: what was just posted (on an error), otherwise the last self-check.
$sf = $errors ? ['sex' => $_POST['sex'] ?? '', 'genotype' => $_POST['genotype'] ?? '', 'answers' => (array)($_POST['answers'] ?? [])]
              : ($latest_self ? ['sex' => $latest_self['sex'], 'genotype' => $latest_self['genotype'], 'answers' => json_decode($latest_self['answers'], true) ?: []] : []);
$self_reasons = ($latest_self && $latest_self['deferral_reasons']) ? explode('; ', $latest_self['deferral_reasons']) : [];
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
      <span class="badge-pill">HEALTH CHECK</span>
      <h1>Pre-donation health check</h1>
      <p>Answer a few health questions before you go to the blood bank. It takes about a minute and tells you straight away if anything would stop you donating. Your answers are only seen by blood bank officers.</p>
    </div>

    <?php if (isset($_GET['done']) && $latest_self): ?>
      <?php if ($latest_self['outcome'] === 'eligible'): ?>
        <div class="bl-outcome bl-outcome-ok"><i class="fas fa-circle-check"></i><div>
          <h3>Nothing in your answers stops you donating</h3>
          <p>At the blood bank an officer will confirm these answers, check your weight, haemoglobin, blood pressure, pulse and temperature, and run quick tests for HIV, hepatitis B and C, syphilis and malaria before you donate.<?= $eligible56 ? '' : ' Remember you can only donate once the 56-day recovery period has passed.' ?></p>
        </div></div>
      <?php elseif ($latest_self['outcome'] === 'temporary_deferral'): ?>
        <div class="bl-outcome bl-outcome-temp"><i class="fas fa-hourglass-half"></i><div>
          <h3>Please wait before donating — until <?= htmlspecialchars(date('j M Y', strtotime($latest_self['deferred_until']))) ?></h3>
          <p>Based on your answers you shouldn't donate yet:</p>
          <ul><?php foreach ($self_reasons as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul>
          <p style="margin-top:.4rem;">You won't be matched to requests until then. Take the check again if your answers change.</p>
        </div></div>
      <?php else: ?>
        <div class="bl-outcome bl-outcome-perm"><i class="fas fa-hand"></i><div>
          <h3>Based on your answers, donating may not be safe for you or for patients</h3>
          <ul><?php foreach ($self_reasons as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul>
          <p style="margin-top:.4rem;">Please speak to a blood bank officer or your doctor — they can advise you confidentially. You won't be matched to requests unless an officer clears you at a screening.</p>
        </div></div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="bl-grid bl-grid-main">
      <section class="bl-card" style="margin-bottom:0;">
        <div class="bl-card-head">
          <div>
            <h2 class="bl-card-title"><i class="fas fa-clipboard-question"></i> Health questionnaire</h2>
            <p class="bl-card-sub">Answer honestly — it protects you and the patient who receives your blood.</p>
          </div>
          <?php if ($latest_self): ?><span class="bl-badge bl-badge-grey">Last done <?= htmlspecialchars(date('j M Y', strtotime($latest_self['screened_at']))) ?></span><?php endif; ?>
        </div>
        <?= $errors ? bl_errors_html($errors) : '' ?>
        <form method="POST">
          <?php bl_csrf_field(); ?>
          <?php include 'inc/screening_questions.php'; ?>
          <div class="bl-form-actions">
            <button type="submit" name="btn_self_check" class="bl-btn bl-btn-primary"><i class="fas fa-check"></i> Check my answers</button>
            <a href="donation_form.php" class="bl-btn bl-btn-outline">Back to Donate Blood</a>
          </div>
        </form>
      </section>

      <div>
        <section class="bl-card">
          <h2 class="bl-card-title" style="margin-bottom:.85rem;"><i class="fas fa-heart-pulse"></i> Your donor status</h2>
          <dl class="bl-dl">
            <dt>Health screening</dt>
            <dd><?php if ($deferral): ?>
                  <span class="bl-badge <?= $deferral['outcome'] === 'permanent_deferral' ? 'bl-badge-red' : 'bl-badge-amber' ?>"><span class="bl-dot"></span> <?= htmlspecialchars(bl_deferral_label($deferral)) ?></span>
                <?php elseif ($latest_off && $latest_off['outcome'] === 'eligible'): ?>
                  <span class="bl-badge bl-badge-green"><span class="bl-dot"></span> Passed <?= htmlspecialchars(date('j M Y', strtotime($latest_off['screened_at']))) ?></span>
                <?php else: ?>
                  <span class="bl-badge bl-badge-grey"><span class="bl-dot"></span> Screened at the bank on the day</span>
                <?php endif; ?></dd>
            <dt>56-day rule</dt>
            <dd><?= $eligible56 ? 'Eligible' : 'Eligible from ' . htmlspecialchars(date('j M Y', strtotime($me['last_donation_date'] . ' +56 days'))) ?></dd>
            <dt>Blood group</dt><dd><span class="bl-group bl-group-sm"><?= htmlspecialchars($me['blood_group'] ?? '—') ?></span></dd>
          </dl>
          <?php if ($deferral && $deferral['source'] === 'officer'): ?>
            <div class="bl-notice bl-notice-info" style="margin:1rem 0 0;"><i class="fas fa-circle-info"></i><div>This was decided at a screening at the blood bank. Please contact the bank if you have questions about it.</div></div>
          <?php endif; ?>
        </section>
        <section class="bl-card" style="margin-bottom:0;">
          <h2 class="bl-card-title" style="margin-bottom:.75rem;"><i class="fas fa-vial"></i> At the blood bank</h2>
          <ul class="bl-checklist">
            <li><i class="fas fa-check-circle"></i>An officer goes over these questions with you.</li>
            <li><i class="fas fa-check-circle"></i>Weight (at least 50 kg), haemoglobin, blood pressure, pulse and temperature are checked.</li>
            <li><i class="fas fa-check-circle"></i>A small blood sample is tested for HIV, hepatitis B and C, syphilis and malaria.</li>
            <li><i class="fas fa-check-circle"></i>Only if everything is clear is your donation recorded. Any result that needs follow-up is discussed with you in private.</li>
          </ul>
        </section>
      </div>
    </div>

  </div>
</div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
