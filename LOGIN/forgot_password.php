<?php
include '../includes/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = strtolower(trim($_POST['email']));

  // Check if email exists in any user table
  $found = false;
  $tables = ['admintbl', 'landlordtbl', 'tenanttbl'];
  foreach ($tables as $table) {
    $stmt = $conn->prepare("SELECT email FROM $table WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
      $found = true;
      break;
    }
  }

  if (!$found) {
    $message = "<p class='error'>❌ Email not found.</p>";
  } else {
    // Generate OTP and expiry time
    $otp = rand(100000, 999999);
    $expires_at = date("Y-m-d H:i:s", time() + 600); // valid for 10 minutes

    // Ensure reset_password table has a UNIQUE email column
    $conn->query("ALTER TABLE reset_password ADD UNIQUE (email)");

    // Insert or update OTP (prevents duplicates)
    $stmt = $conn->prepare("
        INSERT INTO reset_password (email, token, expires_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ");
    $stmt->bind_param("sss", $email, $otp, $expires_at);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
      // Send OTP via email
      try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = "jajasison07@gmail.com";
        $mail->Password = "aebfllyitmpjvzqz"; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jajasison07@gmail.com', 'Tahanan');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Tahanan Password Reset OTP';
        $mail->Body = "
          <h3>Your One-Time Password (OTP) is:</h3>
          <h1 style='color:#8D0B41;'>$otp</h1>
          <p>This OTP is valid for 5 minutes. Do not share it with anyone.</p>
        ";

        $mail->send();
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
      } catch (Exception $e) {
        $message = "<p class='error'>❌ Mail failed: " . $mail->ErrorInfo . "</p>";
      }
    } else {
      $message = "<p class='error'>❌ Failed to insert OTP into database.</p>";
    }
  }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="signup.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');

    :root {
      --bg-color: #fafafa;
      --bg-alt-color: #dfdfdf;
      --main-color: #8D0B41;
      --text-color: #42505A;
      --text-alt-color: #647887;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      list-style: none;
      font-family: "Montserrat", sans-serif;
    }

    /* BODY */
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(90deg, var(--bg-color), var(--bg-alt-color));
      color: var(--text-color);
    }

    /* RESET PASSWORD BOX */
    .forgot-wrapper {
      width: 400px;
      padding: 30px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .forgot-wrapper h1 {
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--main-color);
    }

    .forgot-wrapper p {
      font-size: 14px;
      margin-bottom: 20px;
      color: var(--text-alt-color);
    }

    /* INPUT BOX */
    .input-box {
      margin: 20px 0;
    }

    .input-box input {
      width: 100%;
      padding: 13px 20px;
      background: var(--bg-alt-color);
      border-radius: 10px;
      border: none;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-color);
    }

    .input-box input::placeholder {
      font-weight: 500;
      font-size: 15px;
      color: var(--text-alt-color);
    }

    /* BUTTON */
    .btn {
      width: 100%;
      height: 45px;
      border-radius: 8px;
      border: none;
      background: var(--main-color);
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
      color: var(--bg-color);
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s ease;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .btn:hover {
      background: #b30d4f;
    }

    /* LINKS & MESSAGES */
    .back-link {
      margin-top: 15px;
      font-size: 14px;
    }

    .back-link a {
      color: var(--main-color);
      text-decoration: none;
      font-weight: 500;
    }

    .back-link a:hover {
      color: #b30d4f;
    }

    .success {
      color: green;
      margin-top: 10px;
    }

    .error {
      color: red;
      margin-top: 10px;
    }
  </style>
</head>

<body>
  <div class="forgot-wrapper">
    <!-- success/error message -->
    <?php
    if (!empty($message)) {
      echo $message;
    }
    ?>

    <h1>Forgot Password</h1>
    <p>Enter your email to receive an OTP.</p>
    
    <form method="POST" action="">
      <div class="input-box">
        <input type="email" name="email" placeholder="Enter your email" required>
      </div>
      <button type="submit" class="btn">Send OTP</button>
    </form>

    <p class="back-link"><a href="login.php">Back to Login</a></p>
  </div>
</body>

</html>