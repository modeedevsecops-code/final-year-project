<?php
require_once 'config/db.php';
bl_require_role(['admin', 'officer']);

// Deletes go through POST + CSRF only (a GET link could be triggered by any page).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: notices.php');
    exit;
}
bl_csrf_check();

$dbb       = new operations();
$notice_id = intval($_POST['notice_id'] ?? 0);
$notice    = $dbb->get_notice_by_id($notice_id);

// Admin can delete any alert; an officer only their own.
$officer_id = intval($_SESSION['officer_id'] ?? 0);
$allowed = $notice && ($_SESSION['role'] === 'admin' || (int)$notice['officer_id'] === $officer_id);

if ($allowed) {
    $dbb->delete_notice($notice_id);
    header('Location: notices.php?deleted=1');
} else {
    header('Location: notices.php?denied=1');
}
exit;
