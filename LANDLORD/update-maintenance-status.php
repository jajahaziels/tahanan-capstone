<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    
    // Validate status (matching your database enum values)
    $allowed_statuses = ['Pending', 'Approved', 'In Progress', 'Completed'];
    if (!in_array($status, $allowed_statuses)) {
        die("Invalid status");
    }
    
    // Update the maintenance request status
    $sql = "UPDATE maintenance_requeststbl SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $request_id);
    
    if ($stmt->execute()) {
        // If status is completed, set the completed_date
        if ($status === 'Completed') {
            $updateDate = "UPDATE maintenance_requeststbl SET completed_date = CURDATE() WHERE id = ?";
            $stmtDate = $conn->prepare($updateDate);
            $stmtDate->bind_param("i", $request_id);
            $stmtDate->execute();
        }
        
        echo "success";
    } else {
        echo "error";
    }
    
    $stmt->close();
}
?>