<?php
session_start();
include '../includes/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$SMTP_USERNAME = 'jajasison07@gmail.com';
$SMTP_PASSWORD = 'aebfllyitmpjvzqz';

$errorMsg = "";

function getDeviceHash()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return hash('sha256', $userAgent . $ip);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);

    $roleMap = [
        'landlordtbl' => ['redirect' => '/TAHANAN/LANDLORD/landlord-properties.php', 'db_role' => 'landlord'],
        'tenanttbl'   => ['redirect' => '/TAHANAN/TENANT/tenant.php',                'db_role' => 'tenant'],
        'admintbl'    => ['redirect' => '/TAHANAN/ADMIN/dashboard.php',              'db_role' => 'admin']
    ];

    $found = false;
    foreach ($roleMap as $table => $map) {
        $columns       = "ID, password, firstName, lastName";
        $checkUsername = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'username'");
        if ($checkUsername && $checkUsername->num_rows > 0) {
            $columns .= ", username";
        }

        $stmt = $conn->prepare("SELECT $columns FROM `$table` WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $found = true;

            if (password_verify($password, $row['password'])) {
                $userId     = $row['ID'];
                $dbRole     = $map['db_role'];
                $redirect   = $map['redirect'];
                $deviceHash = getDeviceHash();
                $fullName   = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
                $username   = isset($row['username']) && !empty($row['username'])
                                ? $row['username']
                                : ($row['firstName'] ?? 'User');

                // Admin: skip OTP
                if ($dbRole === 'admin') {
                    $_SESSION['user_id']   = $userId;
                    $_SESSION['username']  = $username;
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['user_type'] = $dbRole;
                    $_SESSION['admin_id']  = $userId;
                    header("Location: $redirect");
                    exit();
                }

                // Check trusted device
                $stmtTrusted = $conn->prepare(
                    "SELECT 1 FROM trusted_devices WHERE user_id=? AND device_hash=? AND role=?"
                );
                $stmtTrusted->bind_param("iss", $userId, $deviceHash, $dbRole);
                $stmtTrusted->execute();
                $resTrusted = $stmtTrusted->get_result();

                if ($resTrusted && $resTrusted->num_rows > 0) {
                    // Trusted device → skip OTP
                    $_SESSION['user_id']   = $userId;
                    $_SESSION['username']  = $username;
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['user_type'] = $dbRole;
                    if ($dbRole === 'tenant')   $_SESSION['tenant_id']   = $userId;
                    if ($dbRole === 'landlord') $_SESSION['landlord_id'] = $userId;
                    header("Location: $redirect");
                    exit();
                }

                // New device → send OTP
                $otp = rand(100000, 999999);
                $_SESSION['device_otp']      = $otp;
                $_SESSION['otp_user_id']     = $userId;
                $_SESSION['otp_device_hash'] = $deviceHash;
                $_SESSION['otp_role']        = $dbRole;
                $_SESSION['otp_expiry']      = time() + 600;
                $_SESSION['otp_name']        = $fullName;
                $_SESSION['otp_username']    = $username;
                $_SESSION['otp_email']       = $email;
                $_SESSION['otp_redirect']    = $redirect;

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $SMTP_USERNAME;
                    $mail->Password   = $SMTP_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->setFrom($SMTP_USERNAME, 'MapAware Home');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'MapAware Home Login OTP';
                    $mail->Body    = "<h3>MapAware Home</h3><p>Your OTP code is: <b>$otp</b>. Expires in 10 minutes.</p>";
                    $mail->send();
                    header("Location: otp.php");
                    exit();
                } catch (Exception $e) {
                    $errorMsg = "OTP send failed: " . $mail->ErrorInfo;
                }

            } else {
                $errorMsg = "Wrong password.";
            }
            break;
        }
    }

    if (!$found) {
        $errorMsg = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | MapAware Home</title>

  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <style>
    .field {
      position: relative;
      margin-bottom: 0.85rem;
    }
    .field input {
      width: 100%;
      padding: 13px 46px;
      border: 1.5px solid #ddd0d5;
      border-radius: 10px;
      background: #faf8f9;
      font-size: 14px;
      color: #1a0a10;
      font-family: inherit;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
      box-sizing: border-box;
    }
    .field input::placeholder { color: #b09aa4; }
    .field input:focus {
      border-color: #8D0B41;
      box-shadow: 0 0 0 3px rgba(141,11,65,0.12);
      background: #fff;
    }
    .field .ic-left {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      color: #8D0B41;
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
    }
    .field .ic-left svg {
      width: 16px;
      height: 16px;
      display: block;
    }
    .field .ic-right {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      color: #8D0B41;
      transition: opacity 0.2s;
    }
    .field .ic-right:hover { opacity: 0.55; }
    .field .ic-right svg {
      width: 16px;
      height: 16px;
      display: block;
    }
  </style>
</head>

<body>

<div class="main-wrapper">

  <!-- LEFT HERO -->
  <div class="hero-section">
    <div class="map-box">
      <img src="maps.jpg" alt="Map">
    </div>
    <div class="hero-text">
      <h1>Your <span>Safe</span> Zone<br>Starts Here</h1>
    </div>
  </div>

  <!-- RIGHT AUTH -->
  <div class="auth-section">
    <div class="auth-card">

      <div class="brand-identity">
        <img src="../img/new_logo.png" alt="MapAware Home Logo">
      </div>

      <form method="POST">

        <h2>Log in MapAware Home</h2>

        <!-- EMAIL -->
        <div class="field">
          <span class="ic-left">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </span>
          <input type="email" name="email" placeholder="Email address" required
                 value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>

        <!-- PASSWORD -->
        <div class="field">
          <span class="ic-left">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input type="password" name="password" id="pw-login" placeholder="Password" required>
          <button type="button" class="ic-right" onclick="togglePw()">
            <svg id="eye-icon-login" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>

        <!-- REMEMBER / FORGOT -->
        <div class="remember-forgot">
          <label>
            <input type="checkbox"> Remember me
          </label>
          <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-primary">Login</button>

        <div class="divider"><span>or</span></div>

        <div class="socials">
          <a href="google-login.php?mode=login&role=tenant" class="btn-google">
            <i class="fa-brands fa-google"></i> Log in with Google
          </a>
          <p class="redirect">
            Create an Account <a href="signup.php" class="signup">Sign up now</a>
          </p>
        </div>

      </form>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  function togglePw() {
    const inp  = document.getElementById('pw-login');
    const icon = document.getElementById('eye-icon-login');
    const hide = inp.type === 'password';
    inp.type = hide ? 'text' : 'password';

    if (hide) {
      icon.innerHTML = `
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
        <line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
      icon.innerHTML = `
        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
        <circle cx="12" cy="12" r="3"/>`;
    }
  }
</script>

<?php if (!empty($errorMsg)): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Login Failed',
    text: '<?= addslashes($errorMsg) ?>',
    confirmButtonColor: '#8D0B41',
    confirmButtonText: 'Try Again'
  });
</script>
<?php endif; ?>

</body>
</html>