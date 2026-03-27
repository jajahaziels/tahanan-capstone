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

// Get parameters
$user_id = $_GET['user_id'] ?? null;
$user_type = $_GET['user_type'] ?? null;

if (!$user_id || !$user_type) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

// Count conversations with unread messages
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT m.conversation_id) as unread_count
    FROM messages m
    INNER JOIN conversation_members cm ON m.conversation_id = cm.conversation_id
    WHERE cm.user_id = ? 
    AND cm.user_type = ?
    AND m.read_at IS NULL
    AND NOT (m.sender_id = ? AND m.sender_type = ?)
");
$stmt->bind_param("isis", $user_id, $user_type, $user_id, $user_type);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "success" => true,
    "unread_count" => (int)$result['unread_count']
]);

$stmt->close();
$conn->close();
?>