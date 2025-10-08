<?php
require_once '../connection.php';
require_once '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = intval($_POST['rental_id'] ?? 0);
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $landlord_id = $_SESSION['landlord_id'] ?? 0;

    if ($rental_id <= 0 || $listing_id <= 0 || $landlord_id <= 0) {
        die("Invalid request.");
    }

    // ✅ Check ownership
    $check = $conn->prepare("
        SELECT r.ID 
        FROM renttbl r
        JOIN listingtbl l ON r.listing_id = l.ID
        WHERE r.ID = ? AND l.landlord_id = ? AND r.status = 'approved'
    ");
    $check->bind_param("ii", $rental_id, $landlord_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        die("Unauthorized or invalid rental.");
    }

    // ✅ Cancel rental
    $cancel = $conn->prepare("UPDATE renttbl SET status='cancelled' WHERE ID=?");
    $cancel->bind_param("i", $rental_id);
    $cancel->execute();

    // ✅ Make listing available again
    $updateListing = $conn->prepare("UPDATE listingtbl SET availability='available' WHERE ID=?");
    $updateListing->bind_param("i", $listing_id);
    $updateListing->execute();

    // ✅ Redirect with success message
    echo "<script>
        alert('✅ Rental successfully cancelled. The property is now available again.');
        window.location.href='property-details.php?ID=$listing_id';
    </script>";
    exit;
} else {
    echo "Invalid request method.";
}
?>
