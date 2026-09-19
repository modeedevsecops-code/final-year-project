<?php
require_once 'config/functions.php';  // starts session, connects DB, defines $db
$ops = new operations();

// Admins and hospital officers both manage the request queue (this page is in
// both sidebars). Was supervisor-only, which bounced admins to the login form.
if (empty($_SESSION['Active']) || !in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'], true)) {
    header('Location: login.php');
    exit;
}

$requests = $ops->get_open_blood_requests(); // Pending + Approved, most urgent first
include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="welcome-banner">
            <span class="badge-pill">REQUEST QUEUE</span>
            <h1>Blood Requests</h1>
            <p>Pending and approved requests, most urgent first. Match each to eligible, nearest donors.</p>
        </div>

        <div class="quick-card" style="text-align:left;">
            <?php if (empty($requests)): ?>
                <div style="text-align:center; padding:1.5rem; color:#666;">
                    <i class="fas fa-check-circle" style="color:#2f8f5b; font-size:1.6rem;"></i>
                    <h3 style="margin:.5rem 0 0;">No open requests</h3>
                    <p>All blood requests are currently fulfilled or cancelled.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; background:#f9f9f9;">
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Patient</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Group</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Hospital</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Urgency</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Status</th>
                            <th style="padding:10px; font-size:.85rem; color:#6c757d;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req):
                            $urgencyColor = $req['urgency_level'] === 'Critical Emergency' ? '#cc0000'
                                          : ($req['urgency_level'] === 'Urgent' ? '#b8860b' : '#2f8f5b');
                        ?>
                            <tr style="border-bottom:1px solid #f0f0f0;">
                                <td style="padding:10px;"><?php echo htmlspecialchars($req['patient_name']); ?></td>
                                <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?php echo htmlspecialchars($req['blood_group']); ?></span></td>
                                <td style="padding:10px;"><?php echo (int)$req['units_needed']; ?></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($req['hospital_name']); ?><br><small style="color:#888;"><?php echo htmlspecialchars($req['location']); ?></small></td>
                                <td style="padding:10px;"><span style="color:<?php echo $urgencyColor; ?>; font-weight:600;"><?php echo htmlspecialchars($req['urgency_level']); ?></span></td>
                                <td style="padding:10px;"><?php echo htmlspecialchars($req['status']); ?></td>
                                <td style="padding:10px;">
                                    <?php if (($_SESSION['role'] ?? '') === 'supervisor'): ?>
                                        <a href="match_donor.php?request_id=<?php echo (int)$req['request_id']; ?>" class="btn btn-danger btn-sm">Match Donor</a>
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
<?php include 'inc/main_js.php'; ?>
</body>
</html>
