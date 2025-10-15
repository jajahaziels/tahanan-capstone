<?php
require_once '../connection.php';
require_once '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'] ?? 0;
$request_id = $_POST['request_id'] ?? 0;
$rental_id = $_POST['rental_id'] ?? 0;
$action = $_POST['action'] ?? '';

if ($landlord_id <= 0 || $request_id <= 0) die("Invalid request.");

// Get the extension info
$stmt = $conn->prepare("SELECT new_end_date FROM extension_requesttbl WHERE ID=? AND landlord_id=? AND status='pending'");
$stmt->bind_param("ii", $request_id, $landlord_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Request not found or already handled.");
}
$row = $result->fetch_assoc();
$new_end_date = $row['new_end_date'];
$stmt->close();

if ($action === 'approve') {
    // Extend the rental end date
    $updateRent = $conn->prepare("UPDATE renttbl SET end_date=? WHERE ID=?");
    $updateRent->bind_param("si", $new_end_date, $rental_id);
    $updateRent->execute();
    $updateRent->close();

    $status = 'approved';
} elseif ($action === 'reject') {
    $status = 'rejected';
} else {
    die("Invalid action.");
}

// Update the extension request status
$update = $conn->prepare("UPDATE extension_requesttbl SET status=? WHERE ID=?");
$update->bind_param("si", $status, $request_id);
$update->execute();
$update->close();

echo "<script>
    alert('✅ Extension request $status successfully!');
    window.location.href='landlord-rental.php?request_id={$rental_id}';
</script>";
?>
