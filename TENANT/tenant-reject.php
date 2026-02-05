<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['tenant_id'], $_POST['lease_id'])) {
    die("Unauthorized access");
}

$tenant_id = (int) $_SESSION['tenant_id'];
$lease_id = (int) $_POST['lease_id'];

// Update the lease as rejected
$stmt = $conn->prepare("UPDATE leasetbl SET tenant_response='rejected', status='terminated' WHERE ID=? AND tenant_id=?");
$stmt->bind_param("ii", $lease_id, $tenant_id);
$stmt->execute();
$stmt->close();

// Redirect back to tenant page
header("Location: tenant-rental.php");
exit;
?>