<?php
// Donor directory. In a blood bank an officer serves the whole eligible donor
// pool, not a personal roster — so this lists every donor with their blood
// group and current 56-day eligibility. Admins and officers both use it.
require_once 'config/db.php';
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supervisor'], true)) {
    header('Location: login.php');
    exit;
}

$ops = new operations();
$conn = $db->connection;

$donors = [];
$res = mysqli_query($conn,
    "SELECT student_id, name, reg_no, email, phone, blood_group, last_donation_date
     FROM students ORDER BY name ASC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $donors[] = $r; } }

$backLink = ($_SESSION['role'] === 'admin') ? 'dashboard.php' : 'officer_dashboard.php';
include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="welcome-banner">
            <span class="badge-pill">DONORS</span>
            <h1>Donor Directory</h1>
            <p>Every registered donor with blood group and current eligibility (56-day rule).</p>
        </div>

        <div class="quick-card" style="text-align:left;">
            <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f9f9f9; text-align:left;">
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">#</th>
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">Donor Name</th>
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">Donor ID</th>
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">Blood Group</th>
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">Last Donation</th>
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">Eligibility</th>
                        <th style="padding:10px; font-size:.85rem; color:#6c757d;">Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donors)): ?>
                        <tr><td colspan="7" style="padding:16px; color:#888;">No donors registered yet.</td></tr>
                    <?php else: $i = 1; foreach ($donors as $d):
                        $eligible = $ops->is_donor_eligible($d['last_donation_date']);
                        $days = $ops->days_until_eligible($d['last_donation_date']);
                    ?>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:10px;"><?= $i++ ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($d['name']) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($d['reg_no']) ?></td>
                            <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($d['blood_group'] ?? 'N/A') ?></span></td>
                            <td style="padding:10px; color:#6c757d; font-size:.9rem;"><?= $d['last_donation_date'] ? htmlspecialchars($d['last_donation_date']) : 'Never' ?></td>
                            <td style="padding:10px;">
                                <?php if ($eligible): ?>
                                    <span class="badge-pill" style="background:#eaf3ee;color:#2c6a4a;">Eligible</span>
                                <?php else: ?>
                                    <span class="badge-pill" style="background:#fbf3e6;color:#a9660a;"><?= (int)$days ?>d to go</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px; font-size:.9rem;"><?= htmlspecialchars($d['phone']) ?><br><span style="color:#888;"><?= htmlspecialchars($d['email']) ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div style="margin-top:20px;"><a href="<?= $backLink ?>" class="btn btn-outline-secondary">Back to Dashboard</a></div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
