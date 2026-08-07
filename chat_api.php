<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';

header('Content-Type: application/json; charset=utf-8');

$dbb = new operations();

// Basic role check (allow both supervisor & student)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send message
    $payload = $_POST;
    $supervisor_id = isset($payload['supervisor_id']) ? intval($payload['supervisor_id']) : 0;
    $student_id = isset($payload['student_id']) ? intval($payload['student_id']) : 0;
    $sender = isset($payload['sender']) ? $payload['sender'] : null; // 'supervisor' or 'student'
    $message = isset($payload['message']) ? trim($payload['message']) : '';

    if (!$supervisor_id || !$student_id || !in_array($sender, ['supervisor','student']) || $message === '') {
        echo json_encode(['success'=>false, 'error'=>'Invalid parameters']);
        exit;
    }

    // Ensure user is allowed to send: if role=supervisor ensure session id matches supervisor_id, if student ensure student id matches
    if ($role === 'supervisor' && intval($_SESSION['supervisor_id']) !== $supervisor_id) {
        echo json_encode(['success'=>false, 'error'=>'Not authorized']);
        exit;
    }
    if ($role === 'student' && intval($_SESSION['user_id']) !== $student_id) {
        echo json_encode(['success'=>false, 'error'=>'Not authorized']);
        exit;
    }

    $ok = $dbb->send_chat_message($supervisor_id, $student_id, $sender, $message);
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// GET: fetch messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $supervisor_id = isset($_GET['supervisor_id']) ? intval($_GET['supervisor_id']) : 0;
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    $mark_read = isset($_GET['mark_read']) ? $_GET['mark_read'] : null; // 'supervisor' or 'student' to mark as read for recipient

    if (!$supervisor_id || !$student_id) {
        echo json_encode(['success'=>false,'error'=>'Missing ids']);
        exit;
    }

    // Authorization: supervisors may only fetch for assigned students; students only for their assigned supervisor
    if ($role === 'supervisor') {
        if (intval($_SESSION['supervisor_id']) !== $supervisor_id) {
            echo json_encode(['success'=>false,'error'=>'Not authorized']);
            exit;
        }
    } elseif ($role === 'student') {
        if (intval($_SESSION['user_id']) !== $student_id) {
            echo json_encode(['success'=>false,'error'=>'Not authorized']);
            exit;
        }
    } else {
        echo json_encode(['success'=>false,'error'=>'Not logged in']);
        exit;
    }

    $rows = $dbb->get_chat_history($supervisor_id, $student_id);

    // Optionally mark as read for recipient
    if ($mark_read && in_array($mark_read, ['supervisor','student'])) {
        $dbb->mark_messages_read($supervisor_id, $student_id, $mark_read);
    }

    echo json_encode(['success'=>true, 'messages'=>$rows]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Invalid request']);
exit;
