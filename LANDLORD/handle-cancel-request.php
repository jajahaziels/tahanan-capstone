<?php
require_once '../connection.php';
require_once '../session_auth.php';

if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized access. Please log in as landlord.");
}

$landlord_id = (int) $_SESSION['landlord_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $rental_id  = intval($_POST['rental_id']);
    $action     = $_POST['action'] ?? '';

    if ($request_id <= 0 || $rental_id <= 0) {
        die("Invalid request.");
    }

    if (!in_array($action, ['approve', 'reject'])) {
        die("Invalid action.");
    }

    // Fetch the cancel request to ensure it belongs to this landlord
    $stmt = $conn->prepare("
        SELECT * FROM cancel_requesttbl 
        WHERE ID = ? AND landlord_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $request_id, $landlord_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cancelRequest = $result->fetch_assoc();
    $stmt->close();

    if (!$cancelRequest) {
        die("Cancel request not found or already handled.");
    }

    if ($action === 'approve') {
        // 1. Update rental status (optional: delete rental or mark as cancelled)
        $stmt = $conn->prepare("UPDATE renttbl SET status='cancelled' WHERE ID=?");
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();
        $stmt->close();

        // 2. Update property availability
        $stmt = $conn->prepare("UPDATE listingtbl SET availability='available' WHERE ID=?");
        $stmt->bind_param("i", $cancelRequest['listing_id']);
        $stmt->execute();
        $stmt->close();

        // 3. Update cancel request status
        $stmt = $conn->prepare("UPDATE cancel_requesttbl SET status='approved' WHERE ID=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();

        header("Location: landlord-properties.php");
        exit;
    }

    if ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE cancel_requesttbl SET status='rejected' WHERE ID=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();

        header("Location: landlord-rental.php?request_id=$rental_id&cancel_rejected=1");
        exit;
    }
}
?>
