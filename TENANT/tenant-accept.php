<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'], $_POST['lease_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];
$lease_id = (int) $_POST['lease_id'];

// Get lease info
$stmt = $conn->prepare("SELECT * FROM leasetbl WHERE ID = ? AND tenant_id = ?");
$stmt->bind_param("ii", $lease_id, $tenant_id);
$stmt->execute();
$lease = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lease) {
    die("Lease not found.");
}

// Update lease status to active + tenant_response
$stmt = $conn->prepare("UPDATE leasetbl SET status='active', tenant_response='accepted' WHERE ID=?");
$stmt->bind_param("i", $lease_id);
$stmt->execute();
$stmt->close();

// Insert into renttbl
$stmt = $conn->prepare("
    INSERT INTO renttbl 
    (listing_id, tenant_id, landlord_id, start_date, end_date, status, lease_id)
    VALUES (?, ?, ?, ?, ?, 'approved', ?)
");
$stmt->bind_param(
    "iiissi", // 6 types for 6 variables
    $lease['listing_id'],
    $tenant_id,
    $lease['landlord_id'],
    $lease['start_date'],
    $lease['end_date'],
    $lease_id
);
$stmt->execute();
$stmt->close();

header("Location: tenant-rental.php?accepted=1");
exit;
?>