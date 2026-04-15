<?php
require_once __DIR__ . '/../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$user_id         = isset($_POST['user_id'])         ? (int)$_POST['user_id']    : 0;
$user_type       = isset($_POST['user_type'])       ? trim($_POST['user_type']) : ''; // ← ADD THIS
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
$mark_all        = isset($_POST['mark_all'])        && $_POST['mark_all'] === '1';

if (!$user_id || !in_array($user_type, ['landlord', 'tenant'])) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id or user_type']);
    exit;
}

if ($mark_all || $notification_id === 0) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = ? AND is_read = 0");
    $stmt->bind_param("is", $user_id, $user_type);
} else {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type = ?");
    $stmt->bind_param("iis", $notification_id, $user_id, $user_type); // ← was "ii", now "iis"
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'affected' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();