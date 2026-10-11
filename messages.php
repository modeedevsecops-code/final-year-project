<?php
// Admin inbox for messages sent from the public Contact page.
require_once 'config/db.php';
bl_require_role('admin');
$conn = $db->connection;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bl_csrf_check();
    $mid = intval($_POST['message_id'] ?? 0);
    if (isset($_POST['btn_mark_read'])) {
        mysqli_query($conn, "UPDATE contact_messages SET status = 'read' WHERE message_id = $mid");
        header('Location: messages.php'); exit;
    }
    if (isset($_POST['btn_delete_message'])) {
        mysqli_query($conn, "DELETE FROM contact_messages WHERE message_id = $mid");
        header('Location: messages.php?deleted=1'); exit;
    }
}

$res = mysqli_query($conn, "SELECT * FROM contact_messages ORDER BY (status = 'new') DESC, created_at DESC");
$messages = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
$new_count = count(array_filter($messages, function ($m) { return $m['status'] === 'new'; }));
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
                <span class="badge-pill">MESSAGES</span>
                <h1>Contact Messages</h1>
                <p>Messages sent from the public Contact page. <?= $new_count ? "$new_count new." : 'No new messages.' ?></p>
            </div>

            <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Message deleted.</div><?php endif; ?>

            <?php if (empty($messages)): ?>
                <div class="quick-card" style="text-align:left;"><p style="color:#888; margin:0;">No messages yet.</p></div>
            <?php else: foreach ($messages as $m): $isNew = $m['status'] === 'new'; ?>
                <div class="quick-card" style="height:auto; text-align:left; margin-bottom:14px; <?= $isNew ? 'border-left:4px solid #cc0000;' : '' ?>">
                    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <h3 style="margin:0; font-size:1.05rem;">
                            <?php if ($isNew): ?><span class="badge bg-danger me-1">NEW</span><?php endif; ?>
                            <?= htmlspecialchars($m['subject']) ?>
                        </h3>
                        <span style="color:#888; font-size:.85rem;"><?= htmlspecialchars(date('d M Y, H:i', strtotime($m['created_at']))) ?></span>
                    </div>
                    <p style="flex:none; color:#555; font-size:.9rem; margin:4px 0 8px;">From <strong><?= htmlspecialchars($m['name']) ?></strong> &lt;<?= htmlspecialchars($m['email']) ?>&gt;</p>
                    <p style="flex:none; white-space:pre-wrap; margin-bottom:12px;"><?= htmlspecialchars($m['message']) ?></p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a class="btn btn-danger btn-sm" href="mailto:<?= htmlspecialchars(rawurlencode($m['email'])) ?>?subject=<?= htmlspecialchars(rawurlencode('Re: ' . $m['subject'])) ?>">Reply by email</a>
                        <?php if ($isNew): ?>
                        <form method="POST" style="margin:0;"><?php bl_csrf_field(); ?><input type="hidden" name="message_id" value="<?= (int)$m['message_id'] ?>">
                            <button type="submit" name="btn_mark_read" class="btn btn-outline-secondary btn-sm">Mark as read</button></form>
                        <?php endif; ?>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this message?');"><?php bl_csrf_field(); ?><input type="hidden" name="message_id" value="<?= (int)$m['message_id'] ?>">
                            <button type="submit" name="btn_delete_message" class="btn btn-outline-danger btn-sm">Delete</button></form>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
