<?php
header('Content-Type: application/json');

require_once '../connection.php';
require_once '../session_auth.php';

// Check if tenant is logged in
if (!isset($_SESSION['tenant_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'You must be logged in to submit a report. Please log in and try again.'
    ]);
    exit;
}

$tenant_id = $_SESSION['tenant_id'];

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$landlord_id = isset($_POST['landlord_id']) ? intval($_POST['landlord_id']) : 0;
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$incident_date = isset($_POST['incident_date']) && !empty($_POST['incident_date']) ? $_POST['incident_date'] : null;
$priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
$is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

// Validate inputs
if ($landlord_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid landlord selected'
    ]);
    exit;
}

if (empty($category)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please select a report category'
    ]);
    exit;
}

if (empty($subject)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please enter a subject/title'
    ]);
    exit;
}

if (empty($description)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please provide a detailed description'
    ]);
    exit;
}

if (strlen($description) < 50) {
    echo json_encode([
        'success' => false, 
        'message' => 'Description must be at least 50 characters. Currently: ' . strlen($description) . ' characters'
    ]);
    exit;
}

// Check if reportstbl table exists
$table_check = $conn->query("SHOW TABLES LIKE 'reportstbl'");
if ($table_check->num_rows == 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: Reports table not found. Please contact administrator.'
    ]);
    exit;
}

// Check if tenant already reported this landlord in the last 30 days
$check_query = "SELECT ID FROM reportstbl 
                WHERE tenant_id = ? 
                AND landlord_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)";
$check_stmt = $conn->prepare($check_query);

if (!$check_stmt) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$check_stmt->bind_param("ii", $tenant_id, $landlord_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'You have already reported this landlord in the last 30 days. Please wait before submitting another report.'
    ]);
    exit;
}

// Insert report
// Note: 8 placeholders = 8 values
$insert_query = "INSERT INTO reportstbl 
    (tenant_id, landlord_id, category, subject, description, incident_date, priority, is_anonymous, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

$stmt = $conn->prepare($insert_query);

if (!$stmt) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

// FIXED: 8 parameters matching the type string "iissssii"
// i = tenant_id (integer)
// i = landlord_id (integer)
// s = category (string)
// s = subject (string)
// s = description (string)
// s = incident_date (string - can be NULL)
// s = priority (string)
// i = is_anonymous (integer)
$stmt->bind_param(
    "iisssssi", 
    $tenant_id, 
    $landlord_id, 
    $category, 
    $subject, 
    $description, 
    $incident_date, 
    $priority,
    $is_anonymous
);

if ($stmt->execute()) {
    $report_id = $conn->insert_id;
    
    // Log the report creation (optional - check if table exists first)
    $log_table_check = $conn->query("SHOW TABLES LIKE 'report_actions_log'");
    if ($log_table_check && $log_table_check->num_rows > 0) {
        $log_query = "INSERT INTO report_actions_log (report_id, admin_username, action_type, action_details, created_at) 
                      VALUES (?, 'SYSTEM', 'report_created', 'Report submitted by tenant', NOW())";
        $log_stmt = $conn->prepare($log_query);
        if ($log_stmt) {
            $log_stmt->bind_param("i", $report_id);
            $log_stmt->execute();
            $log_stmt->close();
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Report submitted successfully! Our admin team will review it shortly.',
        'report_id' => $report_id
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to submit report. Please try again.',
        'debug' => $stmt->error
    ]);
}

$stmt->close();
$check_stmt->close();
$conn->close();
?>