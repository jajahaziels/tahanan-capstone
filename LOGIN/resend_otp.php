<?php
session_start();
include '../includes/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Same SMTP credentials as login.php
$SMTP_USERNAME = 'jajasison07@gmail.com';
$SMTP_PASSWORD = 'aebfllyitmpjvzqz';

header('Content-Type: application/json');

// ✅ Check if required session variables still exist
if (!isset($_SESSION['otp_user_id'], $_SESSION['otp_device_hash'], $_SESSION['otp_role'], $_SESSION['otp_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit();
}

// ✅ Generate new OTP and update session
$newOtp = rand(100000, 999999);
$_SESSION['device_otp'] = $newOtp;
$_SESSION['otp_expiry'] = time() + 600; // 10 minutes

$to = $_SESSION['otp_email'];

// ✅ Send email using PHPMailer (same as login.php)
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $SMTP_USERNAME;
    $mail->Password = $SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($SMTP_USERNAME, 'TAHANAN');
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = 'TAHANAN Login OTP (Resent)';
    $mail->Body = "<h3>TAHANAN</h3>
                   <p>Your new OTP code is: <b>$newOtp</b></p>
                   <p>It expires in 10 minutes.</p>";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => '✅ A new OTP has been sent to your email.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '⚠️ Failed to resend OTP. Error: ' . $mail->ErrorInfo
    ]);
}
