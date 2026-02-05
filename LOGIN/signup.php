<?php
// --- PHPMailer  ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

include '../includes/db.php';
require_once 'google-config.php';
session_start();

$message = "";

// --- CLEAN URL REDIRECT ---
if (isset($_GET['clear'])) {
    header("Location: signup.php");
    exit;
}

// --- GOOGLE SIGNUP LINKS ---
$googleUrlTenant = "google-login.php?mode=signup&role=tenant";
$googleUrlLandlord = "google-login.php?mode=signup&role=landlord";

// --- NORMAL SIGNUP ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $username = trim($_POST['username']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);

    if (empty($username) || empty($email) || empty($password)) {
        $message = "❌ All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    } else {
        // check if email exists in any table
        $checkSql = "SELECT 1 FROM (
                        SELECT email FROM landlordtbl
                        UNION
                        SELECT email FROM tenanttbl
                        UNION
                        SELECT email FROM admintbl
                    ) all_users WHERE email=?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "⚠️ Email already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($role === "landlord") {
                $sql = "INSERT INTO landlordtbl (username, email, password, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
            } else {
                $sql = "INSERT INTO tenanttbl (username, email, password, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                // generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['email'] = $email;
                $_SESSION['user_type'] = $role;

                // send OTP through Email
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
                    $mail->Body = "<h3>Welcome to TAHANAN!</h3><p>Your OTP is <b>$otp</b>. It will expire in 5 minutes.</p>";

                    $mail->send();

                    $_SESSION['success'] = "✅ Account created successfully! Please log in.";
                    header("Location: login.php");
                    exit();
                } catch (Exception $e) {
                    $message = "❌ Could not send OTP. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $message = "❌ Error: Something went wrong. Please try again.";
                error_log("Signup Error: " . $stmt->error);
            }
        }
    }
}

// --- google oauth ---
if (isset($_SESSION['google_signup'])) {
    $googleData = $_SESSION['google_signup'];

    $username = $googleData['name'];
    $email = strtolower($googleData['email']);
    $role = $googleData['role'];

    // check if email already exists
    $checkSql = "SELECT 1 FROM (
                    SELECT email FROM landlordtbl
                    UNION
                    SELECT email FROM tenanttbl
                    UNION
                    SELECT email FROM admintbl
                ) all_users WHERE email=?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $message = "⚠️ Email already registered with another method.";
        unset($_SESSION['google_signup']);
    } else {
        if ($role === "landlord") {
            $sql = "INSERT INTO landlordtbl (username, email, password, status, created_at) VALUES (?, ?, '', 'pending', NOW())";
        } else {
            $sql = "INSERT INTO tenanttbl (username, email, password, status, created_at) VALUES (?, ?, '', 'pending', NOW())";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);

        if ($stmt->execute()) {
            // generate OTP for google signup as well
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = $role;

            //  send OTP here with PHPMailer again if needed

            $_SESSION['success'] = "✅ Google Signup successful! Enter the OTP sent to your email.";
            unset($_SESSION['google_signup']);
            header("Location: verify-otp.php");
            exit;
        } else {
            $message = "❌ Error: Google signup failed.";
            error_log("Google Signup Error: " . $stmt->error);
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Form</title>
    <link rel="stylesheet" href="signup.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inknut+Antiqua:wght@300..900&family=Quicksand:wght@300..700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="form-container">

        <!-- Landlord Form -->
        <div class="form-box landlord">
            <form method="POST" action="signup.php">
                <h1>Sign Up as Landlord</h1>

                <!-- Messages (Landlord) -->
                <?php if (!empty($message) && isset($_POST['role']) && $_POST['role'] === 'landlord'): ?>
                    <p style="color:red; text-align:center; font-weight:bold;"><?php echo $message; ?></p>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error']) && isset($_POST['role']) && $_POST['role'] === 'landlord'): ?>
                    <p style="color:red; text-align:center; font-weight:bold;">
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($_SESSION['success']) && isset($_POST['role']) && $_POST['role'] === 'landlord'): ?>
                    <p style="color:green; text-align:center; font-weight:bold;">
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </p>
                <?php endif; ?>

                <input type="hidden" name="role" value="landlord">
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="terms">
                   <input type="checkbox" required>
                   I agree with <a href="terms-landlord.html" target="_blank">Terms and Conditions</a>
                </div>
                <button type="submit" class="btn signup">Sign Up</button>
                <div class="socials">
                    <p>or</p><br>
                    <a href="<?php echo htmlspecialchars($googleUrlLandlord); ?>">
                        <i class="fa-brands fa-google"></i> Signup with Google
                    </a>
                </div>
            </form>
        </div>

        <!-- Tenant Form -->
        <div class="form-box tenant">
            <form method="POST" action="signup.php">
                <h1>Sign Up as Tenant</h1>

                <!-- Messages (Tenant) -->
                <?php if (!empty($message) && isset($_POST['role']) && $_POST['role'] === 'tenant'): ?>
                    <p style="color:red; text-align:center; font-weight:bold;"><?php echo $message; ?></p>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error']) && isset($_POST['role']) && $_POST['role'] === 'tenant'): ?>
                    <p style="color:red; text-align:center; font-weight:bold;">
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($_SESSION['success']) && isset($_POST['role']) && $_POST['role'] === 'tenant'): ?>
                    <p style="color:green; text-align:center; font-weight:bold;">
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </p>
                <?php endif; ?>

                <input type="hidden" name="role" value="tenant">
                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="terms">
                   <input type="checkbox" required>
                   I agree with <a href="terms-tenant.html" target="_blank">Terms and Conditions</a>
                </div>
                <button type="submit" class="btn signup">Sign Up</button>
                <div class="socials">
                    <p>or</p><br>
                    <a href="<?php echo htmlspecialchars($googleUrlTenant); ?>">
                        <i class="fa-brands fa-google"></i> Signup with Google
                    </a>
                </div>
            </form>
        </div>


        <!-- Toggle Box -->
        <div class="toggle-box">
            <div class="toggle-panel left">
                <h1>Hello, Welcome</h1>
                <h1>to TAHANAN</h1>
                <button class="btn tenant-btn">Sign Up as Tenant</button><br>
                <p>Already have Account?</p>
                <a href="login.php"><button class="btn">Login</button></a>
            </div>
            <div class="toggle-panel right">
                <h1>Hello, Welcome</h1>
                <h1>to TAHANAN</h1>
                <button class="btn landlord-btn">Sign Up as Landlord</button>
                <p>Already have Account?</p>
                <a href="login.php"><button class="btn">Login</button></a>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>

</html>