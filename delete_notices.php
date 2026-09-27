<?php
require_once 'config/db.php';

// Allow both admin and officer roles
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'officer'])) {
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
            $officer_id = isset($_SESSION['officer_id']) ? intval($_SESSION['officer_id']) : 0;
            if ($notice['officer_id'] == $officer_id) {
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
