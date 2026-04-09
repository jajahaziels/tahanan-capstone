<?php
header('Content-Type: application/json');
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_POST['action'])) {
    echo json_encode(["success" => false, "message" => "Missing action"]);
    exit;
}

$action = $_POST['action'];

/* =========================
   TENANT-SIDE ACTIONS
   (renew, terminate)
========================= */
if (in_array($action, ['renew', 'terminate'])) {

    if (!isset($_POST['lease_id'])) {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $lease_id = (int) $_POST['lease_id'];

    // Verify tenant ownership
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

            // Check if there's already a pending termination request
            $checkPending = $conn->prepare("
                SELECT ID FROM lease_terminationstbl 
                WHERE lease_id = ? AND landlord_status = 'pending'
            ");
            $checkPending->bind_param("i", $lease_id);
            $checkPending->execute();
            $alreadyPending = $checkPending->get_result()->fetch_assoc();
            $checkPending->close();

            if ($alreadyPending) {
                echo json_encode(["success" => false, "message" => "You already have a pending termination request."]);
                exit;
            }

            // Only insert the request — landlord will approve/reject via approve_termination action
            $insert = $conn->prepare("
                INSERT INTO lease_terminationstbl 
                    (lease_id, terminated_by, reason, landlord_status, terminated_at)
                VALUES (?, 'tenant', ?, 'pending', NOW())
            ");
            $insert->bind_param("is", $lease_id, $reason);

            if ($insert->execute()) {
                $insert->close();
                echo json_encode([
                    "success" => true,
                    "message" => "Termination request submitted. Waiting for landlord approval."
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to submit termination request."]);
            }
            exit;
    }
}

/* =========================
   LANDLORD-SIDE ACTIONS
   (cancel, approve_termination, reject_termination)
========================= */

// Verify landlord session for all landlord actions
if (!isset($_SESSION['landlord_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$landlord_id = (int) $_SESSION['landlord_id'];

switch ($action) {

    /* ── Cancel a pending lease (landlord made a mistake) ── */
    case 'cancel':
        $lease_id = (int) ($_POST['lease_id'] ?? 0);
        if (!$lease_id) {
            echo json_encode(["success" => false, "message" => "Invalid lease ID."]);
            exit;
        }

        // Only allow cancel if lease is pending AND tenant hasn't accepted yet
        $check = $conn->prepare("
            SELECT ID, tenant_response, listing_id 
            FROM leasetbl 
            WHERE ID = ? AND landlord_id = ? AND status = 'pending'
        ");
        $check->bind_param("ii", $lease_id, $landlord_id);
        $check->execute();
        $lease = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$lease) {
            echo json_encode(["success" => false, "message" => "Lease not found or cannot be cancelled."]);
            exit;
        }

        if ($lease['tenant_response'] === 'accepted') {
            echo json_encode(["success" => false, "message" => "Cannot cancel — tenant has already accepted this lease."]);
            exit;
        }

        $conn->begin_transaction();
        try {
            // Mark lease as cancelled
            $upd = $conn->prepare("
                UPDATE leasetbl 
                SET status = 'cancelled', lease_status = 'cancelled' 
                WHERE ID = ?
            ");
            $upd->bind_param("i", $lease_id);
            $upd->execute();
            $upd->close();

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Lease cancelled. You can now create a new lease agreement."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Failed to cancel lease: " . $e->getMessage()]);
        }
        exit;

    /* ── Approve tenant termination request ── */
    case 'approve_termination':
        $lease_id       = (int) ($_POST['lease_id']       ?? 0);
        $termination_id = (int) ($_POST['termination_id'] ?? 0);

        if (!$lease_id || !$termination_id) {
            echo json_encode(["success" => false, "message" => "Invalid data."]);
            exit;
        }

        // Verify this lease belongs to this landlord
        $check = $conn->prepare("SELECT listing_id, tenant_id FROM leasetbl WHERE ID = ? AND landlord_id = ?");
        $check->bind_param("ii", $lease_id, $landlord_id);
        $check->execute();
        $lease = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$lease) {
            echo json_encode(["success" => false, "message" => "Lease not found or unauthorized."]);
            exit;
        }

        $listing_id = $lease['listing_id'];
        $tenant_id  = $lease['tenant_id'];

        $conn->begin_transaction();
        try {
            // 1. Mark termination as approved
            $upd = $conn->prepare("
                UPDATE lease_terminationstbl 
                SET landlord_status = 'approved', responded_at = NOW() 
                WHERE ID = ? AND lease_id = ?
            ");
            $upd->bind_param("ii", $termination_id, $lease_id);
            $upd->execute();
            $upd->close();

            // 2. Terminate the lease
            $upd2 = $conn->prepare("
                UPDATE leasetbl 
                SET status = 'terminated', lease_status = 'terminated', tenant_response = 'terminated' 
                WHERE ID = ?
            ");
            $upd2->bind_param("i", $lease_id);
            $upd2->execute();
            $upd2->close();

            // 3. Mark listing available again
            $upd3 = $conn->prepare("UPDATE listingtbl SET availability = 'available' WHERE ID = ?");
            $upd3->bind_param("i", $listing_id);
            $upd3->execute();
            $upd3->close();

            // 4. Remove from renttbl
            $del = $conn->prepare("DELETE FROM renttbl WHERE lease_id = ?");
            $del->bind_param("i", $lease_id);
            $del->execute();
            $del->close();

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Termination approved. Lease has been ended and listing is now available."]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["success" => false, "message" => "Failed to approve termination: " . $e->getMessage()]);
        }
        exit;

    /* ── Reject tenant termination request ── */
    case 'reject_termination':
        $termination_id = (int) ($_POST['termination_id'] ?? 0);

        if (!$termination_id) {
            echo json_encode(["success" => false, "message" => "Invalid termination ID."]);
            exit;
        }

        $upd = $conn->prepare("
            UPDATE lease_terminationstbl 
            SET landlord_status = 'rejected', responded_at = NOW() 
            WHERE ID = ?
        ");
        $upd->bind_param("i", $termination_id);

        if ($upd->execute()) {
            $upd->close();
            echo json_encode(["success" => true, "message" => "Termination request rejected. Lease remains active."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to reject termination request."]);
        }
        exit;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        exit;
}
?>