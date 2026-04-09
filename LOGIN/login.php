<?php
session_start();
include '../includes/db.php';
require '../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// FIX 1 ▸ Load SMTP credentials from .env — never hardcode them here.
//          Create a file called `.env` in your project ROOT (one level above
//          public_html / TAHANAN) with these two lines:
//
//              SMTP_USERNAME=your_email@gmail.com
//              SMTP_PASSWORD=your_app_password
//
//          Then add  `.env`  to your  `.gitignore`  so it is never committed.
//          The loader below works without Composer; if you already use
//          vlucas/phpdotenv via Composer, replace this block with:
//              $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
//              $dotenv->load();
// ─────────────────────────────────────────────────────────────────────────────
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;          // skip comments
        [$key, $val] = array_map('trim', explode('=', $line, 2)); // split on first =
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}
loadEnv(__DIR__ . '/../../.env');          // adjust path to your .env location

$SMTP_USERNAME = getenv('SMTP_USERNAME') ?: '';
$SMTP_PASSWORD = getenv('SMTP_PASSWORD') ?: '';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─────────────────────────────────────────────────────────────────────────────
// FIX 3 ▸ Rate-limiting constants
// ─────────────────────────────────────────────────────────────────────────────
define('MAX_ATTEMPTS',   5);    // max failed logins before lockout
define('LOCKOUT_SECS',  900);   // 15-minute lockout window

$errorMsg = "";

// ─────────────────────────────────────────────────────────────────────────────
// FIX 4 ▸ Device identification via a long-lived cookie (not IP + UA)
// ─────────────────────────────────────────────────────────────────────────────
function getOrCreateDeviceToken(): string {
    $cookieName = 'tahanan_device';
    if (!empty($_COOKIE[$cookieName]) && ctype_alnum($_COOKIE[$cookieName])) {
        return $_COOKIE[$cookieName];
    }
    $token = bin2hex(random_bytes(32));   // 64-char random hex
    setcookie(
        $cookieName,
        $token,
        [
            'expires'  => time() + 60 * 60 * 24 * 365,   // 1 year
            'path'     => '/',
            'secure'   => true,   // HTTPS only — change to false on localhost
            'httponly' => true,   // JS cannot read it
            'samesite' => 'Strict',
        ]
    );
    return $token;
}

// ─────────────────────────────────────────────────────────────────────────────
// FIX 5 ▸ CSRF token helpers
// ─────────────────────────────────────────────────────────────────────────────
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ─────────────────────────────────────────────────────────────────────────────
// FIX 3 (helper) ▸ Check / record login attempts (stored in $_SESSION)
//   In production, consider storing attempts in the DB or Redis so they
//   survive across server restarts and work on multiple web servers.
// ─────────────────────────────────────────────────────────────────────────────
function isRateLimited(string $email): bool {
    $key = 'login_attempts_' . md5($email);
    if (!isset($_SESSION[$key])) return false;

    $data = $_SESSION[$key];
    if (time() > $data['reset_at']) {
        unset($_SESSION[$key]);
        return false;
    }
    return $data['count'] >= MAX_ATTEMPTS;
}

function recordFailedAttempt(string $email): void {
    $key = 'login_attempts_' . md5($email);
    if (!isset($_SESSION[$key]) || time() > $_SESSION[$key]['reset_at']) {
        $_SESSION[$key] = ['count' => 0, 'reset_at' => time() + LOCKOUT_SECS];
    }
    $_SESSION[$key]['count']++;
}

