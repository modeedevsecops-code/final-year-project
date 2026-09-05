<?php
session_start();
require_once 'config/db.php'; // adjust path if your db include has a different name/location

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = $db->connection;

// --- Handle stock update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stock_id  = intval($_POST['stock_id']);
    $units     = intval($_POST['units_available']);
    $threshold = intval($_POST['low_stock_threshold']);
    $admin_id  = $_SESSION['user_id'] ?? null;

    // Get the blood_type for this row before updating (needed for alert logic)
    $typeStmt = $conn->prepare("SELECT blood_type FROM blood_stock WHERE id = ?");
    $typeStmt->bind_param("i", $stock_id);
    $typeStmt->execute();
    $typeRow = $typeStmt->get_result()->fetch_assoc();
    $blood_type = $typeRow['blood_type'] ?? null;
    $typeStmt->close();

    // Update the stock row
    $stmt = $conn->prepare("UPDATE blood_stock SET units_available = ?, low_stock_threshold = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("iiii", $units, $threshold, $admin_id, $stock_id);
    $stmt->execute();
    $stmt->close();

    if ($blood_type) {
        if ($units < $threshold) {
            // Below threshold: create an active alert if one doesn't already exist
            $checkStmt = $conn->prepare("SELECT id FROM stock_alerts WHERE blood_type = ? AND status = 'active'");
            $checkStmt->bind_param("s", $blood_type);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if (!$existing) {
                $insertStmt = $conn->prepare("INSERT INTO stock_alerts (blood_type, units_at_alert, threshold, status) VALUES (?, ?, ?, 'active')");
                $insertStmt->bind_param("sii", $blood_type, $units, $threshold);
                $insertStmt->execute();
                $insertStmt->close();
            }
        } else {
            // Back above threshold: resolve any active alert for this type
            $resolveStmt = $conn->prepare("UPDATE stock_alerts SET status = 'resolved', resolved_at = NOW() WHERE blood_type = ? AND status = 'active'");
            $resolveStmt->bind_param("s", $blood_type);
            $resolveStmt->execute();
            $resolveStmt->close();
        }
    }

    header("Location: manage_blood_stock.php?updated=1");
    exit();
}

// --- Fetch all stock rows ---
$result = $conn->query("SELECT * FROM blood_stock ORDER BY blood_type");
$stock_rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// --- Fetch active alerts ---
$alertResult = $conn->query("SELECT * FROM stock_alerts WHERE status = 'active' ORDER BY created_at DESC");
$active_alerts = $alertResult ? $alertResult->fetch_all(MYSQLI_ASSOC) : [];

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="main-content">

    <div class="welcome-banner">
        <span class="badge-pill">BLOOD STOCK</span>
        <h1>Manage Blood Stock</h1>
        <p>Update available units per blood type. Alerts fire automatically when stock drops below threshold.</p>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div style="background:#e6f7ee; color:#0a7a3d; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:600;">
            Stock updated successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($active_alerts)): ?>
    <div class="quick-card" style="text-align:left; border-left:4px solid #d32f2f; margin-bottom:24px;">
        <h3 style="color:#d32f2f; margin-bottom:0.75rem;"><i class="fas fa-triangle-exclamation"></i> Active Low Stock Alerts</h3>
        <?php foreach ($active_alerts as $alert): ?>
            <div style="padding:8px 0; border-bottom:1px solid #f0f0f0; text-align:left;">
                <strong><?= htmlspecialchars($alert['blood_type']) ?></strong>
                — <?= (int)$alert['units_at_alert'] ?> units left (threshold: <?= (int)$alert['threshold'] ?>)
                <span style="color:#888; font-size:0.85rem;"> · since <?= htmlspecialchars($alert['created_at']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="quick-card" style="text-align:left;">
        <h3 style="margin-bottom:1rem;">Current Stock Levels</h3>
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f9f9f9; text-align:left;">
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Blood Type</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Units Available</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Low Stock Threshold</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Last Updated</th>
                    <th style="padding:10px; font-size:0.85rem; color:#6c757d;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_rows as $row):
                    $isLow = $row['units_available'] < $row['low_stock_threshold'];
                ?>
                <tr style="border-bottom:1px solid #f0f0f0; <?= $isLow ? 'background:#fff5f5;' : '' ?>">
                    <form method="POST">
                        <td style="padding:10px; font-weight:700; color:<?= $isLow ? '#d32f2f' : '#7a0000' ?>;">
                            <?= htmlspecialchars($row['blood_type']) ?>
                            <?php if ($isLow): ?><i class="fas fa-triangle-exclamation" style="margin-left:6px;"></i><?php endif; ?>
                        </td>
                        <td style="padding:10px;">
                            <input type="hidden" name="stock_id" value="<?= $row['id'] ?>">
                            <input type="number" name="units_available" value="<?= $row['units_available'] ?>" min="0" style="width:80px; padding:6px; border:1px solid #ddd; border-radius:6px;">
                        </td>
                        <td style="padding:10px;">
                            <input type="number" name="low_stock_threshold" value="<?= $row['low_stock_threshold'] ?>" min="0" style="width:80px; padding:6px; border:1px solid #ddd; border-radius:6px;">
                        </td>
                        <td style="padding:10px; color:#6c757d; font-size:0.9rem;"><?= htmlspecialchars($row['last_updated']) ?></td>
                        <td style="padding:10px;">
                            <button type="submit" style="background:#7a0000; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">Update</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>