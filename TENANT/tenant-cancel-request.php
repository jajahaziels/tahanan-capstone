<?php
require_once '../connection.php';
require_once '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rent_id    = intval($_POST['rent_id']);
    $tenant_id  = intval($_POST['tenant_id']);
    $landlord_id = intval($_POST['landlord_id']);
    $listing_id = intval($_POST['listing_id']);

    $stmt = $conn->prepare("INSERT INTO cancel_requesttbl (rent_id, tenant_id, landlord_id, listing_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $rent_id, $tenant_id, $landlord_id, $listing_id);

    if ($stmt->execute()) {
        header("Location: tenant-rental.php?success=cancel_request");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
