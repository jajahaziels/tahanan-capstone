<?php
require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['landlord_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$landlord_id    = (int) $_SESSION['landlord_id'];
$action         = $_POST['action'] ?? '';
$lease_id       = (int) ($_POST['lease_id'] ?? 0);
$termination_id = (int) ($_POST['termination_id'] ?? 0);
$request_id     = (int) ($_POST['request_id'] ?? 0);

if ($action === 'cancel') {
    if (!$lease_id) { echo json_encode(['success' => false, 'message' => 'Missing lease ID.']); exit; }
    $check = $conn->prepare("SELECT ID FROM leasetbl WHERE ID = ? AND landlord_id = ? AND status = 'pending'");
    $check->bind_param("ii", $lease_id, $landlord_id);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) { echo json_encode(['success' => false, 'message' => 'Lease not found or cannot be cancelled.']); exit; }
    $check->close();
    $stmt = $conn->prepare("UPDATE leasetbl SET status = 'cancelled' WHERE ID = ?");
    $stmt->bind_param("i", $lease_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Lease cancelled successfully.']);
    exit;
}

if ($action === 'approve_termination') {
    if (!$lease_id || !$termination_id) { echo json_encode(['success' => false, 'message' => 'Missing parameters.']); exit; }
    $check = $conn->prepare("SELECT listing_id FROM leasetbl WHERE ID = ? AND landlord_id = ?");
    $check->bind_param("ii", $lease_id, $landlord_id);
    $check->execute();
    $lease = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$lease) { echo json_encode(['success' => false, 'message' => 'Lease not found or unauthorized.']); exit; }
    $listing_id = $lease['listing_id'];
    $stmt = $conn->prepare("UPDATE lease_terminationstbl SET landlord_status = 'approved', responded_at = NOW() WHERE ID = ?");
    $stmt->bind_param("i", $termination_id);
    $stmt->execute(); $stmt->close();
    $stmt = $conn->prepare("UPDATE leasetbl SET status = 'terminated', lease_status = 'terminated' WHERE ID = ?");
    $stmt->bind_param("i", $lease_id);
    $stmt->execute(); $stmt->close();
    $stmt = $conn->prepare("UPDATE listingtbl SET availability = 'available' WHERE ID = ?");
    $stmt->bind_param("i", $listing_id);
    $stmt->execute(); $stmt->close();
    $stmt = $conn->prepare("DELETE FROM renttbl WHERE lease_id = ?");
    $stmt->bind_param("i", $lease_id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Termination approved. Lease ended.']);
    exit;
}

if ($action === 'reject_termination') {
    if (!$termination_id) { echo json_encode(['success' => false, 'message' => 'Missing termination ID.']); exit; }
    $stmt = $conn->prepare("UPDATE lease_terminationstbl SET landlord_status = 'rejected', responded_at = NOW() WHERE ID = ?");
    $stmt->bind_param("i", $termination_id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Termination request rejected.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
