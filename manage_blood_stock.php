<?php
require_once 'config/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = $db->connection;
$ops  = new operations();
$banks = $ops->get_blood_banks();

// --- Handle stock update (scoped to one bank + type) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bl_csrf_check(); // BL-14
    $stock_id  = intval($_POST['stock_id']);
    $units     = intval($_POST['units_available']);
    $threshold = intval($_POST['low_stock_threshold']);
    $admin_id  = $_SESSION['user_id'] ?? null;

    // Look up this row's bank + type before updating (needed for alert logic).
    $typeStmt = $conn->prepare("SELECT blood_bank_id, blood_type FROM blood_stock WHERE id = ?");
    $typeStmt->bind_param("i", $stock_id);
    $typeStmt->execute();
    $typeRow = $typeStmt->get_result()->fetch_assoc();
    $typeStmt->close();
    $bank_id    = $typeRow['blood_bank_id'] ?? null;
    $blood_type = $typeRow['blood_type'] ?? null;

    $stmt = $conn->prepare("UPDATE blood_stock SET units_available = ?, low_stock_threshold = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("iiii", $units, $threshold, $admin_id, $stock_id);
    $stmt->execute();
    $stmt->close();

    if ($bank_id && $blood_type) {
        if ($units < $threshold) {
            // Raise an alert for this bank+type if one isn't already active.
            $checkStmt = $conn->prepare("SELECT id FROM stock_alerts WHERE blood_bank_id = ? AND blood_type = ? AND status = 'active'");
            $checkStmt->bind_param("is", $bank_id, $blood_type);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if (!$existing) {
                $insertStmt = $conn->prepare("INSERT INTO stock_alerts (blood_bank_id, blood_type, units_at_alert, threshold, status) VALUES (?, ?, ?, ?, 'active')");
                $insertStmt->bind_param("isii", $bank_id, $blood_type, $units, $threshold);
                $insertStmt->execute();
                $insertStmt->close();
            }
        } else {
            $resolveStmt = $conn->prepare("UPDATE stock_alerts SET status = 'resolved', resolved_at = NOW() WHERE blood_bank_id = ? AND blood_type = ? AND status = 'active'");
            $resolveStmt->bind_param("is", $bank_id, $blood_type);
            $resolveStmt->execute();
            $resolveStmt->close();
        }
    }

    header("Location: manage_blood_stock.php?bank=" . intval($bank_id) . "&updated=1");
    exit();
}

// --- Which bank are we managing? ---
$selected_bank = isset($_GET['bank']) ? intval($_GET['bank']) : (int)($banks[0]['bank_id'] ?? 0);

// --- This bank's stock rows (ordered by blood type) ---
$stock_rows = [];
if ($selected_bank) {
    $s = $conn->prepare("SELECT * FROM blood_stock WHERE blood_bank_id = ? ORDER BY blood_type");
    $s->bind_param("i", $selected_bank);
    $s->execute();
    $stock_rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
}

// --- This bank's active alerts ---
$active_alerts = [];
if ($selected_bank) {
    $a = $conn->prepare("SELECT * FROM stock_alerts WHERE blood_bank_id = ? AND status = 'active' ORDER BY created_at DESC");
    $a->bind_param("i", $selected_bank);
    $a->execute();
    $active_alerts = $a->get_result()->fetch_all(MYSQLI_ASSOC);
    $a->close();
}

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="main-content">

    <div class="welcome-banner">
        <span class="badge-pill">BLOOD STOCK</span>
        <h1>Manage Blood Stock</h1>
        <p>Units are tracked per blood bank. Pick a bank, then update units and thresholds per blood type.</p>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div style="background:#e6f7ee; color:#0a7a3d; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:600;">
            Stock updated successfully.
        </div>
    <?php endif; ?>

    <!-- Bank selector -->
    <div class="quick-card" style="text-align:left; margin-bottom:20px;">
        <form method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label for="bank" style="font-weight:600;">Blood Bank:</label>
            <select name="bank" id="bank" onchange="this.form.submit()" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px; min-width:280px;">
                <?php foreach ($banks as $b): ?>
                    <option value="<?= (int)$b['bank_id'] ?>" <?= $selected_bank == $b['bank_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?> (<?= (int)$b['total_units'] ?> units)
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-danger btn-sm">Go</button></noscript>
        </form>
    </div>

    <?php if (!empty($active_alerts)): ?>
    <div class="quick-card" style="text-align:left; border-left:4px solid #d32f2f; margin-bottom:24px;">
        <h3 style="color:#d32f2f; margin-bottom:0.75rem;"><i class="fas fa-triangle-exclamation"></i> Active Low Stock Alerts (this bank)</h3>
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
                <?php if (empty($stock_rows)): ?>
                    <tr><td colspan="5" style="padding:14px; color:#888;">No stock rows for this bank.</td></tr>
                <?php else: foreach ($stock_rows as $row):
                    $isLow = $row['units_available'] < $row['low_stock_threshold'];
                ?>
                <tr style="border-bottom:1px solid #f0f0f0; <?= $isLow ? 'background:#fff5f5;' : '' ?>">
                    <form method="POST">
                        <?php bl_csrf_field(); // BL-14 ?>
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
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>
