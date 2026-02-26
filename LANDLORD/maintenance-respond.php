<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = (int) ($_POST['complaint_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $response = trim($_POST['response'] ?? '');
    $scheduled_date = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;

    if ($complaint_id <= 0 || empty($status)) {
        die("Invalid request");
    }

    // Check if this complaint belongs to the logged-in landlord
    $checkSql = "SELECT * FROM maintenance_requeststbl WHERE ID = ? AND landlord_id = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("ii", $complaint_id, $landlord_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Complaint not found or unauthorized");
    }

    // Update complaint
    $completed_date = $status === 'completed' ? date("Y-m-d") : null;

    $updateSql = "UPDATE maintenance_requeststbl 
                  SET status = ?, scheduled_date = ?, landlord_response = ?, completed_date = ?, updated_at = NOW() 
                  WHERE ID = ? AND landlord_id = ?";
    $stmtUpdate = $conn->prepare($updateSql);
    $stmtUpdate->bind_param("ssssii", $status, $scheduled_date, $response, $completed_date, $complaint_id, $landlord_id);

    if ($stmtUpdate->execute()) {
        header("Location: history.php?success=1");
        exit();
    } else {
        die("Failed to update complaint");
    }
} else {
    die("Invalid request method");
}