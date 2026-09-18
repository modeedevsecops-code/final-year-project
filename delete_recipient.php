<?php
// Delete a recipient (admin). POST + CSRF only — no GET deletes. Linked from
// manage_recipients.php but never written.
include 'config/db.php';
bl_require_role('admin');
bl_csrf_check(); // BL-14 — also rejects GET (only POST carries a token)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_recipients.php');
    exit;
}

$id = intval($_POST['recipient_id'] ?? 0);
if ($id > 0) {
    if ($stmt = mysqli_prepare($db->connection, "DELETE FROM recipients WHERE recipient_id = ?")) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
header('Location: manage_recipients.php?deleted=1');
exit;
