<?php
require_once '../connection.php';
include '../session_auth.php';

// Check tenant session
if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access.");
}

$tenant_id = (int) $_SESSION['tenant_id'];
$listing_id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;

if ($listing_id <= 0) {
    die("Invalid property ID.");
}

// Prevent duplicate applications
$check = $conn->prepare("SELECT ID FROM renttbl WHERE tenant_id=? AND listing_id=?");
$check->bind_param("ii", $tenant_id, $listing_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    die("⚠️ You already applied for this property.");
}

// Insert request
$sql = "INSERT INTO renttbl (tenant_id, listing_id, date, status) VALUES (?, ?, NOW(), 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $tenant_id, $listing_id);

if ($stmt->execute()) {
    header("Location: tenant-rental.php?success=1");
    exit;
} else {
    echo "Error: " . $stmt->error;
}
?>
