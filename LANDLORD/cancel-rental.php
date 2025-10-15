<?php
require_once '../connection.php';
require_once '../session_auth.php';

// Verify landlord
if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized access.");
}

$landlord_id = $_SESSION['landlord_id'];
$rental_id = $_POST['rental_id'] ?? 0;
$listing_id = $_POST['listing_id'] ?? 0;

if ($rental_id <= 0 || $listing_id <= 0) {
    die("Invalid rental data.");
}

// Verify this rental belongs to the landlord
$check = $conn->prepare("
    SELECT r.ID 
    FROM renttbl r
    JOIN listingtbl ls ON r.listing_id = ls.ID
    WHERE r.ID = ? AND ls.landlord_id = ? AND r.status = 'approved'
");
$check->bind_param("ii", $rental_id, $landlord_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    die("Rental not found or unauthorized access.");
}
$check->close();

// Cancel rental
$conn->begin_transaction();
try {
    // 1. Update renttbl
    $stmt1 = $conn->prepare("UPDATE renttbl SET status = 'cancelled' WHERE ID = ?");
    $stmt1->bind_param("i", $rental_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. Make listing available again
    $stmt2 = $conn->prepare("UPDATE listingtbl SET availability = 'available' WHERE ID = ?");
    $stmt2->bind_param("i", $listing_id);
    $stmt2->execute();
    $stmt2->close();

    $conn->commit();

    echo "<script>
        alert('✅ Rental cancelled successfully! The property is now available again.');
        window.location.href = 'property-details.php?ID={$listing_id}';
    </script>";

} catch (Exception $e) {
    $conn->rollback();
    echo "❌ Error cancelling rental: " . $e->getMessage();
}
?>
