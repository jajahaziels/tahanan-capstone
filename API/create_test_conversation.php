<?php
// create_test_conversation.php - For testing purposes only
require_once '../connection.php';

// Change these to your actual IDs
$landlord_id = 1; // Change this to your actual landlord ID
$tenant_id = 4;   // Change this to your actual tenant ID

// Check if landlord exists - FIXED: using lowercase column names
$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM landlordtbl WHERE ID = ?");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$landlord = $stmt->get_result()->fetch_assoc();

if (!$landlord) {
    die("Landlord with ID $landlord_id not found. Check your landlordtbl table.");
}

// Check if tenant exists - FIXED: using lowercase column names
$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();

if (!$tenant) {
    die("Tenant with ID $tenant_id not found. Check your tenanttbl table.");
}

// Check if conversation already exists
$stmt = $conn->prepare("
    SELECT c.id FROM conversations c
    JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = ? AND cm1.user_type = 'landlord'
    JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id = ? AND cm2.user_type = 'tenant'
    LIMIT 1
");
$stmt->bind_param("ii", $landlord_id, $tenant_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    echo "<h3>Conversation already exists!</h3>";
    echo "<p>Conversation ID: " . $existing['id'] . "</p>";
    echo "<p><a href='../LANDLORD/landlord-message.php'>Go to Landlord Messages</a></p>";
    echo "<p><a href='../TENANT/tenant-message.php'>Go to Tenant Messages</a></p>";
    exit;
}

// Create new conversation
$conn->begin_transaction();

try {
    // Insert conversation
    $title = "Chat between {$landlord['firstName']} {$landlord['lastName']} and {$tenant['firstName']} {$tenant['lastName']}";
    $stmt = $conn->prepare("INSERT INTO conversations (type, title, created_at) VALUES ('private', ?, NOW())");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $conversation_id = $conn->insert_id;

    // Add landlord as member
    $stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, 'landlord', NOW())");
    $stmt->bind_param("ii", $conversation_id, $landlord_id);
    $stmt->execute();
    
    // Add tenant as member
    $stmt = $conn->prepare("INSERT INTO conversation_members (conversation_id, user_id, user_type, joined_at) VALUES (?, ?, 'tenant', NOW())");
    $stmt->bind_param("ii", $conversation_id, $tenant_id);
    $stmt->execute();

    // Add a sample message from tenant
    $sample_message = "Hello! I have a question about the property.";
    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content, content_type, status, created_at) VALUES (?, ?, ?, 'text', 'active', NOW())");
    $stmt->bind_param("iis", $conversation_id, $tenant_id, $sample_message);
    $stmt->execute();

    $conn->commit();
    
    echo "<h3 style='color: green;'>✓ Test conversation created successfully!</h3>";
    echo "<p><strong>Conversation ID:</strong> $conversation_id</p>";
    echo "<p><strong>Between:</strong> {$landlord['firstName']} {$landlord['lastName']} (Landlord) and {$tenant['firstName']} {$tenant['lastName']} (Tenant)</p>";
    echo "<hr>";
    echo "<p><a href='../LANDLORD/landlord-message.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Go to Landlord Messages</a></p>";
    echo "<p><a href='../TENANT/tenant-message.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px;'>Go to Tenant Messages</a></p>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<h3 style='color: red;'>✗ Error creating conversation:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>