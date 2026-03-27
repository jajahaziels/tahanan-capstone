<?php
require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

// Landlord only
if (!isset($_SESSION['landlord_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$landlord_id = (int)$_SESSION['landlord_id'];
$payment_id  = (int)($_POST['payment_id'] ?? 0);
$action      = trim($_POST['action'] ?? '');

if ($payment_id <= 0 || !in_array($action, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$new_status = ($action === 'approved') ? 'paid' : 'rejected';

$stmt = $conn->prepare("
    UPDATE paymentstbl 
    SET status = ?, updated_at = NOW()
    WHERE id = ? AND landlord_id = ?
");
$stmt->bind_param("sii", $new_status, $payment_id, $landlord_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed or record not found.']);
}