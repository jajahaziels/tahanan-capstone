<?php
session_start();
include '../includes/db.php';
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email']));
    $role = $_POST['role'] ?? 'tenant'; // tenant or landlord
    $table = ($role === 'landlord') ? 'landlordtbl' : 'tenanttbl';

    // Check if account exists
    $stmt = $conn->prepare("SELECT ID FROM $table WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $_SESSION['error'] = "No account found with this email.";
        header("Location: login.php");
        exit();
    }

    // Generate OTP
    $otp = rand(100000, 999999); // 6-digit
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['user_type'] = $role;
    $_SESSION['otp_expiry'] = time() + 300; // 10 minutes

    // Send OTP via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jajasison07@gmail.com';
        $mail->Password = 'aebfllyitmpjvzqz';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jajasison07@gmail.com', 'TAHANAN');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Your OTP code is <b>$otp</b>. It expires in 10 minutes.";

        $mail->send();
        $_SESSION['success'] = "OTP sent to your email!";
        header("Location: otp_verify.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Could not send OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: login.php");
        exit();
    }
}
?>


