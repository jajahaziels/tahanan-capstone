<?php
require_once '../session_auth.php';
require_once '../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$property_id = $_POST['property_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$property_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("SELECT landlord_id, listingName FROM listingtbl WHERE ID = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    echo json_encode(['success' => false, 'error' => 'Property not found']);
    exit;
}

switch ($action) {
    case 'approve':
        $notes = $_POST['notes'] ?? '';
        
        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET verification_status = 'approved',
                verified_by = ?,
                verified_date = NOW(),
                verification_notes = ?
            WHERE ID = ?
        ");
        
        $stmt->bind_param("isi", $admin_id, $notes, $property_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Property approved successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    case 'reject':
        $reason = $_POST['reason'] ?? '';
        
        if (empty($reason)) {
            echo json_encode(['success' => false, 'error' => 'Rejection reason is required']);
            exit;
        }
        
        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET verification_status = 'rejected',
                verified_by = ?,
                verified_date = NOW(),
                rejection_reason = ?,
                verification_notes = ?
            WHERE ID = ?
        ");
        
        $notes = "Rejected: " . $reason;
        $stmt->bind_param("issi", $admin_id, $reason, $notes, $property_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Property rejected'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    case 'schedule':
        $datetime = $_POST['datetime'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($datetime)) {
            echo json_encode(['success' => false, 'error' => 'Date and time required']);
            exit;
        }
        
        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET admin_visit_scheduled = ?,
                verification_notes = ?
            WHERE ID = ?
        ");
        
        $visit_notes = "Site visit scheduled for: " . $datetime . ($notes ? "\nNotes: " . $notes : '');
        $stmt->bind_param("ssi", $datetime, $visit_notes, $property_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Visit scheduled successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();
?>