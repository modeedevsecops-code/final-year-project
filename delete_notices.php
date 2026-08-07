<?php
require_once 'config/db.php';

// Allow both admin and supervisor roles
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['role'] === 'admin');
$dbb = new operations();

if (isset($_GET['id'])) {
    $notice_id = intval($_GET['id']);
    $notice    = $dbb->get_notice_by_id($notice_id);

    if ($notice) {
        if ($is_admin) {
            // Admin can delete ANY alert
            $dbb->delete_notice($notice_id);
            header("Location: notices.php");
            exit;
        } else {
            // Officer can only delete their own alert
            $supervisor_id = isset($_SESSION['supervisor_id']) ? intval($_SESSION['supervisor_id']) : 0;
            if ($notice['supervisor_id'] == $supervisor_id) {
                $dbb->delete_notice($notice_id);
                header("Location: notices.php");
                exit;
            } else {
                echo "<script>alert('You are not authorized to delete this alert.'); window.location='notices.php';</script>";
                exit;
            }
        }
    } else {
        header("Location: notices.php");
        exit;
    }
} else {
    header("Location: notices.php");
    exit;
}
