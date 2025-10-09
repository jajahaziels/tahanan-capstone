<?php
// start_conversation.php
session_start();
header('Content-Type: application/json');

require_once '../connection.php';

// Check if user is logged in as tenant
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tenant') {
    echo json_encode([
        "success" => false,
        "error" => "You must be logged in as a tenant",
        "redirect" => "../LOGIN/login.php"
    ]);
    exit;
}

if (!isset($_SESSION['tenant_id'])) {
    echo json_encode([
        "success" => false,
        "error" => "Tenant ID not found in session",
        "redirect" => "../LOGIN/login.php"
    ]);
    exit;
}

$tenant_id = $_SESSION['tenant_id'];
$landlord_id = $_POST['landlord_id'] ?? null;
$property_id = $_POST['property_id'] ?? null;
$property_name = $_POST['property_name'] ?? null;

if (!$landlord_id || !$property_id) {
    echo json_encode([
        "success" => false,
        "error" => "Missing landlord_id or property_id"
    ]);
    exit;
}

// Verify landlord exists
$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM landlordtbl WHERE ID = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$landlord = $stmt->get_result()->fetch_assoc();

if (!$landlord) {
    echo json_encode([
        "success" => false,
        "error" => "Landlord not found"
    ]);
    exit;
}

// Verify tenant exists
$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();

if (!$tenant) {
    echo json_encode([
        "success" => false,
        "error" => "Tenant not found"
    ]);
    exit;
}

// Check if conversation already exists between this tenant and landlord
$stmt = $conn->prepare("
    SELECT c.id 
    FROM conversations c
    JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = ? AND cm1.user_type = 'tenant'
    JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id = ? AND cm2.user_type = 'landlord'
    WHERE c.type = 'private'
    LIMIT 1
");
$stmt->bind_param("ii", $tenant_id, $landlord_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    // Conversation already exists
    echo json_encode([
        "success" => true,
        "conversation_id" => $existing['id'],
        "existing" => true,
        "message" => "Conversation already exists"
    ]);
    exit;
}

// Create new conversation
$conn->begin_transaction();

try {
    // Create conversation title
    $title = "Chat between {$tenant['firstName']} {$tenant['lastName']} and {$landlord['firstName']} {$landlord['lastName']}";
    
    $stmt = $conn->prepare("INSERT INTO conversations (type, title, created_at) VALUES ('private', ?, NOW())");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $conversation_id = $conn->insert_id;

    // Add tenant as member
    $stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, 'tenant', NOW())");
    $stmt->bind_param("ii", $conversation_id, $tenant_id);
    $stmt->execute();
    
    // Add landlord as member
    $stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, 'landlord', NOW())");
    $stmt->bind_param("ii", $conversation_id, $landlord_id);
    $stmt->execute();

    // Add initial message from tenant about the property
    $initial_message = "Hi! I'm interested in your property" . ($property_name ? ": " . $property_name : "") . ".";
    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content, content_type, status, created_at) VALUES (?, ?, ?, 'text', 'active', NOW())");
    $stmt->bind_param("iis", $conversation_id, $tenant_id, $initial_message);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        "success" => true,
        "conversation_id" => $conversation_id,
        "existing" => false,
        "message" => "Conversation created successfully"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "success" => false,
        "error" => "Failed to create conversation: " . $e->getMessage()
    ]);
}

$conn->close();
?>