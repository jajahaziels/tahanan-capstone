<?php
require_once '../connection.php';
require_once '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'] ?? 0;
$rental_id = $_POST['rental_id'] ?? 0;
$listing_id = $_POST['listing_id'] ?? 0;
$new_end_date = $_POST['new_end_date'] ?? null;

if ($tenant_id <= 0 || $rental_id <= 0 || empty($new_end_date)) {
    die("Invalid request.");
}

// Get landlord_id from listingtbl
$stmt = $conn->prepare("SELECT landlord_id FROM listingtbl WHERE ID = ?");
$stmt->bind_param("i", $listing_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Listing not found.");
}
$landlord_id = $result->fetch_assoc()['landlord_id'];
$stmt->close();

// Check if already requested
$check = $conn->prepare("SELECT ID FROM extension_requesttbl WHERE rent_id=? AND tenant_id=? AND status='pending'");
$check->bind_param("ii", $rental_id, $tenant_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo "<script>alert('⚠️ You already have a pending extension request.'); window.history.back();</script>";
    exit;
}
$check->close();

// Insert new extension request
$stmt = $conn->prepare("
    INSERT INTO extension_requesttbl (rent_id, tenant_id, landlord_id, listing_id, new_end_date)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iiiis", $rental_id, $tenant_id, $landlord_id, $listing_id, $new_end_date);

if ($stmt->execute()) {
    echo "<script>
        alert('✅ Extension request sent successfully!');
        window.location.href='tenant-rental.php';
    </script>";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
?>
