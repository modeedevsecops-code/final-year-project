<?php
// Every notification for the signed-in user (any role), newest first.
// ?format=json returns the unread count + latest items for the bell.
require_once 'config/db.php';
bl_require_login();
$conn = $db->connection;
list($role, $uid) = bl_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_mark_all'])) {
    bl_csrf_check();
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE recipient_role = ? AND recipient_id = ? AND is_read = 0");
    mysqli_stmt_bind_param($stmt, "si", $role, $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // The bell dropdown sends the page it was on; go back there.
    header('Location: ' . (bl_safe_link($_POST['back'] ?? null) ?? 'notifications.php?cleared=1'));
    exit;
}

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json');
    $items = array_map(function ($n) {
        return ['id' => (int)$n['notification_id'], 'title' => $n['title'], 'body' => $n['body'],
                'read' => (bool)$n['is_read'], 'ago' => bl_time_ago($n['created_at']), 'type' => $n['type']];
    }, bl_notifications($conn, $role, $uid, 8));
    echo json_encode(['unread' => bl_unread_count($conn, $role, $uid), 'items' => $items]);
    exit;
}

$all = bl_notifications($conn, $role, $uid, 200);
$unread = bl_unread_count($conn, $role, $uid);
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
                <span class="badge-pill">NOTIFICATIONS</span>
                <h1>Your Notifications</h1>
                <p><?= $unread ? "$unread unread." : 'You\'re all caught up.' ?> Click a notification to open the page it's about.</p>
            </div>

            <div class="quick-card" style="text-align:left;">
                <?php if (isset($_GET['cleared'])): ?><div class="alert alert-success">All notifications marked as read.</div><?php endif; ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap;">
                    <h3 style="margin:0;">All notifications</h3>
                    <?php if ($unread): ?>
                    <form method="POST" style="margin:0;">
                        <?php bl_csrf_field(); ?>
                        <button type="submit" name="btn_mark_all" class="btn btn-outline-danger btn-sm">Mark all as read</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if (empty($all)): ?>
                    <p style="color:#888;">No notifications yet.</p>
                <?php else: foreach ($all as $n): ?>
                    <a href="notification_open.php?id=<?= (int)$n['notification_id'] ?>" class="notif-row<?= $n['is_read'] ? '' : ' unread' ?>">
                        <span class="notif-icon"><i class="fas <?= bl_notification_icon($n['type']) ?>"></i></span>
                        <span class="notif-text">
                            <strong><?= htmlspecialchars($n['title']) ?></strong>
                            <?php if ($n['body']): ?><span><?= htmlspecialchars($n['body']) ?></span><?php endif; ?>
                        </span>
                        <span class="notif-time"><?= htmlspecialchars(bl_time_ago($n['created_at'])) ?></span>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</main>
<?php include 'inc/main_js.php'; ?>
</body>
</html>
