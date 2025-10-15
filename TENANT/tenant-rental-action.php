<?php
require_once '../connection.php';
require_once '../session_auth.php';

if (!isset($_SESSION['tenant_id'])) {
    die("Unauthorized access");
}

$tenant_id = $_SESSION['tenant_id'];
$rental_id = $_POST['rental_id'] ?? null;
$action = $_POST['action'] ?? '';

if (!$rental_id || !in_array($action, ['extend', 'cancel'])) {
    die("Invalid request");
}

// Validate rental belongs to this tenant
$stmt = $conn->prepare("SELECT ID FROM renttbl WHERE ID = ? AND tenant_id = ?");
$stmt->bind_param("ii", $rental_id, $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Unauthorized rental access");
}
$stmt->close();

// Update request
$stmt = $conn->prepare("UPDATE renttbl SET tenant_request = ?, request_status = 'pending' WHERE ID = ?");
$stmt->bind_param("si", $action, $rental_id);

if ($stmt->execute()) {
    echo "<script>
        alert('Request sent to landlord successfully!');
        window.location.href = 'tenant-rental.php';
    </script>";
} else {
    echo "Database error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
