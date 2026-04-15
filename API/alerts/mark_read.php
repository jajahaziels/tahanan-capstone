<?php
session_start();
header('Content-Type: application/json');
require_once '../../connection.php';

$user_id = $_SESSION['user_id'] ?? 0;
$user_type = $_SESSION['user_type'] ?? '';

if(!$user_id || !$user_type) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$alert_id = $data['alert_id'];

$stmt = $conn->prepare("UPDATE user_alert_read_status SET is_read = 1, read_at = NOW() 
                        WHERE alert_id = ? AND user_id = ? AND user_type = ?");
$stmt->bind_param("iis", $alert_id, $user_id, $user_type);
$stmt->execute();

echo json_encode(['success' => true]);
?>