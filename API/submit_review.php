<?php
require_once '../connection.php';
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in as tenant
if (!isset($_SESSION['tenant_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in as a tenant to submit reviews'
    ]);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate input
$landlord_id = filter_input(INPUT_POST, 'landlord_id', FILTER_VALIDATE_INT);
$tenant_id = filter_input(INPUT_POST, 'tenant_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$review_text = trim($_POST['review_text'] ?? '');
$review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT); // For updates

// Validate tenant ID matches session
if ($tenant_id !== $_SESSION['tenant_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid tenant ID'
    ]);
    exit;
}

// Validate inputs
if (!$landlord_id || !$tenant_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: landlord_id or tenant_id'
    ]);
    exit;
}

if (!$rating || $rating < 1 || $rating > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Rating must be between 1 and 5'
    ]);
    exit;
}

if (empty($review_text)) {
    echo json_encode([
        'success' => false,
        'message' => 'Review text is required'
    ]);
    exit;
}

if (strlen($review_text) < 20) {
    echo json_encode([
        'success' => false,
        'message' => 'Review must be at least 20 characters long'
    ]);
    exit;
}

if (strlen($review_text) > 500) {
    echo json_encode([
        'success' => false,
        'message' => 'Review must not exceed 500 characters'
    ]);
    exit;
}

// Sanitize review text
$review_text = htmlspecialchars($review_text, ENT_QUOTES, 'UTF-8');

try {
    // Check if updating existing review
    if ($review_id) {
        // Update existing review
        $sql = "UPDATE reviews 
                SET rating = ?, 
                    review_text = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? 
                AND tenant_id = ? 
                AND landlord_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiii", $rating, $review_text, $review_id, $tenant_id, $landlord_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Review updated successfully',
                'review_id' => $review_id
            ]);
        } else {
            throw new Exception('Failed to update review');
        }
        
    } else {
        // Check if review already exists (shouldn't happen but double-check)
        $check_sql = "SELECT id FROM reviews WHERE landlord_id = ? AND tenant_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $landlord_id, $tenant_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing = $check_result->fetch_assoc();
            echo json_encode([
                'success' => false,
                'message' => 'You have already reviewed this landlord. Please edit your existing review.',
                'existing_review_id' => $existing['id']
            ]);
            exit;
        }
        
        // Insert new review
        $sql = "INSERT INTO reviews (landlord_id, tenant_id, rating, review_text, created_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $landlord_id, $tenant_id, $rating, $review_text);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Review submitted successfully',
                'review_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to submit review');
        }
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>