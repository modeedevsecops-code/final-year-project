<?php
// DB + session first, then guard, THEN header. Rewritten in the recipient-admin
// fix: the old version called $pdo (never created — the app is mysqli) and read
// columns that don't exist on recipients (id/full_name/units_required/
// hospital_name). (BL-08)
include 'config/db.php';
bl_require_role('admin');

$dbb = new operations();
$conn = $db->connection;

$recipients = [];
$res = mysqli_query($conn,
    "SELECT recipient_id, name, email, phone, blood_group, address
     FROM recipients ORDER BY recipient_id DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $recipients[] = $r; } }

include 'inc/header.php';
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
    <title>Manage Recipients | BloodLink Admin</title>
</head>
<body>
<main class="main" id="top">
    <?php include 'inc/navbar.php'; ?><br><br>

    <section class="py-5">
        <div class="container bg-light p-4 rounded shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-danger fw-bold">🩸 Manage Blood Recipients</h3>
                <a href="add_recipient.php" class="btn btn-danger">Add New Recipient</a>
            </div>

            <?php $dbb->display_message(); ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Recipient deleted.</div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Blood Group</th>
                            <th>Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recipients)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No recipients found.</td></tr>
                        <?php else: foreach ($recipients as $row): ?>
                            <tr>
                                <td><?= (int)$row['recipient_id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><span class="badge bg-danger"><?= htmlspecialchars($row['blood_group'] ?? '—') ?></span></td>
                                <td><?= htmlspecialchars($row['address'] ?? '—') ?></td>
                                <td style="white-space:nowrap;">
                                    <a href="edit_recipient.php?id=<?= (int)$row['recipient_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <form action="delete_recipient.php" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete this recipient?');">
                                        <?php bl_csrf_field(); // BL-14 ?>
                                        <input type="hidden" name="recipient_id" value="<?= (int)$row['recipient_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
