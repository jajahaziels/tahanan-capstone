<?php
require_once '../connection.php';

// Get current date
$today = date('Y-m-d');

// Find expired rentals
$sql = "
    SELECT ID, listing_id FROM renttbl
    WHERE end_date < ? AND status = 'approved'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $listing_id = $row['listing_id'];
    $rental_id = $row['ID'];

    // 1. Mark rental as expired
    $conn->query("UPDATE renttbl SET status = 'expired' WHERE ID = $rental_id");

    // 2. Make property available again
    $conn->query("UPDATE listingtbl SET availability = 'available' WHERE ID = $listing_id");
}

$stmt->close();
?>
