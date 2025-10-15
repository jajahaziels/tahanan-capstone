<?php
include '../includes/db.php';

$message = "";
$email = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email']));
  $otp = trim($_POST['otp']);
  $password = $_POST['password'];
  $confirm = $_POST['confirm_password'];

  if ($password !== $confirm) {
    $message = "<p class='error'>❌ Passwords do not match.</p>";
  } else {
    // Check OTP validity
    $stmt = $conn->prepare("SELECT * FROM reset_password WHERE email=? AND token=? LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
      $row = $result->fetch_assoc();

      if (time() <= strtotime($row['expires_at'])) {
        // OTP valid → reset password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updated = false;
        $tables = ['admintbl', 'landlordtbl', 'tenanttbl'];

        foreach ($tables as $table) {
          $stmt = $conn->prepare("UPDATE $table SET password=? WHERE email=?");
          if (!$stmt) {
            $message .= "<p class='error'>❌ Prepare failed on $table: " . $conn->error . "</p>";
            continue;
          }
          $stmt->bind_param("ss", $hashed, $email);
          $stmt->execute();

          if ($stmt->affected_rows > 0) {
            $updated = true;
          }
        }


        if ($updated) {
          $stmt = $conn->prepare("DELETE FROM reset_password WHERE email=?");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $message = "<p class='success'>✅ Password reset successful!</p>";
        } else {
          $message = "<p class='error'>❌ No user account found for this email in any table.</p>";
        }
      } else {
        $message = "<p class='error'>❌ OTP expired. Please request again.</p>";
      }
    } else {
      $message = "<p class='error'>❌ Invalid OTP.</p>";
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
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

    /* FORGOT PASSWORD BOX */
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
      transition: 0.2s ease;
    }

    .input-box input:focus {
      outline: none;
      background: #fff;
      border: 1px solid var(--main-color);
      box-shadow: 0 0 8px rgba(141, 11, 65, 0.2);
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
      transform: translateY(-2px);
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
      transition: 0.2s ease;
    }

    .back-link a:hover {
      color: #b30d4f;
      text-decoration: underline;
    }

    .success {
      color: green;
      margin-top: 10px;
      font-weight: 600;
    }

    .error {
      color: red;
      margin-top: 10px;
      font-weight: 600;
    }
  </style>
</head>

<body>
  <div class="forgot-wrapper">
       <?php
        if (!empty($message)) {
        echo $message;
       }
      ?>

      
    <h1>Reset Password</h1>
    <p>Please enter your OTP below to set your new password.</p>

      <form method="POST">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <div class="input-box">
          <input type="text" name="otp" placeholder="Enter the OTP" required>
        </div>
        <div class="input-box">
          
          <input type="password" name="password" placeholder="New Password" required>
        </div>
        <div class="input-box">
          <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        </div>
        <button type="submit" class="btn">Reset Password</button>
      </form>
    <p><a href="login.php" style="color:#8D0B41;">Back to Login</a></p>
  </div>
</body>

</html>