function clearAttempts(string $email): void {
    unset($_SESSION['login_attempts_' . md5($email)]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Main login handler
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['otp'])) {

    // FIX 5 ▸ Validate CSRF token first
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($submittedToken)) {
        $errorMsg = "Invalid request. Please refresh and try again.";
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        // FIX 3 ▸ Check rate limit before doing any DB work
        if (isRateLimited($email)) {
            $errorMsg = "Too many failed attempts. Please wait 15 minutes and try again.";
        } else {
            $roleMap = [
                'landlordtbl' => ['redirect' => '/TAHANAN/LANDLORD/landlord-properties.php', 'db_role' => 'landlord'],
                'tenanttbl'   => ['redirect' => '/TAHANAN/TENANT/tenant.php',                'db_role' => 'tenant'],
                'admintbl'    => ['redirect' => '/TAHANAN/ADMIN/dashboard.php',              'db_role' => 'admin'],
            ];

            $found       = false;
            $correctPass = false;

            foreach ($roleMap as $table => $map) {
                $columns       = "ID, password, firstName, lastName";
                $checkUsername = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'username'");
                if ($checkUsername && $checkUsername->num_rows > 0) $columns .= ", username";

                $stmt = $conn->prepare("SELECT $columns FROM `$table` WHERE email=? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    $found = true;

                    if (password_verify($password, $row['password'])) {
                        $correctPass = true;
                        clearAttempts($email);   // FIX 3 ▸ reset on success

                        $userId      = $row['ID'];
                        $dbRole      = $map['db_role'];
                        $redirect    = $map['redirect'];
                        $deviceToken = getOrCreateDeviceToken();   // FIX 4
                        $fullName    = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
                        $username    = !empty($row['username']) ? $row['username'] : ($row['firstName'] ?? 'User');

                        if ($dbRole === 'admin') {
                            $_SESSION['user_id']   = $_SESSION['admin_id'] = $userId;
                            $_SESSION['username']  = $username;
                            $_SESSION['full_name'] = $fullName;
                            $_SESSION['user_type'] = $dbRole;
                            session_regenerate_id(true);   // prevent session fixation
                            header("Location: $redirect"); exit();
                        }

                        // FIX 4 ▸ Trusted-device check uses cookie token, not IP+UA
                        $stmtT = $conn->prepare(
                            "SELECT 1 FROM trusted_devices
                              WHERE user_id=? AND device_hash=? AND role=?"
                        );
                        $stmtT->bind_param("iss", $userId, $deviceToken, $dbRole);
                        $stmtT->execute();

                        if ($stmtT->get_result()->num_rows > 0) {
                            $_SESSION['user_id']   = $userId;
                            $_SESSION['username']  = $username;
                            $_SESSION['full_name'] = $fullName;
                            $_SESSION['user_type'] = $dbRole;
                            if ($dbRole === 'tenant')   $_SESSION['tenant_id']   = $userId;
                            if ($dbRole === 'landlord') $_SESSION['landlord_id'] = $userId;
                            session_regenerate_id(true);
                            header("Location: $redirect"); exit();
                        }

                        // New device — send OTP
                        $otp = rand(100000, 999999);
                        $_SESSION['device_otp']      = $otp;
                        $_SESSION['otp_user_id']     = $userId;
                        $_SESSION['otp_device_hash'] = $deviceToken;   // FIX 4
                        $_SESSION['otp_role']        = $dbRole;
                        $_SESSION['otp_expiry']      = time() + 600;
                        $_SESSION['otp_name']        = $fullName;
                        $_SESSION['otp_username']    = $username;
                        $_SESSION['otp_email']       = $email;
                        $_SESSION['otp_redirect']    = $redirect;

                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP(); $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = $SMTP_USERNAME;   // FIX 1 ▸ from .env
                            $mail->Password = $SMTP_PASSWORD;   // FIX 1 ▸ from .env
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;
                            $mail->setFrom($SMTP_USERNAME, 'TAHANAN');
                            $mail->addAddress($email);
                            $mail->isHTML(true);
                            $mail->Subject = 'TAHANAN Login OTP';
                            $mail->Body    = "<h3>TAHANAN</h3><p>Your OTP: <b>$otp</b>. Expires in 10 minutes.</p>";
                            $mail->send();
                            header("Location: otp.php"); exit();
                        } catch (Exception $e) {
                            $errorMsg = "OTP send failed: " . $mail->ErrorInfo;
                        }
                    }
                    // Wrong password — fall through to generic error below
                    break;
                }
            }

            // FIX 2 ▸ Generic error — no distinction between "email not found"
            //         and "wrong password" to prevent user enumeration.
            if (!$correctPass) {
                recordFailedAttempt($email);   // FIX 3 ▸ count the failure
                $errorMsg = "Invalid email or password.";
            }
        }
    }
}

