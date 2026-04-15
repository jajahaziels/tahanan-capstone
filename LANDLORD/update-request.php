<?php
require_once '../connection.php';
include '../session_auth.php';

header('Content-Type: application/json');

$landlord_id = $_SESSION['landlord_id'];
$lease_id    = intval($_POST['lease_id'] ?? 0);
$type        = $_POST['type']   ?? '';
$status      = $_POST['status'] ?? '';

if (!$lease_id || !in_array($type, ['termination', 'renewal']) || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// ✅ Verify this lease belongs to this landlord
$checkStmt = $conn->prepare("SELECT ID, listing_id, tenant_id FROM leasetbl WHERE ID = ? AND landlord_id = ?");
$checkStmt->bind_param("ii", $lease_id, $landlord_id);
$checkStmt->execute();
$lease = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$lease) {
    echo json_encode(['success' => false, 'message' => 'Lease not found or unauthorized.']);
    exit;
}

$listing_id = $lease['listing_id'];
$tenant_id  = $lease['tenant_id'];

// ════════════════════════════════════════════
//  TERMINATION
// ════════════════════════════════════════════
if ($type === 'termination') {

    // 1. Update landlord_status in lease_terminationstbl
    $updateStmt = $conn->prepare("
        UPDATE lease_terminationstbl 
        SET landlord_status = ?, responded_at = NOW()
        WHERE lease_id = ? AND landlord_status = 'pending'
    ");
    $updateStmt->bind_param("si", $status, $lease_id);
    $updateStmt->execute();
    $updateStmt->close();

    if ($status === 'approved') {

        // 2. Mark leasetbl status = 'terminated'
        $leaseStmt = $conn->prepare("
            UPDATE leasetbl 
            SET status = 'terminated', lease_status = 'terminated' 
            WHERE ID = ?
        ");
        $leaseStmt->bind_param("i", $lease_id);
        $leaseStmt->execute();
        $leaseStmt->close();

        // 3. Delete matching row from renttbl
        $rentStmt = $conn->prepare("
            DELETE FROM renttbl 
            WHERE lease_id = ?
        ");
        $rentStmt->bind_param("i", $lease_id);
        $rentStmt->execute();
        $rentStmt->close();

        // 4. Set listing back to available
        $listingStmt = $conn->prepare("
            UPDATE listingtbl 
            SET availability = 'available' 
            WHERE ID = ?
        ");
        $listingStmt->bind_param("i", $listing_id);
        $listingStmt->execute();
        $listingStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Termination approved. Lease ended and listing is now available again.'
        ]);

    } else {

        // Rejected — no other changes needed
        echo json_encode([
            'success' => true,
            'message' => 'Termination request rejected.'
        ]);
    }

// ════════════════════════════════════════════
//  RENEWAL
// ════════════════════════════════════════════
} elseif ($type === 'renewal') {

    // 1. Update landlord_status in lease_renewaltbl
    $updateStmt = $conn->prepare("
        UPDATE lease_renewaltbl 
        SET landlord_status = ?, responded_at = NOW()
        WHERE lease_id = ? AND landlord_status = 'pending'
    ");
    $updateStmt->bind_param("si", $status, $lease_id);
    $updateStmt->execute();
    $updateStmt->close();

    if ($status === 'approved') {

        // 2. Mark lease as renewed
        $leaseStmt = $conn->prepare("
            UPDATE leasetbl 
            SET status = 'renewed', lease_status = 'renewed' 
            WHERE ID = ?
        ");
        $leaseStmt->bind_param("i", $lease_id);
        $leaseStmt->execute();
        $leaseStmt->close();

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
}
?>