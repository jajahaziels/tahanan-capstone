<?php
require_once '../connection.php';
include '../session_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $listing_id = intval($_POST['listing_id']); // you passed this as hidden input
    $action = $_POST['action']; // approve or reject

    if ($action === "approve") {
        $status = "approved";
    } elseif ($action === "reject") {
        $status = "rejected";
    } else {
        die("Invalid action.");
    }

    $sql = "UPDATE renttbl SET status=? WHERE ID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $request_id);

    if (!$stmt->execute()) {
        die("Query failed: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        if ($status === "approved") {
            header("Location: landlord-rental.php?request_id=" . $request_id . "&success=1");
        } else {
            header("Location: property-details.php?ID=" . $listing_id . "&rejected=1");
        }
        exit;
    } else {
        echo "Update failed (maybe already " . $status . " or wrong request_id: " . $request_id . ")";
    }
}
?>
