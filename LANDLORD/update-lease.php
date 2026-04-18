<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['landlord_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$landlord_id    = (int) $_SESSION['landlord_id'];
$action         = $_POST['action']                ?? '';
$lease_id       = (int) ($_POST['lease_id']       ?? 0);
$termination_id = (int) ($_POST['termination_id'] ?? 0);

// Helper: get lease via listingtbl (no landlord_id column needed in leasetbl)
$getLease = function($lease_id) use ($conn, $landlord_id) {
    $stmt = $conn->prepare("
        SELECT l.ID, l.listing_id, l.tenant_id, l.tenant_response
        FROM leasetbl l
        JOIN listingtbl li ON l.listing_id = li.ID
        WHERE l.ID = ? AND li.landlord_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $lease_id, $landlord_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
};

switch ($action) {

    case 'cancel':
        if (!$lease_id) {
            echo json_encode(["success" => false, "message" => "Invalid lease ID."]);
            exit;
        }
        $lease = $getLease($lease_id);
        if (!$lease) {
            echo json_encode(["success" => false, "message" => "Lease not found or unauthorized."]);
            exit;
        }
        if ($lease['tenant_response'] === 'accepted') {
            echo json_encode(["success" => false, "message" => "Cannot cancel — tenant has already accepted this lease."]);
            exit;
        }
        $stmt = $conn->prepare("UPDATE leasetbl SET status = 'cancelled', lease_status = 'cancelled' WHERE ID = ?");
        $stmt->bind_param("i", $lease_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(["success" => true, "message" => "Lease cancelled. You can now create a new lease agreement."]);
        exit;

    case 'approve_termination':
        if (!$lease_id || !$termination_id) {
            echo json_encode(["success" => false, "message" => "Invalid data."]);
            exit;
        }
        $lease = $getLease($lease_id);
        if (!$lease) {
            echo json_encode(["success" => false, "message" => "Lease not found or unauthorized."]);
            exit;
        }
        $listing_id = $lease['listing_id'];

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE lease_terminationstbl SET landlord_status = 'approved', responded_at = NOW() WHERE ID = ? AND lease_id = ?");
            $stmt->bind_param("ii", $termination_id, $lease_id);
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

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Termination approved. Lease ended and listing is now available."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Failed: " . $e->getMessage()]);
        }
        exit;

    case 'reject_termination':
        if (!$termination_id) {
            echo json_encode(["success" => false, "message" => "Invalid termination ID."]);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT lt.ID 
            FROM lease_terminationstbl lt
            JOIN leasetbl l ON lt.lease_id = l.ID
            JOIN listingtbl li ON l.listing_id = li.ID
            WHERE lt.ID = ? AND li.landlord_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $termination_id, $landlord_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(["success" => false, "message" => "Termination not found or unauthorized."]);
            exit;
        }
        $stmt = $conn->prepare("UPDATE lease_terminationstbl SET landlord_status = 'rejected', responded_at = NOW() WHERE ID = ?");
        $stmt->bind_param("i", $termination_id);
        $stmt->execute(); $stmt->close();
        echo json_encode(["success" => true, "message" => "Termination request rejected. Lease remains active."]);
        exit;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        exit;
}