<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = $_SESSION['tenant_id'];
$listing_id = intval($_POST['listing_id'] ?? 0);

if ($listing_id <= 0) {
    die("Invalid property ID.");
}

// === OPTION 1: Prevent tenant from renting another property ===
$check = $conn->prepare("SELECT ID FROM renttbl WHERE tenant_id=? AND status='approved'");
$check->bind_param("i", $tenant_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
        echo "<script>
        alert('⚠️ You already have a property rented. You cannot rent another one.');
        window.history.back();
    </script>";
    exit;
}

// Prevent duplicate applications (your existing code)
$checkDup = $conn->prepare("SELECT ID FROM renttbl WHERE tenant_id=? AND listing_id=?");
$checkDup->bind_param("ii", $tenant_id, $listing_id);
$checkDup->execute();
$dupResult = $checkDup->get_result();

if ($dupResult->num_rows > 0) {
        echo "<script>
        alert('⚠️ You already applied for this property..');
        window.history.back();
    </script>";
    exit;
}

// Insert request
$stmt = $conn->prepare("INSERT INTO renttbl (tenant_id, listing_id, date, status) VALUES (?, ?, NOW(), 'pending')");
$stmt->bind_param("ii", $tenant_id, $listing_id);
$stmt->execute();

        echo "<script>
        alert('Request submitted successfully!');
        window.history.back();
    </script>";
exit;
?>
