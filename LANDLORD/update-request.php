<?php
require_once '../connection.php';
include '../session_auth.php';

$request_id = intval($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($request_id <= 0 || empty($action)) {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

if ($action === 'reject') {
    // Delete the request from the table
    $stmt = $conn->prepare("DELETE FROM requesttbl WHERE ID = ?");
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        echo "Request rejected and deleted";
    } else {
        http_response_code(500);
        echo "Failed to delete request";
    }
    $stmt->close();
} elseif ($action === 'remove') {
    // Optional: remove already rejected request
    $stmt = $conn->prepare("DELETE FROM requesttbl WHERE ID = ?");
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        echo "Request removed";
    } else {
        http_response_code(500);
        echo "Failed to remove request";
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo "Invalid action";
}

$conn->close();
?>