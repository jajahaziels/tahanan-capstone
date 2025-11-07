<?php
// start_conversation.php
session_start();
header('Content-Type: application/json');

require_once '../connection.php';

// Check if user is logged in (either tenant or landlord)
if (!isset($_SESSION['user_type'])) {
    echo json_encode([
        "success" => false,
        "error" => "You must be logged in",
        "redirect" => "../LOGIN/login.php"
    ]);
    exit;
}

$user_type = $_SESSION['user_type'];

// Get current user's ID based on their type
if ($user_type === 'tenant') {
    if (!isset($_SESSION['tenant_id'])) {
        echo json_encode([
            "success" => false,
            "error" => "Tenant ID not found in session",
            "redirect" => "../LOGIN/login.php"
        ]);
        exit;
    }
    $current_user_id = $_SESSION['tenant_id'];
} elseif ($user_type === 'landlord') {
    if (!isset($_SESSION['landlord_id'])) {
        echo json_encode([
            "success" => false,
            "error" => "Landlord ID not found in session",
            "redirect" => "../LOGIN/login.php"
        ]);
        exit;
    }
    $current_user_id = $_SESSION['landlord_id'];
} else {
    echo json_encode([
        "success" => false,
        "error" => "Invalid user type"
    ]);
    exit;
}

// Get parameters - support both GET and POST
$landlord_id = $_POST['landlord_id'] ?? $_GET['landlord_id'] ?? null;
$tenant_id = $_POST['tenant_id'] ?? $_GET['tenant_id'] ?? null;
$property_id = $_POST['property_id'] ?? $_GET['property_id'] ?? null;
$property_name = $_POST['property_name'] ?? $_GET['property_name'] ?? null;

// Determine the other party based on who's logged in
if ($user_type === 'tenant') {
    $tenant_id = $current_user_id; // Current user is the tenant
    if (!$landlord_id) {
        echo json_encode([
            "success" => false,
            "error" => "Missing landlord_id"
        ]);
        exit;
    }
} else { // landlord
    $landlord_id = $current_user_id; // Current user is the landlord
    if (!$tenant_id) {
        echo json_encode([
            "success" => false,
            "error" => "Missing tenant_id"
        ]);
        exit;
    }
}

if (!$property_id) {
    echo json_encode([
        "success" => false,
        "error" => "Missing property_id"
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
    // Conversation already exists - redirect to it
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
    $title = "Chat about " . ($property_name ? $property_name : "Property #" . $property_id);
    
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

    // Add initial message - from whoever started the conversation
    if ($user_type === 'tenant') {
        $initial_message = "Hi! I'm interested in your property" . ($property_name ? ": " . $property_name : "") . ".";
    } else {
        $initial_message = "Hello! I'm reaching out about" . ($property_name ? ": " . $property_name : " this property") . ".";
    }
    
    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content, content_type, status, created_at) VALUES (?, ?, ?, 'text', 'active', NOW())");
    $stmt->bind_param("iis", $conversation_id, $current_user_id, $initial_message);
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