<?php
require_once '../session_auth.php';
require_once '../connection.php';

// Check if admin is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get parameters
$landlord_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : null;

// Validate inputs
if (!$landlord_id || !in_array($action, ['verified', 'rejected'])) {
    header("Location: verify.php?error=invalid");
    exit();
}

// If rejecting, require a rejection reason
if ($action === 'rejected' && empty($rejection_reason)) {
    header("Location: verify.php?error=no_reason");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get landlord info for email notification (optional)
    $landlord_query = "SELECT firstName, lastName, email FROM landlordtbl WHERE ID = ?";
    $stmt = $conn->prepare($landlord_query);
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $landlord = $stmt->get_result()->fetch_assoc();
    
    if (!$landlord) {
        throw new Exception("Landlord not found");
    }
    
    // Update verification status
    if ($action === 'verified') {
        // Approve the landlord
        $update_query = "UPDATE landlordtbl 
                        SET verification_status = 'verified',
                            admin_rejection_reason = NULL,
                            verified_date = NOW()
                        WHERE ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $landlord_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update landlord status");
        }
        
        // Optional: Send approval email
        // sendApprovalEmail($landlord['email'], $landlord['firstName']);
        
        // Log the action (optional)
        logAdminAction($conn, $_SESSION['username'], 'verified', $landlord_id);
        
        $conn->commit();
        header("Location: verify.php?success=verified");
        exit();
        
    } elseif ($action === 'rejected') {
        // Reject the landlord with reason
        $update_query = "UPDATE landlordtbl 
                        SET verification_status = 'rejected',
                            admin_rejection_reason = ?
                        WHERE ID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $rejection_reason, $landlord_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update landlord status");
        }
        
        // Optional: Send rejection email
        // sendRejectionEmail($landlord['email'], $landlord['firstName'], $rejection_reason);
        
        // Log the action (optional)
        logAdminAction($conn, $_SESSION['username'], 'rejected', $landlord_id, $rejection_reason);
        
        $conn->commit();
        header("Location: verify.php?success=rejected");
        exit();
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Verification error: " . $e->getMessage());
    header("Location: verify.php?error=database");
    exit();
}

// Helper function to log admin actions (optional but recommended)
function logAdminAction($conn, $admin_username, $action, $landlord_id, $notes = null) {
    // Create admin_actions table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS admin_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_username VARCHAR(100),
        action_type VARCHAR(50),
        target_landlord_id INT,
        notes TEXT,
        action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin (admin_username),
        INDEX idx_landlord (target_landlord_id),
        INDEX idx_timestamp (action_timestamp)
    )";
    $conn->query($create_table);
    
    // Log the action
    $log_query = "INSERT INTO admin_actions (admin_username, action_type, target_landlord_id, notes) 
                  VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("ssis", $admin_username, $action, $landlord_id, $notes);
    $stmt->execute();
}

// Optional: Email notification functions
function sendApprovalEmail($email, $name) {
    // Implement email sending logic here
    // Example using PHP mail() or a service like SendGrid, Mailgun, etc.
    
    $subject = "Your Landlord Verification Has Been Approved!";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Verification Approved!</h1>
            </div>
            <div class='content'>
                <p>Hello $name,</p>
                <p>Great news! Your landlord verification has been approved by our admin team.</p>
                <p>You can now:</p>
                <ul>
                    <li>Create property listings</li>
                    <li>Manage your properties</li>
                    <li>Connect with potential tenants</li>
                </ul>
                <p>Thank you for completing the verification process and helping us maintain a safe rental platform.</p>
                <a href='https://yourdomain.com/landlord/add_property.php' class='button'>Create Your First Listing</a>
                <p style='margin-top: 30px; font-size: 12px; color: #666;'>
                    If you have any questions, please contact our support team.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@yourdomain.com" . "\r\n";
    
    // Uncomment to send email
    // mail($email, $subject, $message, $headers);
}

function sendRejectionEmail($email, $name, $reason) {
    // Implement email sending logic here
    
    $subject = "Action Required: Landlord Verification Update";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .reason-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .button { display: inline-block; padding: 12px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚠️ Verification Requires Attention</h1>
            </div>
            <div class='content'>
                <p>Hello $name,</p>
                <p>Thank you for submitting your landlord verification documents. Unfortunately, we need you to resubmit your documentation due to the following issue:</p>
                <div class='reason-box'>
                    <strong>Reason for Rejection:</strong>
                    <p>" . nl2br(htmlspecialchars($reason)) . "</p>
                </div>
                <p>Please review the feedback above and resubmit your documents with the necessary corrections.</p>
                <p><strong>What to do next:</strong></p>
                <ol>
                    <li>Log in to your landlord account</li>
                    <li>Go to the verification page</li>
                    <li>Upload the corrected documents</li>
                    <li>Submit for review again</li>
                </ol>
                <a href='https://yourdomain.com/landlord/verification.php' class='button'>Resubmit Documents</a>
                <p style='margin-top: 30px; font-size: 12px; color: #666;'>
                    If you have questions about this rejection, please contact our support team.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@yourdomain.com" . "\r\n";
    
    // Uncomment to send email
    // mail($email, $subject, $message, $headers);
}
?>