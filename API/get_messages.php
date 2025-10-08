<?php
// get_messages.php

header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";       // change if needed
$pass = "";           // change if needed
$db   = "tahanandb";  // your database

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get conversation_id from request
$conversation_id = $_GET['conversation_id'] ?? null;

if (!$conversation_id) {
    echo json_encode(["success" => false, "error" => "Missing conversation_id"]);
    exit;
}

// Fetch messages
$stmt = $conn->prepare("SELECT id, sender_id, content as message, created_at, file_path, file_type, file_size, content_type
                        FROM messages 
                        WHERE conversation_id = ? AND status = 'active'
                        ORDER BY created_at ASC");
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode([
    "success" => true,
    "messages" => $messages
]);

$stmt->close();
$conn->close();
  