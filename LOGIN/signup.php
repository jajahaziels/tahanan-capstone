<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

include '../includes/db.php';
require_once 'google-config.php';
session_start();

$message     = "";
$messageType = "error";

if (isset($_GET['clear'])) {
  header("Location: signup.php"); exit;
}

$googleUrlTenant   = "google-login.php?mode=signup&role=tenant";
$googleUrlLandlord = "google-login.php?mode=signup&role=landlord";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $role     = $_POST['role'];
  $username = trim($_POST['username']);
  $email    = strtolower(trim($_POST['email']));
  $password = trim($_POST['password']);

  if (empty($username) || empty($email) || empty($password)) {
    $message = "All fields are required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Invalid email format.";
  } elseif (strlen($password) < 8) {
    $message = "Password must be at least 8 characters.";
  } else {
    $checkSql = "SELECT 1 FROM (
      SELECT email FROM landlordtbl UNION SELECT email FROM tenanttbl UNION SELECT email FROM admintbl
    ) all_users WHERE email=?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
      $message = "This email is already registered.";
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $sql = $role === "landlord"
        ? "INSERT INTO landlordtbl (username, email, password, status, created_at) VALUES (?, ?, ?, 'pending', NOW())"
        : "INSERT INTO tenanttbl  (username, email, password, status, created_at) VALUES (?, ?, ?, 'pending', NOW())";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $username, $email, $hashedPassword);

      if ($stmt->execute()) {
        $otp = rand(100000, 999999);
        $_SESSION['otp']       = $otp;
        $_SESSION['email']     = $email;
        $_SESSION['user_type'] = $role;

        $mail = new PHPMailer(true);
        try {
          $mail->isSMTP(); $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth = true;
          $mail->Username = 'jajasison07@gmail.com';
          $mail->Password = 'aebfllyitmpjvzqz';
          $mail->SMTPSecure = 'tls'; $mail->Port = 587;
          $mail->setFrom('jajasison07@gmail.com', 'MapAware Home');
          $mail->addAddress($email); $mail->isHTML(true);
          $mail->Subject = 'Your MapAware Home Verification Code';
          $mail->Body    = "
            <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;background:#fafafa;border-radius:12px;'>
              <h2 style='color:#8D0B41;margin-bottom:8px;'>Welcome to MapAware Home!</h2>
              <p style='color:#647887;margin-bottom:24px;'>Use the code below to verify your " . ucfirst($role) . " account.</p>
              <div style='background:#fff;border:1.5px solid #f4c0d1;border-radius:10px;padding:24px;text-align:center;margin-bottom:24px;'>
                <span style='font-size:36px;font-weight:800;letter-spacing:10px;color:#8D0B41;'>$otp</span>
              </div>
              <p style='color:#aaa;font-size:13px;'>This code expires in 5 minutes. Do not share it with anyone.</p>
            </div>
          ";
          $mail->send();
          $message     = "Your " . ucfirst($role) . " account has been created! Check your email for the OTP.";
          $messageType = "success";
        } catch (Exception $e) {
          $message = "Account created but OTP could not be sent. " . $mail->ErrorInfo;
        }
      } else {
        $message = "Something went wrong. Please try again.";
      }
    }
  }
}

