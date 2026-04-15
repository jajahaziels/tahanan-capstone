<?php
// ============================================
// API/notify_listing_status.php
// Called internally when admin approves/rejects a listing.
// Inserts a row into the notifications table for the landlord.
// ============================================

require_once __DIR__ . '/../connection.php';

header('Content-Type: application/json');

// Accept POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$landlord_id  = isset($_POST['landlord_id'])  ? (int)$_POST['landlord_id']  : 0;
$listing_name = isset($_POST['listing_name']) ? trim($_POST['listing_name']) : '';
$action       = isset($_POST['action'])       ? trim($_POST['action'])       : ''; // 'approved' or 'rejected'
$reason       = isset($_POST['reason'])       ? trim($_POST['reason'])       : ''; // optional rejection reason

if (!$landlord_id || !$listing_name || !in_array($action, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters']);
    exit;
}

// Build the notification message
if ($action === 'approved') {
    $message = "✅ Your listing \"" . $listing_name . "\" has been approved and is now live!";
} else {
    $message  = "❌ Your listing \"" . $listing_name . "\" was not approved.";
    if (!empty($reason)) {
        $message .= " Reason: " . $reason;
    }
}

$type = 'system'; // uses the enum value from your notifications table

$stmt = $conn->prepare("
    INSERT INTO notifications (user_id, message, type, is_read, created_at)
    VALUES (?, ?, ?, 0, NOW())
");
$stmt->bind_param("iss", $landlord_id, $message, $type);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'notification_id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();