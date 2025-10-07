<?php
require_once '../connection.php';

$email = "tahanan@gmail.com";
$newPassword = "Admin123!";
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admintbl SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);

if ($stmt->execute()) {
    echo "✅ <strong>Password updated successfully!</strong><br><br>";
    echo "📧 Email: tahanan@gmail.com<br>";
    echo "🔑 Password: Admin123!<br><br>";
    echo "<a href='../LOGIN/login.php'>Go to Login Page</a><br><br>";
    echo "⚠️ <strong>DELETE THIS FILE NOW!</strong>";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>