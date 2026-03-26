<?php
require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

$tenant_id  = (int) ($_SESSION['tenant_id'] ?? 0);
$listing_id = (int) ($_POST['listing_id']   ?? 0);

if (!$tenant_id || !$listing_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Only allow cancelling a PENDING request that belongs to this tenant
$stmt = $conn->prepare("
    DELETE FROM requesttbl
    WHERE tenant_id  = ?
      AND listing_id = ?
      AND status     = 'pending'
    LIMIT 1
");
$stmt->bind_param("ii", $tenant_id, $listing_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Application cancelled successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'No pending application found to cancel.']);
}

$stmt->close();
?>