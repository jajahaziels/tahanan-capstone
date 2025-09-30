<?php
// session_check.php
session_start();
header('Content-Type: application/json');

// Include database connection - use correct path
$conn_path = dirname(__FILE__) . '/../connection.php';
if (file_exists($conn_path)) {
    require_once $conn_path;
} else {
    echo json_encode([
        "success" => false,
        "error" => "Database connection file not found"
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_type'])) {
    echo json_encode([
        "success" => false,
        "error" => "User not logged in",
        "redirect" => "../LOGIN/login.php"
    ]);
    exit;
}

$user_type = $_SESSION['user_type'];

// Get user ID based on type
if ($user_type === 'landlord') {
    if (!isset($_SESSION['landlord_id'])) {
        echo json_encode([
            "success" => false,
            "error" => "Landlord ID not found in session",
            "redirect" => "../LOGIN/login.php"
        ]);
        exit;
    }
    $user_id = $_SESSION['landlord_id'];
    $table = 'landlordtbl';
} else if ($user_type === 'tenant') {
    if (!isset($_SESSION['tenant_id'])) {
        echo json_encode([
            "success" => false,
            "error" => "Tenant ID not found in session",
            "redirect" => "../LOGIN/login.php"
        ]);
        exit;
    }
    $user_id = $_SESSION['tenant_id'];
    $table = 'tenanttbl';
} else {
    echo json_encode([
        "success" => false,
        "error" => "Invalid user type",
        "redirect" => "../LOGIN/login.php"
    ]);
    exit;
}

// Verify user exists in the appropriate table
try {
    $stmt = $conn->prepare("SELECT ID, firstName, middleName, lastName, email FROM $table WHERE ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $full_name = trim($user['firstName'] . ' ' . ($user['middleName'] ? $user['middleName'] . ' ' : '') . $user['lastName']);
        
        echo json_encode([
            "success" => true,
            "user_id" => $user_id,
            "user_type" => $user_type,
            "name" => $full_name,
            "email" => $user['email']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "User not found in database",
            "redirect" => "../LOGIN/login.php"
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Database error: " . $e->getMessage()
    ]);
}

$conn->close();
?>