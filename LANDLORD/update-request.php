<?php
/**
 * update-request.php
 *
 * Handles two distinct call patterns:
 *
 *  A) From rental-management.php (Approve/Reject termination or renewal):
 *       POST: lease_id, type (termination|renewal), status (approved|rejected)
 *
 *  B) From landlord-property-details.php (Remove a tenant request card):
 *       POST: request_id, action=remove
 */

require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

$landlord_id    = (int)($_SESSION['landlord_id'] ?? 0);
$lease_id       = (int)($_POST['lease_id']       ?? 0);
$type           = trim($_POST['type']            ?? '');
$status         = trim($_POST['status']          ?? '');
$action         = trim($_POST['action']          ?? '');
$request_id     = (int)($_POST['request_id']     ?? 0);

if (!$landlord_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
//  PATTERN B — Remove a request card  (action=remove)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'remove') {
    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        exit;
    }

    // Mark tenant_action = 'removed' so it is filtered from the landlord's view
    $s = $conn->prepare("
        UPDATE requesttbl
        SET tenant_action = 'removed'
        WHERE ID = ?
    ");
    $s->bind_param("i", $request_id);
    $s->execute();
    $s->close();

    echo json_encode(['success' => true, 'message' => 'Request removed successfully.']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
//  PATTERN A — Approve / Reject termination or renewal
// ══════════════════════════════════════════════════════════════════════════════
if (!$lease_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid lease ID.']);
    exit;
}
if (!in_array($type, ['termination', 'renewal'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request type.']);
    exit;
}
if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// ── Verify this lease belongs to this landlord ────────────────────────────
$checkStmt = $conn->prepare("
    SELECT ID, listing_id, tenant_id
    FROM leasetbl
    WHERE ID = ? AND landlord_id = ?
");
$checkStmt->bind_param("ii", $lease_id, $landlord_id);
$checkStmt->execute();
$lease = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$lease) {
    echo json_encode(['success' => false, 'message' => 'Lease not found or you are not authorized to manage it.']);
    exit;
}

$listing_id = (int)$lease['listing_id'];
$tenant_id  = (int)$lease['tenant_id'];

// ══════════════════════════════════════════════════════════════════════════════
//  TERMINATION
// ══════════════════════════════════════════════════════════════════════════════
if ($type === 'termination') {

    // 1. Update the pending termination record
    $updStmt = $conn->prepare("
        UPDATE lease_terminationstbl
        SET landlord_status = ?, responded_at = NOW()
        WHERE lease_id = ? AND landlord_status = 'pending'
    ");
    $updStmt->bind_param("si", $status, $lease_id);
    $updStmt->execute();
    $affected = $updStmt->affected_rows;
    $updStmt->close();

    if ($affected === 0) {
        // Already processed or no pending record — still return success so the UI reloads cleanly
        echo json_encode(['success' => true, 'message' => 'No pending termination request found (may have already been processed).']);
        exit;
    }

    if ($status === 'approved') {

        // 2. Mark lease as terminated
        $s = $conn->prepare("
            UPDATE leasetbl
            SET status          = 'terminated',
                lease_status    = 'terminated',
                tenant_response = 'terminated'
            WHERE ID = ?
        ");
        $s->bind_param("i", $lease_id);
        $s->execute();
        $s->close();

        // 3. Remove from renttbl
        $s = $conn->prepare("DELETE FROM renttbl WHERE lease_id = ?");
        $s->bind_param("i", $lease_id);
        $s->execute();
        $s->close();

        // 4. Mark listing as available again
        $s = $conn->prepare("UPDATE listingtbl SET availability = 'available' WHERE ID = ?");
        $s->bind_param("i", $listing_id);
        $s->execute();
        $s->close();

        // 5. Tag the request row so the landlord card view can reflect the new state
        //    (uses 'terminated' — kept visible so landlord can create new lease)
        $s = $conn->prepare("
            UPDATE requesttbl
            SET tenant_action = 'terminated'
            WHERE listing_id = ? AND tenant_id = ?
            ORDER BY ID DESC LIMIT 1
        ");
        $s->bind_param("ii", $listing_id, $tenant_id);
        $s->execute();
        $s->close();

        echo json_encode([
            'success' => true,
            'message' => 'Termination approved. The lease has been ended and the listing is now available again.'
        ]);

    } else {
        // Rejected — lease stays active, nothing else to do
        echo json_encode([
            'success' => true,
            'message' => 'Termination request rejected. The lease remains active.'
        ]);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
//  RENEWAL
// ══════════════════════════════════════════════════════════════════════════════
if ($type === 'renewal') {

    // 1. Update the pending renewal record
    $updStmt = $conn->prepare("
        UPDATE lease_renewaltbl
        SET landlord_status = ?, responded_at = NOW()
        WHERE lease_id = ? AND landlord_status = 'pending'
    ");
    $updStmt->bind_param("si", $status, $lease_id);
    $updStmt->execute();
    $affected = $updStmt->affected_rows;
    $updStmt->close();

    if ($affected === 0) {
        echo json_encode(['success' => true, 'message' => 'No pending renewal request found (may have already been processed).']);
        exit;
    }

    if ($status === 'approved') {

        // 2. Mark lease as renewed
        $s = $conn->prepare("
            UPDATE leasetbl
            SET status       = 'renewed',
                lease_status = 'renewed'
            WHERE ID = ?
        ");
        $s->bind_param("i", $lease_id);
        $s->execute();
        $s->close();

        echo json_encode([
            'success' => true,
            'message' => 'Lease renewal approved successfully.'
        ]);

    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Renewal request rejected.'
        ]);
    }
    exit;
}

// Fallthrough (should never reach here)
echo json_encode(['success' => false, 'message' => 'Unhandled request.']);