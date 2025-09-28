<?php
// session_check.php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode([
        "success" => false,
        "error" => "User not logged in",
        "redirect" => "../login.php"
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Verify user exists in the appropriate table
if ($user_type === 'landlord') {
    $stmt = $conn->prepare("SELECT ID, firstName, middleName, lastName, email FROM landlord WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $full_name = trim($user['firstName'] . ' ' . ($user['middleName'] ? $user['middleName'] . ' ' : '') . $user['lastName']);
    }
} else if ($user_type === 'tenant') {
    $stmt = $conn->prepare("SELECT ID, firstName, middleName, lastName, email FROM tenant WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $full_name = trim($user['firstName'] . ' ' . ($user['middleName'] ? $user['middleName'] . ' ' : '') . $user['lastName']);
    }
} else {
    echo json_encode([
        "success" => false,
        "error" => "Invalid user type",
        "redirect" => "../login.php"
    ]);
    exit;
}

// Check if user was found
if (!$user) {
    echo json_encode([
        "success" => false,
        "error" => "User not found in database",
        "redirect" => "../login.php"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "user_id" => $user_id,
    "user_type" => $user_type,
    "name" => $full_name,
    "email" => $user['email']
]);
?>