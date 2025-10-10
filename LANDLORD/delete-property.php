<?php
require_once '../connection.php';
session_start();

// Ensure landlord is logged in
$landlord_id = $_SESSION['landlord_id'] ?? 0;
if ($landlord_id <= 0) {
    die("Unauthorized access. Please log in as landlord.");
}

// Get property ID
$listing_id = $_POST['id'] ?? null;
if (!$listing_id) {
    die("Invalid property ID.");
}


// Check if property belongs to landlord
$stmt = $conn->prepare("SELECT * FROM listingtbl WHERE ID = ? AND landlord_id = ?");
$stmt->bind_param("ii", $listing_id, $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    die("Property not found or you don’t have permission to delete it.");
}

// Check if the property is occupied
$check_rental = $conn->prepare("
    SELECT ID 
    FROM renttbl 
    WHERE listing_id = ? AND status = 'approved'
");
$check_rental->bind_param("i", $listing_id);
$check_rental->execute();
$rental_result = $check_rental->get_result();
$isOccupied = $rental_result->num_rows > 0;
$check_rental->close();

if ($isOccupied) {
    echo "<script>
        alert('❌ Cannot delete: This property is currently occupied.');
        window.location.href = 'landlord-properties.php';
    </script>";
    exit;
}

// Delete property images
$images = json_decode($property['images'], true) ?? [];
foreach ($images as $img) {
    $filePath = "uploads/" . $img;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Delete property
$delete_stmt = $conn->prepare("DELETE FROM listingtbl WHERE ID = ?");
$delete_stmt->bind_param("i", $listing_id);

if ($delete_stmt->execute()) {
    echo "<script>
        alert('✅ Property deleted successfully!');
        window.location.href = 'landlord-properties.php';
    </script>";
} else {
    echo "<script>
        alert('⚠️ Failed to delete property.');
        window.location.href = 'landlord-properties.php';
    </script>";
}

$delete_stmt->close();
$conn->close();
?>
