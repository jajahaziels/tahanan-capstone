<?php
session_start();
include '../includes/db.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP Config
$SMTP_USERNAME = 'jajasison07@gmail.com';
$SMTP_PASSWORD = 'aebfllyitmpjvzqz';

$errorMsg = "";
$showOtpBox = false;

// Generate device hash
function getDeviceHash()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return hash('sha256', $userAgent . $ip);
}

// ----------------- NORMAL LOGIN -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['otp'])) {
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);

    // Add admin table here
    $roleMap = [
        'landlordtbl' => ['redirect' => '/TAHANAN/LANDLORD/landlord-properties.php', 'db_role' => 'landlord'],
        'tenanttbl' => ['redirect' => '/TAHANAN/TENANT/tenant.php', 'db_role' => 'tenant'],
        'admintbl' => ['redirect' => '/TAHANAN/ADMIN/homepage.php', 'db_role' => 'admin']
    ];

    $found = false;
    foreach ($roleMap as $table => $map) {
        $stmt = $conn->prepare("SELECT ID, password, firstName, lastName FROM `$table` WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $found = true;

            if (password_verify($password, $row['password'])) {
                $userId = $row['ID'];
                $dbRole = $map['db_role'];
                $redirect = $map['redirect'];
                $deviceHash = getDeviceHash();

                // --- NO OTP FOR ADMIN ---
                if ($dbRole === 'admin') {
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $row['firstName'] . ' ' . $row['lastName'];
                    $_SESSION['user_type'] = $dbRole;

                    header("Location: $redirect");
                    exit();
                }

                // --- NORMAL OTP FLOW FOR LANDLORD / TENANT ---
                $stmtTrusted = $conn->prepare("SELECT 1 FROM trusted_devices WHERE user_id=? AND device_hash=? AND role=?");
                $stmtTrusted->bind_param("iss", $userId, $deviceHash, $dbRole);
                $stmtTrusted->execute();
                $resTrusted = $stmtTrusted->get_result();

                if ($resTrusted && $resTrusted->num_rows > 0) {
                    // Trusted device → login immediately
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $row['firstName'] . ' ' . $row['lastName'];
                    $_SESSION['user_type'] = $dbRole;

                    // Optional: set tenant_id / landlord_id for message pages
                    if ($dbRole === 'tenant') {
                        $_SESSION['tenant_id'] = $userId;
                    } elseif ($dbRole === 'landlord') {
                        $_SESSION['landlord_id'] = $userId;
                    }

                    header("Location: $redirect");
                    exit();
                }

                // Device not trusted need to send OTP
                $otp = rand(100000, 999999);
                $_SESSION['device_otp'] = $otp;
                $_SESSION['otp_user_id'] = $userId;
                $_SESSION['otp_device_hash'] = $deviceHash;
                $_SESSION['otp_role'] = $dbRole;
                $_SESSION['otp_expiry'] = time() + 600; // 10 minutes
                $_SESSION['otp_name'] = $row['firstName'] . ' ' . $row['lastName'];
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_redirect'] = $redirect; 
                
                // Send OTP via email
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
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'TAHANAN Login OTP';
                    $mail->Body = "<h3>TAHANAN</h3><p>OTP: <b>$otp</b>. Expires in 10 minutes.</p>";
                    $mail->send();

                    // Redirect to OTP page
                    header("Location: otp.php");
                    exit();

                } catch (Exception $e) {
                    $errorMsg = "❌ OTP send failed: " . $mail->ErrorInfo;
                }

            } else {
                $errorMsg = "❌ Wrong password.";
            }
            break;
        }
    }

    if (!$found) {
        $errorMsg = "❌ No account found with this email.";
    }
}
?>




<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <link rel="stylesheet" href="signup.css"> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <title>Login Form</title> 

</head> 

<body> 
    <div class="form-container login-container"> 
        <div class="form-box login-form"> 
            <form method="POST" action=""> 
                <h1>Login Form</h1> 
                
                <!-- Debug information  -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    
                    User Agent: <?php echo htmlspecialchars(substr($_SERVER['HTTP_USER_AGENT'], 0, 50)); ?>...<br>
                    IP: <?php echo $_SERVER['REMOTE_ADDR']; ?><br>
                    HTTPS: <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Yes' : 'No'; ?>
                </div>
                <?php endif; ?>
                
                <!-- Show error/success messages neatly --> 
                <?php if (!empty($errorMsg)): ?> 
                    <p class="message error"><?php echo $errorMsg; ?></p> 
                <?php elseif (!empty($_SESSION['success'])): ?> 
                    <p class="message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p> 
                <?php endif; ?> 
                
                <div class="input-box"> 
                    <input type="text" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"> 
                    <i class="fa-solid fa-user"></i> 
                </div> 
                <div class="input-box"> 
                    <input type="password" name="password" placeholder="Password" required> 
                    <i class="fa-solid fa-key"></i> 
                </div> 
                <div class="remember-forgot"> 
                    <label><input type="checkbox" name="remember_me" value="1" <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>> Remember me</label> 
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>

 
                </div><br> 
                <button type="submit" class="btn login">Login</button> 
                
                <!-- OTP box appears only when condition is satisfy --> 
                <?php if ($showOtpBox): ?> 
                    <div class="otp-box"> 
                        <h3>Enter OTP</h3> 
                        <input type="text" name="otp" placeholder="Enter OTP" required> 
                        <br> 
                        <button type="submit">Verify OTP</button> 
                    </div> 
                <?php endif; ?> 
                
                <div class="socials"><br> 
                    <p>or</p><br> 
                    <a href="google-login.php?mode=login&role=tenant"> 
                        <i class="fa-brands fa-google"></i> Login with Google 
                    </a> 
                </div><br> 
                <div class="signup-link"> 
                    Create an Account <a href="signup.php" class="signup">Signup now</a> 
                </div> 
            </form> 
        </div> 
    </div> 
</body> 
</html>
