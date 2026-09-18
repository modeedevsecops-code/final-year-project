<?php
// Recipient's blood-request history. Linked from the recipient sidebar, the
// recipient dashboard, and the "request submitted" success message — but the
// page was never written (BL-16). Built in Phase 1 on the real blood_requests
// data, guarded to recipients only.
require_once 'config/db.php';
bl_require_role('recipient');

$recipientId = intval($_SESSION['recipient_id'] ?? $_SESSION['user_id'] ?? 0);
$conn = $db->connection;

// This recipient's own requests, newest first (prepared — no interpolation).
$requests = [];
if ($stmt = mysqli_prepare($conn,
        "SELECT request_id, patient_name, blood_group, units_needed, hospital_name,
                location, urgency_level, status, created_at
         FROM blood_requests
         WHERE recipient_id = ?
         ORDER BY created_at DESC")) {
    mysqli_stmt_bind_param($stmt, "i", $recipientId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $requests[] = $row; }
    mysqli_stmt_close($stmt);
}

// Any donations actually received against those requests (the "You received X
// from Y" view) — reuse the operations helper that already existed unused.
$ops = new operations();
$received = $ops->get_recipient_history($recipientId);

$statusStyle = [
    'Pending'   => 'background:#fff4e5;color:#a9660a;',
    'Approved'  => 'background:#e8f1fb;color:#1a5fb4;',
    'Fulfilled' => 'background:#eaf3ee;color:#2c6a4a;',
    'Cancelled' => 'background:#f1f1f1;color:#777;',
];
$urgencyStyle = [
    'Critical Emergency' => 'background:#fbecee;color:#c2172e;',
    'Urgent'             => 'background:#fbf3e6;color:#a9660a;',
    'Normal'             => 'background:#eef2f5;color:#3f6b7a;',
];
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<?php include 'inc/header.php'; ?>
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">

            <div class="welcome-banner">
                <span class="badge-pill">REQUEST HISTORY</span>
                <h1>Your Blood Requests</h1>
                <p>Every request you have submitted, with its current status. Fulfilled requests show the donation you received.</p>
            </div>

            <div class="quick-card" style="text-align:left;">
                <h3 style="margin-bottom:1rem;">Requests</h3>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f9f9f9; text-align:left;">
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Date</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Patient</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Hospital</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Urgency</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="7" style="padding:16px; color:#888;">
                                You haven't submitted any requests yet.
                                <a href="request_blood.php">Create one &rarr;</a>
                            </td></tr>
                        <?php else: foreach ($requests as $r):
                            $ust = $urgencyStyle[$r['urgency_level']] ?? '';
                            $sst = $statusStyle[$r['status']] ?? '';
                        ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px; color:#6c757d; font-size:.9rem;"><?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($r['patient_name']) ?></td>
                                <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($r['blood_group']) ?></span></td>
                                <td style="padding:10px;"><?= (int)$r['units_needed'] ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($r['hospital_name']) ?><br><span style="color:#888; font-size:.82rem;"><?= htmlspecialchars($r['location']) ?></span></td>
                                <td style="padding:10px;"><span class="badge-pill" style="<?= $ust ?>"><?= htmlspecialchars($r['urgency_level']) ?></span></td>
                                <td style="padding:10px;"><span class="badge-pill" style="<?= $sst ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <?php if (!empty($received)): ?>
            <div class="quick-card" style="text-align:left; margin-top:24px;">
                <h3 style="margin-bottom:1rem;">Donations Received</h3>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f9f9f9; text-align:left;">
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Date</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Donor</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($received as $d): ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px; color:#6c757d; font-size:.9rem;"><?= htmlspecialchars(date('d M Y', strtotime($d['donation_date']))) ?></td>
                                <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= htmlspecialchars($d['blood_group']) ?></span></td>
                                <td style="padding:10px;"><?= (int)$d['units'] ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($d['donor_name'] ?? 'Anonymous donor') ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($d['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top:24px;">
                <a href="request_blood.php" class="btn btn-danger">New Request</a>
                <a href="recipient_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>

        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
