<?php
require_once '../LOGIN/session_auth.php';
require_once '../connection.php';

// Only admins can delete
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../LOGIN/login.php");
    exit;
}

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = intval($_GET['id']);
    
    if ($type === 'landlord') {
        $stmt = $conn->prepare("DELETE FROM landlordtbl WHERE ID = ?");
    } elseif ($type === 'tenant') {
        $stmt = $conn->prepare("DELETE FROM tenanttbl WHERE ID = ?");
    } else {
        header("Location: admin.php?error=invalid_type");
        exit;
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: admin.php?success=deleted");
    } else {
        header("Location: admin.php?error=delete_failed");
    }
    
    $stmt->close();
} else {
    header("Location: admin.php");
}

$conn->close();
?>