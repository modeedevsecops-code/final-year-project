<?php
// Member's donation history — every donation an officer has confirmed for them,
// plus their 56-day eligibility.
include 'config/db.php';
bl_require_role('member');

$ops       = new operations();
$member_id = intval($_SESSION['member_id'] ?? 0);
$member    = $ops->get_donor_by_id($member_id);
$history   = $ops->get_donor_history($member_id);
$eligible  = $ops->is_donor_eligible($member['last_donation_date'] ?? null);
$wait      = $ops->days_until_eligible($member['last_donation_date'] ?? null);
$total_units = array_sum(array_map(function ($d) { return (int)$d['units']; }, $history));
?>
<?php include 'inc/header.php'; ?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="welcome-banner">
                <span class="badge-pill">MY DONATIONS</span>
                <h1>Your Donation History</h1>
                <p>Every donation a hospital officer has recorded for you. Thank you — each unit can help save up to three lives.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-tint"></i></div>
                        <h3><?= count($history) ?></h3>
                        <p>Donation<?= count($history) === 1 ? '' : 's' ?> recorded</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-flask"></i></div>
                        <h3><?= $total_units ?></h3>
                        <p>Unit<?= $total_units === 1 ? '' : 's' ?> given</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="quick-card">
                        <div class="quick-icon"><i class="fas fa-calendar-check"></i></div>
                        <h3><?= $eligible ? 'Eligible' : (int)$wait . ' day(s)' ?></h3>
                        <p><?= $eligible ? 'You can donate again now' : 'until you can donate again' ?></p>
                    </div>
                </div>
            </div>

            <div class="quick-card" style="text-align:left;">
                <h3 style="margin-bottom:1rem;">Donations</h3>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f9f9f9; text-align:left;">
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Date</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">For</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Blood bank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="5" style="padding:16px; color:#888;">
                                No donations recorded yet. When an officer matches you to a request and confirms it, it will appear here.
                            </td></tr>
                        <?php else: foreach ($history as $d): ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px; color:#6c757d; font-size:.9rem;"><?= htmlspecialchars(date('d M Y', strtotime($d['donation_date']))) ?></td>
                                <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($d['blood_group']) ?></span></td>
                                <td style="padding:10px;"><?= (int)$d['units'] ?></td>
                                <td style="padding:10px;">
                                    <?php if ($d['request_id']): ?>
                                        <?= htmlspecialchars($d['patient_name'] ?? 'Patient') ?>
                                        <br><span style="color:#888; font-size:.82rem;"><?= htmlspecialchars($d['hospital_name'] ?? '') ?></span>
                                    <?php else: ?>
                                        Walk-in donation <span style="color:#888; font-size:.82rem;">(added to bank stock)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px;"><?= htmlspecialchars($d['bank_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div style="margin-top:24px;">
                <a href="member_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>

        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
