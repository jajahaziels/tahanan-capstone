<?php
require_once '../connection.php';
include '../session_auth.php';

if (!isset($_POST['complaint_id'])) {
    echo "Invalid request";
    exit;
}

$complaint_id = (int) $_POST['complaint_id'];

// Optional security: only allow deleting completed complaints
$check = $conn->prepare("SELECT status FROM maintenance_requeststbl WHERE ID = ?");
$check->bind_param("i", $complaint_id);
$check->execute();
$result = $check->get_result();
$row = $result->fetch_assoc();

if (!$row || strtolower($row['status']) !== 'completed') {
    echo "Not allowed";
    exit;
}

$stmt = $conn->prepare("DELETE FROM maintenance_requeststbl WHERE ID = ?");
$stmt->bind_param("i", $complaint_id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "Database error";
}