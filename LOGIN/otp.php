<?php
session_start();
include '../includes/db.php';

$message = "";

// If form is submitted (verify OTP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
  $enteredOtp = trim($_POST['otp']);

  if (!isset($_SESSION['device_otp'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry'])) {
    $_SESSION['error'] = "OTP expired. Please login again.";
    header("Location: login.php");
    exit();
  }

  if ($enteredOtp != $_SESSION['device_otp'] || time() > $_SESSION['otp_expiry']) {
    $message = "<p class='error' style='color:red; font-weight:bold;'>❌ Invalid or expired OTP.</p>";
  } else {
    // Mark device as trusted
    $stmt = $conn->prepare("
            INSERT INTO trusted_devices (user_id, device_hash, last_ip, last_used, role)
            VALUES (?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE last_ip=VALUES(last_ip), last_used=NOW()
        ");
    $stmt->bind_param(
      "isss",
      $_SESSION['otp_user_id'],
      $_SESSION['otp_device_hash'],
      $_SERVER['REMOTE_ADDR'],
      $_SESSION['otp_role']
    );
    $stmt->execute();

    // Set login session
    $_SESSION['user_id'] = $_SESSION['otp_user_id'];
    $_SESSION['username'] = $_SESSION['user_name'];
    $_SESSION['user_type'] = $_SESSION['otp_role'];

    // Clear OTP session variables
    unset($_SESSION['device_otp'], $_SESSION['otp_user_id'], $_SESSION['otp_device_hash'], $_SESSION['otp_role'], $_SESSION['otp_expiry'], $_SESSION['user_name']);

    // Redirect by role
    if ($_SESSION['user_type'] === 'landlord') {
      header("Location: /TAHANAN/LANDLORD/landlord-properties.php");
    } else {
      header("Location: /TAHANAN/TENANT/tenant.php");
    }
    exit();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Verify Email</title>
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
      font-family: "Montserrat", sans-serif;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(90deg, var(--bg-color), var(--bg-alt-color));
      color: var(--text-color);
    }

    .verify-wrapper {
      width: 500px;
      height: 80vh;
      padding: 40px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 0 25px rgba(0, 0, 0, 0.15);
      text-align: center;
      align-items: center;
    }

    .verify-wrapper h1 {
      padding-top: 8px;
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--main-color);
    }

    .verify-wrapper h2 {
      font-size: 17px;
      font-weight: 400;
      color: var(--text-alt-color);
      margin-bottom: 30px;
      line-height: 1.4;
      padding-top: 13px;
    }

    .verify-wrapper p {
      font-size: 14px;
      margin-bottom: 10px;
      color: var(--text-color);
      font-weight: 600;
      text-align: left;
      padding-top: 30px;
      padding-left: 20px;
    }

    .verify-wrapper h4 {
      font-size: 14px;
      margin-bottom: 10px;
      color: var(--text-color);
      font-weight: 600;
      text-align: center;
      padding-top: 10px;
      padding-left: 20px;
    }

    .verify-wrapper h5 {
      font-size: 14px;
      margin-bottom: 10px;
      color: var(--text-color);
      font-weight: 600;
      text-align: center;
      padding-top: 40px;
      padding-left: 20px;
    }

    .verify-wrapper h6 {
      font-size: 14px;
      margin-bottom: 10px;
      color: var(--text-alt-color);
      font-weight: 600;
      text-align: center;
      padding-top: 20px;
      padding-left: 20px;
    }

    .input-box {
      margin: 15px 0 25px 0;
    }

    .input-box input {
      width: 95%;
      padding: 15px;
      background: var(--bg-alt-color);
      border-radius: 15px;
      border: none;
      font-size: 16px;
      font-weight: 600;
      color: var(--text-color);
      text-align: center;
    }

    .input-box input::placeholder {
      color: var(--text-alt-color);
      font-weight: 500;
    }

    .btn {
      width: 95%;
      padding: 15px;
      background: var(--main-color);
      color: var(--bg-color);
      font-size: 16px;
      font-weight: 600;
      border: none;
      border-radius: 15px;
      cursor: pointer;
      transition: 0.3s ease;
    }

    .btn:hover {
      background: #b30d4f;
    }

    .resend-text {
      margin-top: 20px;
      font-size: 14px;
      color: var(--text-alt-color);
    }

    .resend-text a {
      color: var(--main-color);
      text-decoration: none;
      font-weight: 500;
    }

    .resend-text a:hover {
      color: #b30d4f;
    }

    .back-link {
      margin-top: 10px;
      font-size: 14px;
    }

    .back-link a {
      color: var(--main-color);
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a {
      color: var(--main-color);
      text-decoration: none;
      font-weight: 500;
    }

    .back-link a:hover {
      color: #b30d4f;
    }
  </style>
</head>

<body>
  <div class="verify-wrapper">
    <h1>Verify your email</h1>
    <h2>We've sent a verification code to your email address. Please enter it below.</h2>

    <?= $message ?>

    <p>Verification Code</p>
    <form method="POST" action="">
      <div class="input-box">
        <input type="text" name="otp" placeholder="Enter 6-digit code" required>
      </div>
      <button type="submit" class="btn">Verify Email</button>
    </form>

    <h4 class="resend-text">
      Didn't receive the code?
      <a href="#" id="resend-link">Resend</a>
      <span id="timer"></span>
    </h4>
    <h5 class="back-link"><a href="signup.php">Back to Signup</a></h5>
    <h6 class="back-link"><a href="login.php">Already have an account? Login</a></h6>
  </div>

  <script>
    const resendLink = document.getElementById('resend-link');
    const timerSpan = document.getElementById('timer');
    let countdown = 30;
    let timer;

    function startTimer() {
      resendLink.style.pointerEvents = 'none';
      resendLink.style.opacity = '0.5';
      timerSpan.textContent = ` (Wait ${countdown}s)`;

      timer = setInterval(() => {
        countdown--;
        timerSpan.textContent = ` (Wait ${countdown}s)`;
        if (countdown <= 0) {
          clearInterval(timer);
          resendLink.style.pointerEvents = 'auto';
          resendLink.style.opacity = '1';
          timerSpan.textContent = '';
          countdown = 30;
        }
      }, 1000);
    }

    resendLink.addEventListener('click', function (e) {
      e.preventDefault();

      fetch('resend_otp.php', {
        method: 'GET'
      })
        .then(response => response.json())
        .then(data => {
          const msgBox = document.createElement('p');
          msgBox.style.marginTop = '15px';
          msgBox.style.fontWeight = '600';

          if (data.success) {
            msgBox.style.color = 'green';
          } else {
            msgBox.style.color = 'red';
          }

          msgBox.textContent = data.message;
          document.querySelector('.verify-wrapper').appendChild(msgBox);

          startTimer();
        })
        .catch(error => {
          console.error('Error:', error);
        });
    });
  </script>
</body>

</html>