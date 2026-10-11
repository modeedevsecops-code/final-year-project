<?php
// Marks one of the signed-in user's notifications as read, then opens its link.
require_once 'config/db.php';
bl_require_login();
list($role, $uid) = bl_current_user();
$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare($db->connection, "SELECT link FROM notifications WHERE notification_id = ? AND recipient_role = ? AND recipient_id = ?");
mysqli_stmt_bind_param($stmt, "isi", $id, $role, $uid);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($row) {
    $stmt = mysqli_prepare($db->connection, "UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
header('Location: ' . (bl_safe_link($row['link'] ?? null) ?? 'notifications.php'));
exit;
