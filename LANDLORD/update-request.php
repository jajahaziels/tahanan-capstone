<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

$landlord_id = $_SESSION['landlord_id'] ?? 0;
$request_id  = intval($_POST['request_id'] ?? 0);
$action      = $_POST['action'] ?? '';

if (!$landlord_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if (!$request_id || $action !== 'remove') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Verify this request belongs to a listing owned by this landlord
$checkStmt = $conn->prepare("
    SELECT r.ID 
    FROM requesttbl r
    JOIN listingtbl l ON r.listing_id = l.ID
    WHERE r.ID = ? AND l.landlord_id = ?
");
$checkStmt->bind_param("ii", $request_id, $landlord_id);
$checkStmt->execute();
$row = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized.']);
    exit;
}

// Mark as removed via tenant_action
$stmt = $conn->prepare("UPDATE requesttbl SET tenant_action = 'removed' WHERE ID = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Request removed successfully.']);