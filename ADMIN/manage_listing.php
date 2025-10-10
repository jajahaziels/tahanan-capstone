<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Only admins can manage listings
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../LOGIN/login.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $listing_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'toggle') {
        // Toggle listing availability between available/occupied
        $stmt = $conn->prepare("UPDATE listingtbl SET availability = IF(availability = 'available', 'occupied', 'available') WHERE ID = ?");
        $stmt->bind_param("i", $listing_id);
        
        if ($stmt->execute()) {
            header("Location: view_listing.php?id={$listing_id}&success=toggled");
        } else {
            header("Location: view_listing.php?id={$listing_id}&error=toggle_failed");
        }
        
    } elseif ($action === 'delete') {
        // Delete listing
        $stmt = $conn->prepare("DELETE FROM listingtbl WHERE ID = ?");
        $stmt->bind_param("i", $listing_id);
        
        if ($stmt->execute()) {
            header("Location: listing.php?success=deleted");
        } else {
            header("Location: view_listing.php?id={$listing_id}&error=delete_failed");
        }
    }
    
    $stmt->close();
} else {
    header("Location: listing.php");
}

$conn->close();
?>