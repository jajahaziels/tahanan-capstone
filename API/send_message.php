<?php
session_start();
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

// Get sender_id from SESSION
if (!isset($_SESSION['user_type'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user_type = $_SESSION['user_type'];

// Get the actual logged-in user's ID from session
if ($user_type === 'tenant') {
    $sender_id = $_SESSION['tenant_id'] ?? null;
} elseif ($user_type === 'landlord') {
    $sender_id = $_SESSION['landlord_id'] ?? null;
} else {
    echo json_encode(["success" => false, "error" => "Invalid user type"]);
    exit;
}

if (!$sender_id) {
    echo json_encode(["success" => false, "error" => "User ID not found in session"]);
    exit;
}

// Get data from request
$conversation_id = $_POST['conversation_id'] ?? null;
$message         = $_POST['message'] ?? null;

if (!$conversation_id) {
    echo json_encode(["success" => false, "error" => "Missing conversation_id"]);
    exit;
}

// Verify user is a member of this conversation
$stmt = $conn->prepare("SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? AND user_type = ?");
$stmt->bind_param("iis", $conversation_id, $sender_id, $user_type);
$stmt->execute();
$is_member = $stmt->get_result()->fetch_assoc();

if (!$is_member) {
    echo json_encode(["success" => false, "error" => "You are not a member of this conversation"]);
    exit;
}

// Handle file upload if present
$file_path = null;
$file_type = null;
$file_size = null;
$content_type = 'text';

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/chat_files/';
    
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
    
    if (empty($message)) {
        $message = $file_name;
    }
}

// Must have either message or file
if (empty($message) && empty($file_path)) {
    echo json_encode(["success" => false, "error" => "Message or file required"]);
    exit;
}

// Insert into messages table with sender_type
$stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, sender_type, content, content_type, status, file_path, file_type, file_size, created_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?, NOW())");
$stmt->bind_param("iisssssi", $conversation_id, $sender_id, $user_type, $message, $content_type, $file_path, $file_type, $file_size);

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