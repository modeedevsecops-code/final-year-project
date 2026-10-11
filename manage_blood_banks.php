<?php
// Admin CRUD for blood-bank facilities (the entity the brief's ERD/locator
// centre on). Add / edit / delete banks; each is geocoded so it appears on the
// Blood Bank Locator.
require_once 'config/db.php';
bl_require_role('admin');

$ops  = new operations();
$conn = $db->connection;

// Handle add / update / delete
$ops->add_blood_bank();                              // acts on btn_add_bank
if (isset($_POST['btn_update_bank'])) {
    $ops->update_blood_bank(intval($_POST['bank_id'] ?? 0));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_delete_bank'])) {
    bl_csrf_check();
    $del = intval($_POST['bank_id']);
    if ($stmt = mysqli_prepare($conn, "DELETE FROM blood_banks WHERE bank_id = ?")) {
        mysqli_stmt_bind_param($stmt, "i", $del);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header('Location: manage_blood_banks.php?deleted=1');
    exit;
}

$editing = isset($_GET['edit']) ? $ops->get_bank_by_id(intval($_GET['edit'])) : null;
$banks   = $ops->get_blood_banks();
include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>

    <div class="main-content">
        <div class="welcome-banner">
            <span class="badge-pill">BLOOD BANKS</span>
            <h1>Registered Blood Banks</h1>
            <p>Facilities donors and recipients can locate and route to. Each address is geocoded onto the map.</p>
        </div>

        <?php $ops->display_message(); ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Blood bank deleted.</div><?php endif; ?>

        <div class="row g-3">
            <!-- Add / Edit form -->
            <div class="col-md-4">
                <div class="quick-card" style="text-align:left;">
                    <h3 style="margin-bottom:1rem;"><?= $editing ? 'Edit Bank' : 'Add a Bank' ?></h3>
                    <form method="POST" action="manage_blood_banks.php">
                        <?php bl_csrf_field(); ?>
                        <?php if ($editing): ?><input type="hidden" name="bank_id" value="<?= (int)$editing['bank_id'] ?>"><?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($editing['name'] ?? '') ?>" placeholder="e.g. Barau Dikko Teaching Hospital">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Address / Location</label>
                            <input type="text" name="address" class="form-control" required
                                   value="<?= htmlspecialchars($editing['address'] ?? '') ?>" placeholder="e.g. Lafiya Road, Kaduna">
                            <small class="text-muted">Geocoded to coordinates for the map.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control"
                                   value="<?= htmlspecialchars($editing['contact_phone'] ?? '') ?>" placeholder="080...">
                        </div>
                        <?php if ($editing): ?>
                            <button type="submit" name="btn_update_bank" class="btn btn-danger">Update Bank</button>
                            <a href="manage_blood_banks.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="btn_add_bank" class="btn btn-danger">Add Bank</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- List -->
            <div class="col-md-8">
                <div class="quick-card" style="text-align:left;">
                    <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f9f9f9; text-align:left;">
                                <th style="padding:10px; font-size:.85rem; color:#6c757d;">Name</th>
                                <th style="padding:10px; font-size:.85rem; color:#6c757d;">Address</th>
                                <th style="padding:10px; font-size:.85rem; color:#6c757d;">Coords</th>
                                <th style="padding:10px; font-size:.85rem; color:#6c757d;">Units</th>
                                <th style="padding:10px; font-size:.85rem; color:#6c757d;">Officers</th>
                                <th style="padding:10px; font-size:.85rem; color:#6c757d;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($banks)): ?>
                                <tr><td colspan="6" style="padding:14px; color:#888;">No banks yet.</td></tr>
                            <?php else: foreach ($banks as $b): ?>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td style="padding:10px; font-weight:600;"><?= htmlspecialchars($b['name']) ?></td>
                                    <td style="padding:10px; font-size:.9rem;"><?= htmlspecialchars($b['address']) ?></td>
                                    <td style="padding:10px; font-size:.82rem; color:#888;">
                                        <?= $b['latitude'] !== null ? htmlspecialchars(round($b['latitude'],3).', '.round($b['longitude'],3)) : '<span style="color:#c2172e;">not set</span>' ?>
                                    </td>
                                    <td style="padding:10px;"><span class="badge-pill" style="background:#fdeaea;color:#7a0000;"><?= (int)$b['total_units'] ?></span></td>
                                    <td style="padding:10px;"><?= (int)$b['officer_count'] ?></td>
                                    <td style="padding:10px; white-space:nowrap;">
                                        <a href="manage_blood_banks.php?edit=<?= (int)$b['bank_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this bank? Its stock and alerts go with it.');">
                                            <?php bl_csrf_field(); ?>
                                            <input type="hidden" name="bank_id" value="<?= (int)$b['bank_id'] ?>">
                                            <button type="submit" name="btn_delete_bank" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