if (isset($_SESSION['google_signup'])) {
  $googleData = $_SESSION['google_signup'];
  $username   = $googleData['name'];
  $email      = strtolower($googleData['email']);
  $role       = $googleData['role'];

  $checkSql = "SELECT 1 FROM (SELECT email FROM landlordtbl UNION SELECT email FROM tenanttbl UNION SELECT email FROM admintbl) all_users WHERE email=?";
  $stmt = $conn->prepare($checkSql);
  $stmt->bind_param("s", $email);
  $stmt->execute();

  if ($stmt->get_result()->num_rows > 0) {
    $message = "This email is already registered with another method.";
    unset($_SESSION['google_signup']);
  } else {
    $sql = $role === "landlord"
      ? "INSERT INTO landlordtbl (username, email, password, status, created_at) VALUES (?, ?, '', 'pending', NOW())"
      : "INSERT INTO tenanttbl  (username, email, password, status, created_at) VALUES (?, ?, '', 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    if ($stmt->execute()) {
      $otp = rand(100000, 999999);
      $_SESSION['otp'] = $otp; $_SESSION['email'] = $email; $_SESSION['user_type'] = $role;
      $_SESSION['success'] = "Google Signup successful! Enter the OTP sent to your email.";
      unset($_SESSION['google_signup']);
      header("Location: verify-otp.php"); exit;
    } else {
      $message = "Google signup failed. Please try again.";
    }
  }
}

