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
    $table = 'tenanttbl';
} elseif ($user_type === 'landlord') {
    $user_id = $_SESSION['landlord_id'] ?? null;
    $table = 'landlordtbl';
} else {
    echo json_encode(["success" => false, "error" => "Invalid user type"]);
    exit;
}

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User ID not found"]);
    exit;
}

// Get POST data
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(["success" => false, "error" => "New passwords do not match"]);
    exit;
}

if (strlen($new_password) < 8) {
    echo json_encode(["success" => false, "error" => "Password must be at least 8 characters long"]);
    exit;
}

// Get current password from database
$stmt = $conn->prepare("SELECT password FROM $table WHERE ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo json_encode(["success" => false, "error" => "User not found"]);
    exit;
}

$stored_password = $result['password'];

// Check if current password matches
// Try both plain text and hashed comparison
$password_matches = false;

if (password_verify($current_password, $stored_password)) {
    // Password is hashed with password_hash()
    $password_matches = true;
} elseif (md5($current_password) === $stored_password) {
    // Password is hashed with MD5
    $password_matches = true;
} elseif ($current_password === $stored_password) {
    // Password is stored in plain text
    $password_matches = true;
}

if (!$password_matches) {
    echo json_encode(["success" => false, "error" => "Current password is incorrect"]);
    exit;
}

// Hash new password using password_hash (best practice)
$new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$stmt = $conn->prepare("UPDATE $table SET password = ? WHERE ID = ?");
$stmt->bind_param("si", $new_password_hashed, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Password changed successfully"]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to update password"]);
}

$stmt->close();
$conn->close();
?>