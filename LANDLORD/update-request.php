<?php
require_once '../connection.php';
include '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $listing_id = intval($_POST['listing_id']);
    $action = $_POST['action'];

    // Validate action
    if ($action === "approve") {
        $status = "approved";

        // Prevent multiple approvals for same listing
        $check = $conn->prepare("SELECT ID FROM renttbl WHERE listing_id=? AND status='approved'");
        $check->bind_param("i", $listing_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            die("⚠️ This property already has an approved tenant.");
        }
    } elseif ($action === "reject") {
        $status = "rejected";
    } else {
        die("Invalid action.");
    }

    // Update renttbl
    $sql = "UPDATE renttbl SET status=? WHERE ID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $request_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        if ($status === "approved") {
            header("Location: landlord-rental.php?success=1");
        } else {
            header("Location: property-details.php?ID=$listing_id&rejected=1");
        }
        exit;
    } else {
        echo "⚠️ Update failed (maybe already $status or invalid request_id: $request_id)";
    }
}
?>
