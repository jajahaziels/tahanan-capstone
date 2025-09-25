<?php
// send_message.php

header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";       // change if you set a MySQL user
$pass = "";           // change if you set a MySQL password
$db   = "tahanandb";  // your database name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get data from request
$conversation_id = $_POST['conversation_id'] ?? null;
$sender_id       = $_POST['sender_id'] ?? null;
$message         = $_POST['message'] ?? null;

if (!$conversation_id || !$sender_id || !$message) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

// Insert into messages table
$stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $conversation_id, $sender_id, $message);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
