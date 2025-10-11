<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Only admins can verify
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../LOGIN/login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $landlord_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'verified') {
        // Approve landlord - set verification_status = 'verified'
        $stmt = $conn->prepare("UPDATE landlordtbl SET verification_status = 'verified' WHERE ID = ?");
        $stmt->bind_param("i", $landlord_id);
        
        if ($stmt->execute()) {
            header("Location: verify.php?success=verified");
        } else {
            header("Location: verify.php?error=approval_failed");
        }
        
    } elseif ($action === 'rejected') {
        // Reject landlord - set verification_status = 'rejected'
        // You can also delete the account if you prefer: DELETE FROM landlordtbl WHERE ID = ?
        $stmt = $conn->prepare("UPDATE landlordtbl SET verification_status = 'rejected' WHERE ID = ?");
        $stmt->bind_param("i", $landlord_id);
        
        if ($stmt->execute()) {
            header("Location: verify.php?success=rejected");
        } else {
            header("Location: verify.php?error=rejection_failed");
        }
    } else {
        header("Location: verify.php?error=invalid_action");
    }
    
    $stmt->close();
} else {
    header("Location: verify.php");
}

$conn->close();
?>