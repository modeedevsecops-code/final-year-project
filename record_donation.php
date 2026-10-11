<?php
// Officer records a walk-in donation (not tied to a request): the member's
// units go into the officer's own bank stock. Matched donations for a request
// are confirmed from match_donor.php instead.
require_once 'config/db.php';
bl_require_role('officer');

$conn       = $db->connection;
$ops        = new operations();
$officer_id = intval($_SESSION['officer_id'] ?? 0);
$error      = null;

$bres = mysqli_query($conn, "SELECT bb.bank_id, bb.name FROM hospital_workers hw
                             LEFT JOIN blood_banks bb ON bb.bank_id = hw.blood_bank_id
                             WHERE hw.worker_id = $officer_id");
$bank = $bres ? mysqli_fetch_assoc($bres) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_record_donation'])) {
    bl_csrf_check();
    $lookup = trim($_POST['member_lookup'] ?? '');
    $col    = strpos($lookup, '@') !== false ? 'email' : 'member_code';
    $lookup = $col === 'email' ? strtolower($lookup) : strtoupper($lookup);
    $stmt = mysqli_prepare($conn, "SELECT member_id FROM members WHERE $col = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $lookup);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$found) {
        $error = 'No member found with that email or Member ID.';
    } else {
        $result = $ops->process_donation($found['member_id'], null, $officer_id, (int)($_POST['units'] ?? 0));
        if ($result['success']) {
            $_SESSION['flash_record'] = $result['message'];
            header('Location: record_donation.php');
            exit;
        }
        $error = $result['message'];
    }
}
$flash = $_SESSION['flash_record'] ?? null;
unset($_SESSION['flash_record']);

// Recent walk-in donations at this bank.
$recent = [];
if ($bank && $bank['bank_id']) {
    $res = mysqli_query($conn, "SELECT d.donation_date, d.blood_group, d.units, m.name, m.member_code, hw.name AS officer_name
                                FROM donations d
                                LEFT JOIN members m ON m.member_id = d.donor_id
                                LEFT JOIN hospital_workers hw ON hw.worker_id = d.officer_id
                                WHERE d.blood_bank_id = " . (int)$bank['bank_id'] . " AND d.request_id IS NULL
                                ORDER BY d.donation_id DESC LIMIT 10");
    $recent = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}
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
                <span class="badge-pill">RECORD DONATION</span>
                <h1>Record a Walk-in Donation</h1>
                <p>For members who donate without a matching request. The units are added to
                   <strong><?= htmlspecialchars($bank['name'] ?? 'your bank') ?></strong>'s stock under the member's blood group.
                   To donate for a specific patient, use <a href="blood_requests.php" style="color:#fff; text-decoration:underline;">Blood Requests &rarr; Match Donor</a>.</p>
            </div>

            <?php if (empty($bank['bank_id'])): ?>
                <div class="alert alert-warning">Your officer account isn't linked to a blood bank yet. Ask an administrator to assign one before recording donations.</div>
            <?php else: ?>

            <div class="quick-card" style="text-align:left; margin-bottom:24px;">
                <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="POST" class="row g-3 align-items-end"
                      onsubmit="return confirm('Record this donation and add it to your bank\'s stock?');">
                    <?php bl_csrf_field(); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="member_lookup">Member email or Member ID</label>
                        <input type="text" class="form-control" id="member_lookup" name="member_lookup" required
                               placeholder="e.g. zainab@bloodlink.test or BL-KD-001"
                               value="<?= $error ? htmlspecialchars($_POST['member_lookup'] ?? '') : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold" for="units">Units</label>
                        <input type="number" class="form-control" id="units" name="units" min="1" max="<?= BL_MAX_UNITS_PER_DONATION ?>" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="btn_record_donation" class="btn btn-danger w-100">Record Donation</button>
                    </div>
                </form>
                <p class="text-muted small mt-3 mb-0">The 56-day rule is checked automatically. The member's blood group comes from their profile.</p>
            </div>

            <div class="quick-card" style="text-align:left;">
                <h3 style="margin-bottom:1rem;">Recent walk-in donations at this bank</h3>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f9f9f9; text-align:left;">
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Date</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Member</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Recorded by</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="5" style="padding:16px; color:#888;">No walk-in donations recorded here yet.</td></tr>
                        <?php else: foreach ($recent as $r): ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px; color:#6c757d; font-size:.9rem;"><?= htmlspecialchars(date('d M Y', strtotime($r['donation_date']))) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($r['name'] ?? '(deleted member)') ?> <span style="color:#888; font-size:.82rem;"><?= htmlspecialchars($r['member_code'] ?? '') ?></span></td>
                                <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($r['blood_group']) ?></span></td>
                                <td style="padding:10px;"><?= (int)$r['units'] ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($r['officer_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