$activeRole = $_POST['role'] ?? 'landlord';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | MapAware Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: #f5f0f2;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    /* ── Card ── */
    .auth-card {
      background: #ffffff;
      border: 1px solid #e8dde2;
      border-radius: 18px;
      padding: 2.2rem 2rem 2rem;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 4px 32px rgba(141,11,65,0.07);
    }

    /* ── Logo ── */
    .logo-wrap {
      text-align: center;
      margin-bottom: 1.6rem;
    }
    .logo-wrap img {
      width: 80px;
      height: auto;
      margin-bottom: 0.6rem;
      display: block;
      margin-left: auto;
      margin-right: auto;
    }
    .logo-wrap h2 {
      font-size: 17px;
      font-weight: 600;
      color: #1a0a10;
      margin-bottom: 3px;
      letter-spacing: -0.01em;
    }
    .logo-wrap p {
      font-size: 13px;
      color: #8a7480;
    }

    /* ── Role tabs ── */
    .role-tabs {
      display: flex;
      border: 1.5px solid #8D0B41;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 1.4rem;
    }
    .role-tab {
      flex: 1;
      padding: 10px 8px;
      font-size: 13px;
      font-weight: 600;
      color: #8D0B41;
      background: transparent;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      font-family: inherit;
      transition: background 0.18s, color 0.18s;
    }
    .role-tab + .role-tab { border-left: 1.5px solid #8D0B41; }
    .role-tab.active { background: #8D0B41; color: #fff; }

    /* Tab icons — inline SVG, fixed size, no inheritance issues */
    .role-tab .tab-icon {
      width: 14px;
      height: 14px;
      flex-shrink: 0;
      display: block;
    }

    /* ── Form panels ── */
    .form-panel { display: none; }
    .form-panel.active { display: block; }

    /* ── Input field wrapper ── */
    .field {
      position: relative;
      margin-bottom: 0.85rem;
    }

    /* All inputs get symmetric padding for left + right icons */
    .field input {
      width: 100%;
      padding: 13px 46px;          /* left icon + right icon space */
      border: 1.5px solid #ddd0d5;
      border-radius: 10px;
      background: #faf8f9;
      font-size: 14px;
      font-weight: 400;
      color: #1a0a10;
      font-family: inherit;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .field input::placeholder { color: #b09aa4; }
    .field input:focus {
      border-color: #8D0B41;
      box-shadow: 0 0 0 3px rgba(141,11,65,0.12);
      background: #fff;
    }

    /* Left decorative icon — perfectly centered vertically */
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

    /* Right eye-toggle button — same vertical rule as left icon */
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

    /* ── Password strength ── */
    .strength-wrap { display: none; margin: -2px 0 12px; }
    .strength-wrap.visible { display: block; }

    .s-bars { display: flex; gap: 4px; margin-bottom: 4px; }
    .s-bar {
      flex: 1; height: 3px; border-radius: 99px;
      background: #e0d5d9;
      transition: background 0.3s;
    }
    .s-bar.weak   { background: #e53935; }
    .s-bar.fair   { background: #fb8c00; }
    .s-bar.good   { background: #fdd835; }
    .s-bar.strong { background: #43a047; }

    .s-label {
      font-size: 11px;
      font-weight: 600;
      color: #b09aa4;
      text-align: right;
      margin-bottom: 5px;
      transition: color 0.3s;
    }
    .s-rules { display: flex; flex-direction: column; gap: 3px; }
    .s-rule {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      color: #b09aa4;
      transition: color 0.2s;
    }
    .s-rule svg {
      width: 11px;
      height: 11px;
      flex-shrink: 0;
      color: #d0c0c6;
      transition: color 0.2s;
    }
    .s-rule.pass { color: #43a047; }
    .s-rule.pass svg { color: #43a047; }

    /* ── Terms ── */
    .terms-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 6px 0 14px;
      font-size: 12.5px;
      color: #7a6570;
    }
    .terms-row input[type=checkbox] {
      width: 15px; height: 15px;
      flex-shrink: 0;
      cursor: pointer;
      accent-color: #8D0B41;
    }
    .terms-row a { color: #8D0B41; font-weight: 600; text-decoration: none; }
    .terms-row a:hover { text-decoration: underline; }

    /* ── Submit button ── */
    .btn-signup {
      width: 100%;
      padding: 13px;
      background: #8D0B41;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      letter-spacing: 0.01em;
      transition: filter 0.2s, transform 0.15s, box-shadow 0.2s;
    }
    .btn-signup:hover {
      filter: brightness(1.1);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(141,11,65,0.25);
    }
    .btn-signup:active { transform: translateY(0); filter: none; box-shadow: none; }

    /* ── Divider ── */
    .divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 1rem 0;
      font-size: 12px;
      color: #b09aa4;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #ecdde4;
    }

    /* ── Google button ── */
    .btn-google {
      width: 100%;
      padding: 11px 16px;
      border: 1.5px solid #ddd0d5;
      border-radius: 10px;
      background: #fff;
      color: #1a0a10;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    }
    .btn-google:hover {
      border-color: #8D0B41;
      background: #fdf4f7;
      box-shadow: 0 2px 10px rgba(141,11,65,0.10);
    }
    .btn-google .g-icon {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      display: block;
    }

    /* ── Redirect link ── */
    .redirect {
      text-align: center;
      margin-top: 1.1rem;
      font-size: 13px;
      color: #7a6570;
    }
    .redirect a { color: #8D0B41; font-weight: 600; text-decoration: none; }
    .redirect a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<div class="auth-card">

  <!-- Logo -->
  <div class="logo-wrap">
    <img src="../img/new_logo.png" alt="MapAware Home Logo">
    <h2>Create your account</h2>
    <p>MapAware Home — find your safe space</p>
  </div>

  <!-- Role tabs -->
  <div class="role-tabs">
    <button class="role-tab <?= $activeRole === 'landlord' ? 'active' : '' ?>" id="tab-landlord" onclick="switchTab('landlord')">
      <!-- House icon -->
      <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Landlord
    </button>
    <button class="role-tab <?= $activeRole === 'tenant' ? 'active' : '' ?>" id="tab-tenant" onclick="switchTab('tenant')">
      <!-- Person icon -->
      <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Tenant
    </button>
  </div>

  <!-- ── LANDLORD PANEL ── -->
  <div class="form-panel <?= $activeRole === 'landlord' ? 'active' : '' ?>" id="panel-landlord">
    <form method="POST" action="signup.php">
      <input type="hidden" name="role" value="landlord">

      <!-- Username -->
      <div class="field">
        <span class="ic-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </span>
        <input type="text" name="username" placeholder="Username" required
               value="<?= (($_POST['role'] ?? '') === 'landlord' && isset($_POST['username'])) ? htmlspecialchars($_POST['username']) : '' ?>">
      </div>

      <!-- Email -->
      <div class="field">
        <span class="ic-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
        </span>
        <input type="email" name="email" placeholder="Email address" required
               value="<?= (($_POST['role'] ?? '') === 'landlord' && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>

      <!-- Password -->
      <div class="field">
        <span class="ic-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </span>
        <input type="password" name="password" id="pw-landlord"
               placeholder="Password (min. 8 chars)" required
               oninput="checkStrength('landlord')"
               onfocus="document.getElementById('str-landlord').classList.add('visible')"
               onblur="if(!this.value) document.getElementById('str-landlord').classList.remove('visible')">
        <button type="button" class="ic-right" id="eye-btn-landlord" onclick="togglePw('pw-landlord','landlord')">
          <!-- Eye open -->
          <svg id="eye-icon-landlord" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>

      <!-- Strength meter -->
      <div class="strength-wrap" id="str-landlord">
        <div class="s-bars">
          <div class="s-bar" id="lb1"></div>
          <div class="s-bar" id="lb2"></div>
          <div class="s-bar" id="lb3"></div>
          <div class="s-bar" id="lb4"></div>
        </div>
        <div class="s-label" id="llbl">Start typing…</div>
        <div class="s-rules">
          <div class="s-rule" id="lr1">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            At least 8 characters
          </div>
          <div class="s-rule" id="lr2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Uppercase letter (A–Z)
          </div>
          <div class="s-rule" id="lr3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Lowercase letter (a–z)
          </div>
          <div class="s-rule" id="lr4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Number (0–9)
          </div>
          <div class="s-rule" id="lr5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Special character (!@#$…)
          </div>
        </div>
      </div>

      <!-- Terms -->
      <div class="terms-row">
        <input type="checkbox" id="terms-landlord" required>
        <label for="terms-landlord">I agree to the <a href="terms-landlord.html" target="_blank">Terms and Conditions</a></label>
      </div>

      <button type="submit" class="btn-signup">Create Landlord Account</button>

      <div class="divider">or</div>

      <a href="<?= htmlspecialchars($googleUrlLandlord) ?>" class="btn-google">
        <svg class="g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        Sign up with Google
      </a>

      <p class="redirect">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>

  <!-- ── TENANT PANEL ── -->
  <div class="form-panel <?= $activeRole === 'tenant' ? 'active' : '' ?>" id="panel-tenant">
    <form method="POST" action="signup.php">
      <input type="hidden" name="role" value="tenant">

      <!-- Username -->
      <div class="field">
        <span class="ic-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </span>
        <input type="text" name="username" placeholder="Username" required
               value="<?= (($_POST['role'] ?? '') === 'tenant' && isset($_POST['username'])) ? htmlspecialchars($_POST['username']) : '' ?>">
      </div>

      <!-- Email -->
      <div class="field">
        <span class="ic-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
        </span>
        <input type="email" name="email" placeholder="Email address" required
               value="<?= (($_POST['role'] ?? '') === 'tenant' && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>

      <!-- Password -->
      <div class="field">
        <span class="ic-left">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </span>
        <input type="password" name="password" id="pw-tenant"
               placeholder="Password (min. 8 chars)" required
               oninput="checkStrength('tenant')"
               onfocus="document.getElementById('str-tenant').classList.add('visible')"
               onblur="if(!this.value) document.getElementById('str-tenant').classList.remove('visible')">
        <button type="button" class="ic-right" id="eye-btn-tenant" onclick="togglePw('pw-tenant','tenant')">
          <!-- Eye open -->
          <svg id="eye-icon-tenant" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
      </div>

      <!-- Strength meter -->
      <div class="strength-wrap" id="str-tenant">
        <div class="s-bars">
          <div class="s-bar" id="tb1"></div>
          <div class="s-bar" id="tb2"></div>
          <div class="s-bar" id="tb3"></div>
          <div class="s-bar" id="tb4"></div>
        </div>
        <div class="s-label" id="tlbl">Start typing…</div>
        <div class="s-rules">
          <div class="s-rule" id="tr1">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            At least 8 characters
          </div>
          <div class="s-rule" id="tr2">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Uppercase letter (A–Z)
          </div>
          <div class="s-rule" id="tr3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Lowercase letter (a–z)
          </div>
          <div class="s-rule" id="tr4">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Number (0–9)
          </div>
          <div class="s-rule" id="tr5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Special character (!@#$…)
          </div>
        </div>
      </div>

      <!-- Terms -->
      <div class="terms-row">
        <input type="checkbox" id="terms-tenant" required>
        <label for="terms-tenant">I agree to the <a href="terms-tenant.html" target="_blank">Terms and Conditions</a></label>
      </div>

      <button type="submit" class="btn-signup">Create Tenant Account</button>

      <div class="divider">or</div>

      <a href="<?= htmlspecialchars($googleUrlTenant) ?>" class="btn-google">
        <svg class="g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        Sign up with Google
      </a>

      <p class="redirect">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>

</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($message) && $messageType === 'error'): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Sign Up Failed',
    text: '<?= addslashes($message) ?>',
    confirmButtonColor: '#8D0B41',
    confirmButtonText: 'Try Again'
  });
</script>
<?php endif; ?>

<?php if (!empty($message) && $messageType === 'success'): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Account Created! Processed to Login',
    text: '<?= addslashes($message) ?>',
    confirmButtonColor: '#8D0B41',
    confirmButtonText: 'Okay'
  }).then(() => window.location.href = 'login.php');
</script>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '<?= addslashes($_SESSION['success']) ?>',
    confirmButtonColor: '#8D0B41',
    timer: 3000,
    timerProgressBar: true
  });
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<script>
  /* ── Tab switching ── */
  function switchTab(role) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-'   + role).classList.add('active');
    document.getElementById('panel-' + role).classList.add('active');
  }

  /* ── Eye toggle ── */
  function togglePw(inputId, role) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById('eye-icon-' + role);
    const hide = inp.type === 'password';
    inp.type = hide ? 'text' : 'password';

    /* Swap between open-eye and crossed-eye SVG paths */
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

  /* ── Password strength ── */
  function checkStrength(role) {
    const p   = role === 'landlord' ? 'l' : 't';
    const val = document.getElementById('pw-' + role).value;

    const rules = {
      length:  val.length >= 8,
      upper:   /[A-Z]/.test(val),
      lower:   /[a-z]/.test(val),
      number:  /[0-9]/.test(val),
      special: /[^A-Za-z0-9]/.test(val),
    };

    ['length','upper','lower','number','special'].forEach((k, i) => {
      document.getElementById(p + 'r' + (i + 1)).classList.toggle('pass', rules[k]);
    });

    const score = Object.values(rules).filter(Boolean).length;
    const bars  = [1,2,3,4].map(n => document.getElementById(p + 'b' + n));
    const lbl   = document.getElementById(p + 'lbl');

    bars.forEach(b => { b.className = 's-bar'; });

    if (!val) {
      lbl.textContent = 'Start typing…';
      lbl.style.color = '';
      return;
    }

    const levels = [
      { max:1, cls:['weak'],                              text:'Very weak',   color:'#e53935' },
      { max:2, cls:['weak','fair'],                       text:'Weak',        color:'#fb8c00' },
      { max:3, cls:['fair','fair','good'],                text:'Fair',        color:'#f9a825' },
      { max:4, cls:['good','good','good','strong'],       text:'Strong',      color:'#43a047' },
      { max:5, cls:['strong','strong','strong','strong'], text:'Very strong', color:'#2e7d32' },
    ];

    const lvl = levels.find(l => score <= l.max) || levels[4];
    lvl.cls.forEach((cls, i) => { if (bars[i]) bars[i].classList.add(cls); });
    lbl.textContent = lvl.text;
    lbl.style.color = lvl.color;
  }
</script>
</body>
</html>