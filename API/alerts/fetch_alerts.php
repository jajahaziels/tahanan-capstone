<?php
session_start();
header('Content-Type: application/json');
require_once '../../connection.php';

$user_id = $_SESSION['user_id'] ?? 0;
$user_type = $_SESSION['user_type'] ?? '';

if(!$user_id || !$user_type) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'alerts' => []]);
    exit;
}

$last_check = $_GET['last_check'] ?? 0;

$sql = "SELECT ea.*, uars.is_read 
        FROM emergency_alerts ea
        LEFT JOIN user_alert_read_status uars ON ea.id = uars.alert_id 
            AND uars.user_id = ? AND uars.user_type = ?
        WHERE ea.is_active = 1 
        AND (uars.is_read = 0 OR uars.is_read IS NULL)
        AND UNIX_TIMESTAMP(ea.created_at) > ?
        ORDER BY ea.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $user_id, $user_type, $last_check);
$stmt->execute();
$result = $stmt->get_result();

$alerts = [];
while($row = $result->fetch_assoc()) {
    $alerts[] = $row;
}

echo json_encode(['success' => true, 'alerts' => $alerts]);
?>