// Generate (or reuse) CSRF token for the form below
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MapAware Home</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
    <style>
        /* ══════════════════════════════════════
           LEFT HERO PANEL — full redesign
        ══════════════════════════════════════ */

        .hero-section {
            position: relative;
            flex: 1.2;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-right: none;
        }

        .map-box {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .map-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.80) saturate(0.85);
            animation: mapReveal 1.5s cubic-bezier(0.19,1,0.22,1) both;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                155deg,
                rgba(141,11,65,0.52) 0%,
                rgba(107, 67, 82, 0.62)   50%,
                rgba(12,2,6,0.72)    100%
            );
        }

        .hero-content {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            height: 100%;
            padding: 44px 42px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: rgba(255,255,255,0.85);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 20px;
            width: fit-content;
            animation: fadeInUp 0.7s ease-out 0.3s both;
        }
        .hero-badge i { color: #f9a8c9; font-size: 10px; }

        .hero-section > .hero-text { display: none !important; }

        .hero-headline {
            margin-bottom: 14px;
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }
        .hero-headline h1 {
            font-size: clamp(1.9rem, 3vw, 2.9rem);
            font-weight: 800;
            line-height: 1.12;
            color: #fff;
        }
        .hero-headline h1 span {
            color: #f9a8c9;
            font-style: italic;
        }

        .hero-desc {
            font-size: 13.5px;
            color: rgba(255,255,255,0.56);
            line-height: 1.75;
            max-width: 360px;
            margin-bottom: 26px;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }

        .hero-features {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-bottom: 32px;
            animation: fadeInUp 0.8s ease-out 0.85s both;
        }
        .feat-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            padding: 7px 13px;
            border-radius: 8px;
            color: rgba(255,255,255,0.80);
            font-size: 12px;
            font-weight: 500;
            transition: background 0.22s, border-color 0.22s;
        }
        .feat-pill:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.28);
        }
        .feat-pill i { color: #f9a8c9; font-size: 11px; }

        .hero-stats {
            display: flex;
            gap: 28px;
            padding-top: 22px;
            border-top: 1px solid rgba(255,255,255,0.11);
            animation: fadeInUp 0.8s ease-out 1.0s both;
        }
        .stat-item { display: flex; flex-direction: column; gap: 2px; }
        .stat-value { font-size: 22px; font-weight: 800; color: #fff; }
        .stat-label {
            font-size: 10px;
            color: rgba(255,255,255,0.44);
            text-transform: uppercase;
            letter-spacing: 0.09em;
        }

        #passwordInput { padding-right: 54px !important; }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            z-index: 4;
            color: var(--main-color);
            font-size: 1rem;
            display: flex;
            align-items: center;
            transition: opacity 0.2s;
        }
        .toggle-password:hover { opacity: 0.60; }

        .btn-google {
            width: 100%;
            padding: 13px 18px;
            border: 2px solid var(--main-color);
            border-radius: 10px;
            background: #fff;
            color: var(--main-color);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            position: relative;
            overflow: hidden;
            transition: color 0.28s ease, box-shadow 0.28s ease, transform 0.18s ease;
        }
        .btn-google::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--main-color);
            transform: scaleX(0);
            transform-origin: left center;
            transition: transform 0.30s cubic-bezier(0.4,0,0.2,1);
            z-index: 0;
        }
        .btn-google:hover::before { transform: scaleX(1); }
        .btn-google:hover {
            color: #fff;
            box-shadow: 0 6px 22px rgba(141,11,65,0.28);
            transform: translateY(-2px);
        }
        .btn-google:active { transform: translateY(0); box-shadow: none; }
        .btn-google .g-icon, .btn-google span { position: relative; z-index: 1; }
        .btn-google .g-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            transition: filter 0.28s ease;
        }
        .btn-google:hover .g-icon { filter: brightness(0) invert(1); }

        .btn-primary.login {
            transition: filter 0.22s, box-shadow 0.22s, transform 0.15s;
        }
        .btn-primary.login:hover {
            filter: brightness(1.12);
            box-shadow: 0 6px 20px rgba(141,11,65,0.30);
            transform: translateY(-2px);
        }
        .btn-primary.login:active {
            transform: translateY(0);
            box-shadow: none;
            filter: none;
        }

        .input-box input:focus {
            box-shadow: 0 0 0 3px rgba(141,11,65,0.14) !important;
        }

        .remember-forgot a, .redirect a { transition: opacity 0.2s; }
        .remember-forgot a:hover, .redirect a:hover { opacity: 0.70; text-decoration: underline; }
    </style>
