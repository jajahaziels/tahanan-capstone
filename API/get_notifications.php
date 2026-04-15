<?php
// ============================================
// API/get_notifications.php
// Returns unread (and recent read) notifications for a user.
// Called by the global notification poller in the frontend.
// ============================================

require_once __DIR__ . '/../connection.php';

header('Content-Type: application/json');

$user_id   = isset($_GET['user_id'])   ? (int)$_GET['user_id']        : 0;
$user_type = isset($_GET['user_type']) ? trim($_GET['user_type'])      : '';

if (!$user_id || !in_array($user_type, ['landlord', 'tenant'])) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id or user_type']);
    exit;
}

// Return last 20 notifications (unread first, then recent read ones)
$stmt = $conn->prepare("
    SELECT id, message, type, link, is_read, created_at
    FROM notifications
    WHERE user_id = ? AND user_type = ?
    ORDER BY is_read ASC, created_at DESC
    LIMIT 20
");
$stmt->bind_param("is", $user_id, $user_type);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count  = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id'         => (int)$row['id'],
        'message'    => $row['message'],
        'type'       => $row['type'],
        'link'       => $row['link'] ?? null,
        'is_read'    => (bool)$row['is_read'],
        'created_at' => $row['created_at'],
        'time_ago'   => getTimeAgo($row['created_at']),
    ];
    if (!(int)$row['is_read']) {
        $unread_count++;
    }
}

$stmt->close();

echo json_encode([
    'success'      => true,
    'notifications'=> $notifications,
    'unread_count' => $unread_count,
]);

// ── Helper ──────────────────────────────────
function getTimeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}