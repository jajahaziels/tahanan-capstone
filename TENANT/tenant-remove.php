<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id']) || !isset($_POST['lease_id'])) {
    echo "Unauthorized or missing lease ID";
    exit;
}

$tenant_id = (int) $_SESSION['tenant_id'];
$lease_id = (int) $_POST['lease_id'];

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT listing_id FROM leasetbl WHERE ID = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $lease_id, $tenant_id);
    $stmt->execute();
    $stmt->bind_result($listing_id);
    if (!$stmt->fetch()) {
        throw new Exception("Lease not found or not yours.");
    }
    $stmt->close();

    // Delete from leasetbl
    $stmt = $conn->prepare("DELETE FROM leasetbl WHERE ID = ? AND tenant_id = ?");
    $stmt->bind_param("ii", $lease_id, $tenant_id);
    $stmt->execute();
    $stmt->close();

    // Delete from requesttbl for this tenant and listing
    $stmt = $conn->prepare("DELETE FROM requesttbl WHERE tenant_id = ? AND listing_id = ?");
    $stmt->bind_param("ii", $tenant_id, $listing_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo "success";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
