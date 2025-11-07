<?php
session_start();
require_once '../connection.php';

$tenant_id = $_SESSION['tenant_id'] ?? 1;
$landlord_id = 1; // Change this to an actual landlord ID

echo "<h2>Creating Test Conversation</h2>";
echo "Tenant ID: $tenant_id<br>";
echo "Landlord ID: $landlord_id<br><br>";

// Check if landlord exists
$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM landlordtbl WHERE ID = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$landlord = $stmt->get_result()->fetch_assoc();

if (!$landlord) {
    die("❌ Landlord not found! Please change \$landlord_id to a valid landlord ID.");
}

echo "✅ Landlord found: {$landlord['firstName']} {$landlord['lastName']}<br><br>";

// Check if tenant exists
$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();

if (!$tenant) {
    die("❌ Tenant not found!");
}

echo "✅ Tenant found: {$tenant['firstName']} {$tenant['lastName']}<br><br>";

// Check if conversation already exists
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
    echo "✅ Conversation already exists! ID: {$existing['id']}<br>";
    $conversation_id = $existing['id'];
} else {
    echo "Creating new conversation...<br>";
    
    $conn->begin_transaction();
    
    try {
        // Create conversation
        $title = "Chat between {$tenant['firstName']} and {$landlord['firstName']}";
        $stmt = $conn->prepare("INSERT INTO conversations (type, title, created_at) VALUES ('private', ?, NOW())");
        $stmt->bind_param("s", $title);
        $stmt->execute();
        $conversation_id = $conn->insert_id;
        
        echo "✅ Conversation created! ID: $conversation_id<br>";
        
        // Add tenant as member
        $stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, 'tenant', NOW())");
        $stmt->bind_param("ii", $conversation_id, $tenant_id);
        $stmt->execute();
        echo "✅ Tenant added as member<br>";
        
        // Add landlord as member
        $stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, 'landlord', NOW())");
        $stmt->bind_param("ii", $conversation_id, $landlord_id);
        $stmt->execute();
        echo "✅ Landlord added as member<br>";
        
        // Add initial message
        $initial_message = "Hi! This is a test conversation.";
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content, content_type, status, created_at) VALUES (?, ?, ?, 'text', 'active', NOW())");
        $stmt->bind_param("iis", $conversation_id, $tenant_id, $initial_message);
        $stmt->execute();
        echo "✅ Initial message added<br>";
        
        $conn->commit();
        echo "<br>✅ <strong>SUCCESS! Conversation created successfully!</strong><br>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "❌ Error: " . $e->getMessage();
    }
}

echo "<br><br>";
echo "<a href='tenant-messages.php?conversation_id=$conversation_id'>Open Conversation</a><br>";
echo "<a href='test-conversations.php'>Test get_conversations.php</a>";

$conn->close();
?>