<?php
session_start();
header('Content-Type: application/json');


require_once '../../connection.php';

if(!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$alert_type = $data['alert_type'];
$title = $data['title'];
$message = $data['message'];
$severity = $data['severity'];
$admin_name = $_SESSION['username'];

// Insert alert into database
$stmt = $conn->prepare("INSERT INTO emergency_alerts (alert_type, title, message, severity, sent_by_admin) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $alert_type, $title, $message, $severity, $admin_name);
$stmt->execute();
$alert_id = $conn->insert_id;

// Get all tenants
$tenants = $conn->query("SELECT ID as user_id, 'tenant' as user_type FROM tenanttbl WHERE status = 'active'");
// Get all landlords  
$landlords = $conn->query("SELECT ID as user_id, 'landlord' as user_type FROM landlordtbl WHERE status = 'active'");

$total_recipients = 0;

// Insert read status for tenants
$stmt2 = $conn->prepare("INSERT INTO user_alert_read_status (alert_id, user_id, user_type, is_read) VALUES (?, ?, ?, 0)");
while($tenant = $tenants->fetch_assoc()) {
    $stmt2->bind_param("iis", $alert_id, $tenant['user_id'], $tenant['user_type']);
    $stmt2->execute();
    $total_recipients++;
}

// Insert read status for landlords
while($landlord = $landlords->fetch_assoc()) {
    $stmt2->bind_param("iis", $alert_id, $landlord['user_id'], $landlord['user_type']);
    $stmt2->execute();
    $total_recipients++;
}

echo json_encode(['success' => true, 'alert_id' => $alert_id, 'recipients' => $total_recipients]);
?>