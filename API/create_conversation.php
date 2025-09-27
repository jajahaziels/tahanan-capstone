<?php
// create_conversation.php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db = "tahanandb";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$user1_id = $_POST['user1_id'] ?? null;
$user1_type = $_POST['user1_type'] ?? null;
$user2_id = $_POST['user2_id'] ?? null;
$user2_type = $_POST['user2_type'] ?? null;
$title = $_POST['title'] ?? "Private Chat";

if (!$user1_id || !$user2_id || !$user1_type || !$user2_type) {
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit;
}

// Check if conversation already exists
$check_stmt = $conn->prepare("
    SELECT c.id FROM conversations c
    JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = ?
    JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id = ?
    WHERE c.type = 'private'
    LIMIT 1
");
$check_stmt->bind_param("ii", $user1_id, $user2_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["success" => true, "conversation_id" => $row['id'], "existing" => true]);
    exit;
}

// Create new conversation
$conn->begin_transaction();

try {
    // Insert conversation
    $conv_stmt = $conn->prepare("INSERT INTO conversations (type, title, created_at) VALUES ('private', ?, NOW())");
    $conv_stmt->bind_param("s", $title);
    $conv_stmt->execute();
    $conversation_id = $conn->insert_id;

    // Add members
    $member_stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, ?, NOW())");
    
    $member_stmt->bind_param("iis", $conversation_id, $user1_id, $user1_type);
    $member_stmt->execute();
    
    $member_stmt->bind_param("iis", $conversation_id, $user2_id, $user2_type);
    $member_stmt->execute();

    $conn->commit();
    echo json_encode(["success" => true, "conversation_id" => $conversation_id, "existing" => false]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Failed to create conversation"]);
}

$conn->close();
?>