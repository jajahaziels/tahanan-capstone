<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "tahanandb";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get user info from session
if (!isset($_SESSION['user_type'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user_type = $_SESSION['user_type'];

if ($user_type === 'tenant') {
    $user_id = $_SESSION['tenant_id'] ?? null;
} elseif ($user_type === 'landlord') {
    $user_id = $_SESSION['landlord_id'] ?? null;
} else {
    echo json_encode(["success" => false, "error" => "Invalid user type"]);
    exit;
}

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User ID not found"]);
    exit;
}

// Get conversation_id from request
$conversation_id = $_POST['conversation_id'] ?? null;

if (!$conversation_id) {
    echo json_encode(["success" => false, "error" => "Missing conversation_id"]);
    exit;
}

// Verify user is a member of this conversation
$stmt = $conn->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? AND user_type = ?");
$stmt->bind_param("iis", $conversation_id, $user_id, $user_type);
$stmt->execute();
$is_member = $stmt->get_result()->fetch_assoc();

if (!$is_member) {
    echo json_encode(["success" => false, "error" => "You are not a member of this conversation"]);
    exit;
}

// Mark all messages in this conversation as read (where the current user is NOT the sender)
$stmt = $conn->prepare("
    UPDATE messages 
    SET read_at = NOW() 
    WHERE conversation_id = ? 
    AND read_at IS NULL 
    AND NOT (sender_id = ? AND sender_type = ?)
");
$stmt->bind_param("iis", $conversation_id, $user_id, $user_type);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo json_encode([
        "success" => true,
        "marked_read" => $affected_rows
    ]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
?>