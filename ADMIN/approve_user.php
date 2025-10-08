<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Only admins can approve
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../LOGIN/login.php");
    exit;
}

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = intval($_GET['id']);
    
    // Check if 'status' column exists in your tables
    // If not, you'll need to add it first: ALTER TABLE landlordtbl ADD COLUMN status VARCHAR(20) DEFAULT 'pending';
    
    if ($type === 'landlord') {
        $stmt = $conn->prepare("UPDATE landlordtbl SET status = 'active' WHERE ID = ?");
    } elseif ($type === 'tenant') {
        $stmt = $conn->prepare("UPDATE tenanttbl SET status = 'active' WHERE ID = ?");
    } else {
        header("Location: admin.php?error=invalid_type");
        exit;
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: admin.php?success=approved");
    } else {
        header("Location: admin.php?error=approve_failed");
    }
    
    $stmt->close();
} else {
    header("Location: admin.php");
}

$conn->close();
?>