</head>
<body>
<div class="main-wrapper">

    <!-- ══════════ LEFT HERO PANEL ══════════ -->
    <div class="hero-section">
        <div class="map-box">
            <img src="maps.jpg" alt="Map background">
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-orb"></div>

        <div class="hero-content">
            <div class="hero-badge">
                <i class="fa-solid fa-circle-dot fa-beat-fade"></i>
                Trusted by Many San Pedro Residents
            </div>

            <div class="hero-headline">
                <h1>Your <span>Safe</span> Zone<br>Starts Here</h1>
            </div>

            <p class="hero-desc">
                Find verified rentals in map-aware, safe communities.
                Real homes, real data, and real peace of mind — all in one place.
            </p>

            <div class="hero-features">
                <div class="feat-pill"><i class="fa-solid fa-shield-halved"></i> Verified Listings</div>
                <div class="feat-pill"><i class="fa-solid fa-map-location-dot"></i> Interactive Map</div>
                <div class="feat-pill"><i class="fa-solid fa-house-chimney-window"></i> List and Manage Properties</div>
                <div class="feat-pill"><i class="fa-solid fa-star"></i> Rated Landlords</div>
            </div>
        </div>
    </div>

    <!-- RIGHT AUTH PANEL -->
    <div class="auth-section">
        <div class="auth-card">

            <div class="brand-identity">
                <img src="../img/new_logo.png" alt="MapAware Home Logo">
            </div>

            <form action="" method="POST">

                <!-- FIX 5 ▸ CSRF hidden field -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <h2>Log in or Sign up</h2>

                <!-- Email -->
                <div class="input-box">
                    <input type="text" name="email" placeholder="Email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <i class="fa-solid fa-user" style="margin-left: 80px;"></i>
                </div>

                <!-- Password + eye toggle -->
                <div class="input-box">
                    <input type="password" name="password" id="passwordInput" placeholder="Password" required>
                    <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>

                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="remember_me" value="1"
                               <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-primary btn login">Login</button>

                <div class="divider"><span>or</span></div>

                <div class="socials">
                    <a href="google-login.php?mode=login&role=tenant" class="btn-google">
                        <svg class="g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        <span>Log in with Google</span>
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

<?php if (!empty($errorMsg)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: '<?php echo addslashes($errorMsg); ?>',
        confirmButtonColor: '#8d0b41',
        confirmButtonText: 'Try Again'
    });
</script>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo addslashes($_SESSION['success']); ?>',
        confirmButtonColor: '#8d0b41',
        timer: 3000,
        timerProgressBar: true
    });
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<script>
    // Password visibility toggle
    const toggleBtn = document.getElementById('togglePassword');
    const passInput = document.getElementById('passwordInput');
    const eyeIcon   = document.getElementById('eyeIcon');

    toggleBtn.addEventListener('click', function () {
        const hidden = passInput.type === 'password';
        passInput.type = hidden ? 'text' : 'password';
        eyeIcon.classList.toggle('fa-eye',       !hidden);
        eyeIcon.classList.toggle('fa-eye-slash',  hidden);
    });
</script>
</body>
</html>