<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

$landlord_id = $_SESSION['landlord_id'] ?? 0;
$request_id  = intval($_POST['request_id'] ?? 0);
$action      = $_POST['action'] ?? '';

// For lease requests
$lease_id    = intval($_POST['lease_id'] ?? 0);
$type        = $_POST['type']   ?? '';
$status      = $_POST['status'] ?? '';

if (!$landlord_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// ─── REMOVE REQUEST (existing) ───
if ($action === 'remove') {
    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

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

    $stmt = $conn->prepare("UPDATE requesttbl SET tenant_action = 'removed' WHERE ID = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Request removed successfully.']);
    exit;
}

// ─── RENEWAL / TERMINATION ───
if ($action === 'lease_request') {
    if (!$lease_id || !$type || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
        exit;
    }

    // Verify lease belongs to this landlord
    $stmt = $conn->prepare("
        SELECT l.ID, l.listing_id 
        FROM leasetbl l
        JOIN listingtbl li ON l.listing_id = li.ID
        WHERE l.ID = ? AND li.landlord_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $lease_id, $landlord_id);
    $stmt->execute();
    $lease = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$lease) {
        echo json_encode(['success' => false, 'message' => 'Lease not found or unauthorized.']);
        exit;
    }

    if ($type === 'renewal') {
        $stmt = $conn->prepare("
            UPDATE lease_renewaltbl 
            SET landlord_status = ?, responded_at = NOW()
            WHERE lease_id = ? AND landlord_status = 'pending'
        ");
        $stmt->bind_param("si", $status, $lease_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Renewal ' . $status . ' successfully.']);
        exit;
    }

    if ($type === 'termination') {
        if ($status === 'approved') {
            $listing_id = $lease['listing_id'];
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE lease_terminationstbl SET landlord_status = 'approved', responded_at = NOW() WHERE lease_id = ? AND landlord_status = 'pending'");
                $stmt->bind_param("i", $lease_id); $stmt->execute(); $stmt->close();

                $stmt = $conn->prepare("UPDATE leasetbl SET status = 'terminated', lease_status = 'terminated' WHERE ID = ?");
                $stmt->bind_param("i", $lease_id); $stmt->execute(); $stmt->close();

                $stmt = $conn->prepare("UPDATE listingtbl SET availability = 'available' WHERE ID = ?");
                $stmt->bind_param("i", $listing_id); $stmt->execute(); $stmt->close();

                $stmt = $conn->prepare("DELETE FROM renttbl WHERE lease_id = ?");
                $stmt->bind_param("i", $lease_id); $stmt->execute(); $stmt->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Termination approved. Lease ended and listing is now available.']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
            }
        } else {
            $stmt = $conn->prepare("UPDATE lease_terminationstbl SET landlord_status = 'rejected', responded_at = NOW() WHERE lease_id = ? AND landlord_status = 'pending'");
            $stmt->bind_param("i", $lease_id); $stmt->execute(); $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Termination rejected. Lease remains active.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request type.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);