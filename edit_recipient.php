<?php
// Edit a recipient (admin). Linked from manage_recipients.php but never
// written — created in the recipient-admin fix.
include 'config/db.php';
bl_require_role('admin');

$dbb = new operations();

$id = intval($_GET['id'] ?? ($_POST['recipient_id'] ?? 0));
if (isset($_POST['btn_update_recipient'])) {
    $dbb->update_recipient($id);
}
$recipient = $dbb->get_recipient_by_id($id);

include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Edit Recipient | BloodLink Admin</title>
</head>
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?><br><br>

    <section class="py-5">
        <div class="container bg-light p-4 rounded shadow-sm" style="max-width: 650px;">
            <h3 class="mb-4 text-danger fw-bold">🩸 Edit Recipient</h3>
            <?php $dbb->display_message(); ?>

            <?php if (!$recipient): ?>
                <div class="alert alert-warning">Recipient not found. <a href="manage_recipients.php">Back to list</a></div>
            <?php else: ?>
            <form action="edit_recipient.php?id=<?= (int)$recipient['recipient_id'] ?>" method="POST">
                <?php bl_csrf_field(); // BL-14 ?>
                <input type="hidden" name="recipient_id" value="<?= (int)$recipient['recipient_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($recipient['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($recipient['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($recipient['phone']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Blood Group</label>
                    <?php $bg = $recipient['blood_group'] ?? ''; ?>
                    <select name="blood_group" class="form-control form-select">
                        <option value="">Select Blood Group (optional)</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                            <option value="<?= $g ?>" <?= $bg === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Address / Location</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($recipient['address'] ?? '') ?>">
                </div>

                <button type="submit" name="btn_update_recipient" class="btn btn-danger">Update Recipient</button>
                <a href="manage_recipients.php" class="btn btn-outline-secondary">Cancel</a>
            </form>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
