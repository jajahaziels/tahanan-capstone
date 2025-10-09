<?php
// send_message.php

header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root";       
$pass = "";           
$db   = "tahanandb";  

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get data from request
$conversation_id = $_POST['conversation_id'] ?? null;
$sender_id       = $_POST['sender_id'] ?? null;
$message         = $_POST['message'] ?? null;

if (!$conversation_id || !$sender_id) {
    echo json_encode(["success" => false, "error" => "Missing conversation_id or sender_id"]);
    exit;
}

// Handle file upload if present
$file_path = null;
$file_type = null;
$file_size = null;
$content_type = 'text';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/chat_files/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_name = $_FILES['file']['name'];
    $file_size = $_FILES['file']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_docs = ['pdf', 'doc', 'docx', 'txt', 'xlsx', 'xls'];
    
    if (in_array($file_ext, $allowed_images)) {
        $file_type = 'image';
        $content_type = 'image';
    } elseif (in_array($file_ext, $allowed_docs)) {
        $file_type = 'file';
        $content_type = 'file';
    } else {
        echo json_encode(["success" => false, "error" => "File type not allowed"]);
        exit;
    }
    
    // Check file size (max 10MB)
    if ($file_size > 10485760) {
        echo json_encode(["success" => false, "error" => "File too large (max 10MB)"]);
        exit;
    }
    
    // Generate unique filename
    $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_name;
    
    if (!move_uploaded_file($file_tmp, $file_path)) {
        echo json_encode(["success" => false, "error" => "Failed to upload file"]);
        exit;
    }
    
    // If no message text provided, use filename
    if (empty($message)) {
        $message = $file_name;
    }
}

// Must have either message or file
if (empty($message) && empty($file_path)) {
    echo json_encode(["success" => false, "error" => "Message or file required"]);
    exit;
}

// Insert into messages table with file info
$stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content, content_type, status, file_path, file_type, file_size, created_at) VALUES (?, ?, ?, ?, 'active', ?, ?, ?, NOW())");
$stmt->bind_param("iissssi", $conversation_id, $sender_id, $message, $content_type, $file_path, $file_type, $file_size);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message_id" => $conn->insert_id
    ]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$stmt->close();
$conn->close();
?>