<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../connection.php';

$user_id = $_GET['user_id'] ?? null;
$user_type = $_GET['user_type'] ?? null;

if (!$user_id || !$user_type) {
    echo json_encode(["success" => false, "error" => "Missing user_id or user_type"]);
    exit;
}

// Get conversations for this user
$stmt = $conn->prepare("
    SELECT 
        c.id as conversation_id,
        c.title,
        c.type,
        c.created_at,
        cm_other.user_id as other_user_id,
        cm_other.user_type as other_user_type,
        CASE 
            WHEN cm_other.user_type = 'landlord' THEN 
                CONCAT(IFNULL(l.firstName, ''), ' ', IFNULL(l.middleName, ''), ' ', IFNULL(l.lastName, ''))
            WHEN cm_other.user_type = 'tenant' THEN 
                CONCAT(IFNULL(t.firstName, ''), ' ', IFNULL(t.middleName, ''), ' ', IFNULL(t.lastName, ''))
        END as other_user_name,
        CASE 
            WHEN cm_other.user_type = 'landlord' THEN l.email
            WHEN cm_other.user_type = 'tenant' THEN t.email
        END as other_user_email,
        (SELECT content FROM messages WHERE conversation_id = c.id AND status = 'active' ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id AND status = 'active' ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM conversations c
    INNER JOIN conversation_members cm ON cm.conversation_id = c.id 
        AND cm.user_id = ? 
        AND cm.user_type = ?
    INNER JOIN conversation_members cm_other ON cm_other.conversation_id = c.id 
        AND (cm_other.user_id != ? OR cm_other.user_type != ?)
    LEFT JOIN landlordtbl l ON cm_other.user_type = 'landlord' AND cm_other.user_id = l.ID
    LEFT JOIN tenanttbl t ON cm_other.user_type = 'tenant' AND cm_other.user_id = t.ID
    WHERE c.type = 'private'
    ORDER BY COALESCE(last_message_time, c.created_at) DESC
");

$stmt->bind_param("isis", $user_id, $user_type, $user_id, $user_type);
$stmt->execute();
$result = $stmt->get_result();

$conversations = [];
while ($row = $result->fetch_assoc()) {
    // Clean up the name
    $row['other_user_name'] = trim(preg_replace('/\s+/', ' ', $row['other_user_name']));
    $conversations[] = $row;
}

echo json_encode([
    "success" => true,
    "conversations" => $conversations
]);

$stmt->close();
$conn->close();
?>