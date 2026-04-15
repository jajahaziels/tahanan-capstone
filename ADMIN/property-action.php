<?php
require_once '../session_auth.php';
require_once '../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$admin_id    = $_SESSION['admin_id'];
$property_id = $_POST['property_id'] ?? null;
$action      = $_POST['action'] ?? null;

if (!$property_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("SELECT landlord_id, listingName FROM listingtbl WHERE ID = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result   = $stmt->get_result();
$property = $result->fetch_assoc();

if (!$property) {
    echo json_encode(['success' => false, 'error' => 'Property not found']);
    exit;
}

$landlord_id  = $property['landlord_id'];
$listing_name = $property['listingName'];

// ── Helper: insert a notification for the landlord ───────────────────────────
function notifyLandlord(mysqli $conn, int $landlord_id, string $message): void {
    $type      = 'system';
    $user_type = 'landlord';
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, user_type, message, type, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $stmt->bind_param("isss", $landlord_id, $user_type, $message, $type);
    $stmt->execute();
    $stmt->close();
}

switch ($action) {

    case 'approve':
        $notes = $_POST['notes'] ?? '';

        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET verification_status = 'approved',
                verified_by         = ?,
                verified_date       = NOW(),
                verification_notes  = ?
            WHERE ID = ?
        ");
        $stmt->bind_param("isi", $admin_id, $notes, $property_id);

        if ($stmt->execute()) {
            // ── Notify landlord ──
            $message = "✅ Your listing \"" . $listing_name . "\" has been approved and is now live!";
            if (!empty($notes)) {
                $message .= " Admin note: " . $notes;
            }
            notifyLandlord($conn, $landlord_id, $message);

            echo json_encode(['success' => true, 'message' => 'Property approved successfully']);
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

        $notes = "Rejected: " . $reason;

        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET verification_status = 'rejected',
                verified_by         = ?,
                verified_date       = NOW(),
                rejection_reason    = ?,
                verification_notes  = ?
            WHERE ID = ?
        ");
        $stmt->bind_param("issi", $admin_id, $reason, $notes, $property_id);

        if ($stmt->execute()) {
            // ── Notify landlord ──
            $message = "❌ Your listing \"" . $listing_name . "\" was not approved. Reason: " . $reason;
            notifyLandlord($conn, $landlord_id, $message);

            echo json_encode(['success' => true, 'message' => 'Property rejected']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    case 'schedule':
        $datetime = $_POST['datetime'] ?? '';
        $notes    = $_POST['notes']    ?? '';

        if (empty($datetime)) {
            echo json_encode(['success' => false, 'error' => 'Date and time required']);
            exit;
        }

        $visit_notes = "Site visit scheduled for: " . $datetime . ($notes ? "\nNotes: " . $notes : '');

        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET admin_visit_scheduled = ?,
                verification_notes    = ?
            WHERE ID = ?
        ");
        $stmt->bind_param("ssi", $datetime, $visit_notes, $property_id);

        if ($stmt->execute()) {
            // ── Notify landlord ──
            $formatted_dt = date('F j, Y \a\t g:i A', strtotime($datetime));
            $message      = "📅 A site visit for your listing \"" . $listing_name . "\" has been scheduled on " . $formatted_dt . ".";
            if (!empty($notes)) {
                $message .= " Note: " . $notes;
            }
            notifyLandlord($conn, $landlord_id, $message);

            echo json_encode(['success' => true, 'message' => 'Visit scheduled successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

$conn->close();