<?php
header('Content-Type: application/json');
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_POST['lease_id'], $_POST['action'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$lease_id = (int) $_POST['lease_id'];
$action   = $_POST['action'];

/* =========================
   VERIFY OWNERSHIP
========================= */
$check = $conn->prepare("SELECT tenant_id, listing_id FROM leasetbl WHERE ID = ?");
$check->bind_param("i", $lease_id);
$check->execute();
$result = $check->get_result()->fetch_assoc();
$check->close();

if (!$result || $result['tenant_id'] != $_SESSION['tenant_id']) {
    echo json_encode(["success" => false, "message" => "Unauthorized action"]);
    exit;
}

$listing_id = $result['listing_id'];

/* =========================
   ACTION HANDLER
========================= */
switch ($action) {

    case 'renew':
        $insert = $conn->prepare("
            INSERT INTO lease_renewaltbl (lease_id, requested_date, status)
            VALUES (?, NOW(), 'pending')
        ");
        $insert->bind_param("i", $lease_id);

        if ($insert->execute()) {
            echo json_encode(["success" => true, "message" => "Lease renewal request submitted."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to submit renewal request."]);
        }
        $insert->close();
        exit;

    case 'terminate':
        $reason = trim($_POST['reason'] ?? '');
        if (empty($reason)) {
            echo json_encode(["success" => false, "message" => "Reason is required."]);
            exit;
        }

        if (ob_get_length()) ob_clean();

        // ✅ FIX 1: Update BOTH status AND lease_status columns
        $update = $conn->prepare("
            UPDATE leasetbl
            SET status = 'terminated', lease_status = 'terminated'
            WHERE ID = ?
        ");
        $update->bind_param("i", $lease_id);

        // ✅ Insert termination request record
        $insert = $conn->prepare("
            INSERT INTO lease_terminationstbl (lease_id, terminated_by, reason, landlord_status)
            VALUES (?, 'tenant', ?, 'pending')
        ");
        $insert->bind_param("is", $lease_id, $reason);

        // ✅ FIX 2: Set listing back to available
        $listingUpdate = $conn->prepare("
            UPDATE listingtbl
            SET availability = 'available'
            WHERE ID = ?
        ");
        $listingUpdate->bind_param("i", $listing_id);

        // ✅ FIX 3: Remove from renttbl
        $rentDelete = $conn->prepare("
            DELETE FROM renttbl WHERE lease_id = ?
        ");
        $rentDelete->bind_param("i", $lease_id);

        if (
            $update->execute() &&
            $insert->execute() &&
            $listingUpdate->execute() &&
            $rentDelete->execute()
        ) {
            $update->close();
            $insert->close();
            $listingUpdate->close();
            $rentDelete->close();
            echo json_encode([
                "success" => true,
                "message" => "Termination request submitted. Your lease has been marked as terminated."
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to submit termination request."]);
        }
        exit;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        exit;
}
?>