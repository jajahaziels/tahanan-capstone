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
          $mail->setFrom('jajasison07@gmail.com', 'TAHANAN');
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
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
  <style>
    /* Allow scrolling on signup (more content than login) */
    body, html { overflow: auto; }

    /* ══ LEFT HERO — identical to login ══ */
    .hero-section {
      position: relative; flex: 1.2;
      display: flex; flex-direction: column;
      overflow: hidden; border-right: none;
    }
    .map-box { position: absolute; inset: 0; z-index: 0; }
    .map-box img {
      width: 100%; height: 100%; object-fit: cover;
      filter: brightness(0.80) saturate(0.85);
      animation: mapReveal 1.5s cubic-bezier(0.19,1,0.22,1) both;
    }
    .hero-overlay {
      position: absolute; inset: 0; z-index: 1;
      background: linear-gradient(155deg,
        rgba(141,11,65,0.52) 0%, rgba(55,3,22,0.62) 50%, rgba(12,2,6,0.72) 100%);
    }
    .hero-orb {
      position: absolute; top: -80px; right: -80px;
      width: 340px; height: 340px; border-radius: 50%;
      background: radial-gradient(circle, rgba(141,11,65,0.40) 0%, transparent 65%);
      z-index: 2; pointer-events: none;
    }
    .hero-content {
      position: relative; z-index: 3;
      display: flex; flex-direction: column; justify-content: flex-end;
      height: 100%; padding: 44px 42px;
    }
    .hero-section > .hero-text { display: none !important; }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18);
      backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
      color: rgba(255,255,255,0.85); font-size: 11px; font-weight: 600;
      letter-spacing: 0.09em; text-transform: uppercase;
      padding: 6px 14px; border-radius: 999px; margin-bottom: 20px; width: fit-content;
      animation: fadeInUp 0.7s ease-out 0.3s both;
    }
    .hero-badge i { color: #f9a8c9; font-size: 10px; }

    .hero-headline { margin-bottom: 14px; animation: fadeInUp 0.8s ease-out 0.5s both; }
    .hero-headline h1 { font-size: clamp(1.9rem,3vw,2.9rem); font-weight: 800; line-height: 1.12; color: #fff; }
    .hero-headline h1 span { color: #f9a8c9; font-style: italic; }

    .hero-desc {
      font-size: 13.5px; color: rgba(255,255,255,0.56); line-height: 1.75;
      max-width: 360px; margin-bottom: 26px;
      animation: fadeInUp 0.8s ease-out 0.7s both;
    }
    .hero-features {
      display: flex; flex-wrap: wrap; gap: 9px; margin-bottom: 32px;
      animation: fadeInUp 0.8s ease-out 0.85s both;
    }
    .feat-pill {
      display: inline-flex; align-items: center; gap: 7px;
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.14);
      backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
      padding: 7px 13px; border-radius: 8px;
      color: rgba(255,255,255,0.80); font-size: 12px; font-weight: 500;
      transition: background 0.22s, border-color 0.22s;
    }
    .feat-pill:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.28); }
    .feat-pill i { color: #f9a8c9; font-size: 11px; }

    .hero-stats {
      display: flex; gap: 28px; padding-top: 22px;
      border-top: 1px solid rgba(255,255,255,0.11);
      animation: fadeInUp 0.8s ease-out 1.0s both;
    }
    .stat-item { display: flex; flex-direction: column; gap: 2px; }
    .stat-value { font-size: 22px; font-weight: 800; color: #fff; }
    .stat-label { font-size: 10px; color: rgba(255,255,255,0.44); text-transform: uppercase; letter-spacing: 0.09em; }

    /* ══ RIGHT PANEL ══ */
    .auth-section {
      flex: 1; display: flex; justify-content: center; align-items: flex-start;
      padding: 2rem; background-color: #ffffff; overflow-y: auto;
    }
    .auth-card { width: 100%; max-width: 380px; text-align: center; }

    /* Logo — same as login */
    .brand-identity img {
      width: 90px; margin-bottom: 0.75rem;
      animation: logoEntrance 0.8s ease-out 0.6s both;
    }

    /* Role switcher — pill toggle */
    .role-tabs {
      display: flex; border: 2px solid var(--main-color);
      border-radius: 10px; overflow: hidden; margin-bottom: 1.5rem;
      animation: formEntrance 0.6s ease-out 0.7s both;
    }
    .role-tab {
      flex: 1; padding: 11px 8px;
      font-size: 13px; font-weight: 700; color: var(--main-color);
      background: #fff; border: none; cursor: pointer;
      font-family: "Montserrat", sans-serif;
      display: flex; align-items: center; justify-content: center; gap: 7px;
      transition: background 0.2s, color 0.2s;
    }
    .role-tab + .role-tab { border-left: 2px solid var(--main-color); }
    .role-tab.active { background: var(--main-color); color: #fff; }
    .role-tab i { font-size: 13px; }

    /* Form panels */
    .form-panel { display: none; }
    .form-panel.active { display: block; animation: formEntrance 0.4s ease-out both; }

    /* Inputs — match login exactly */
    .auth-card .input-box { position: relative; margin-bottom: 1.1rem; }
    .auth-card .input-box input {
      width: 100%; padding: 15px 45px 15px 20px;
      border: 2px solid var(--main-color); border-radius: 12px;
      background-color: var(--bg-alt-color); outline: none;
      font-size: 14px; color: var(--text-color); font-weight: 500;
      font-family: "Montserrat", sans-serif; transition: box-shadow 0.2s;
    }
    .auth-card .input-box input:focus { box-shadow: 0 0 0 3px rgba(141,11,65,0.14); }
    .auth-card .input-box input::placeholder { color: var(--text-alt-color); font-weight: 400; }
    .auth-card .input-box i {
      position: absolute; right: 18px; top: 50%; transform: translateY(-50%);
      color: var(--main-color); font-size: 15px;
    }
    .auth-card .input-box input.has-toggle { padding-right: 52px; }

    /* Eye toggle — same as login */
    .toggle-password {
      position: absolute; right: 18px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      padding: 0; line-height: 1; z-index: 4;
      color: var(--main-color); font-size: 1rem;
      display: flex; align-items: center; transition: opacity 0.2s;
    }
    .toggle-password:hover { opacity: 0.60; }

    /* Password strength */
    .strength-wrap { display: none; margin: -8px 0 12px; text-align: left; }
    .strength-wrap.visible { display: block; }
    .strength-bars { display: flex; gap: 5px; margin-bottom: 5px; }
    .strength-bar  { flex: 1; height: 4px; border-radius: 99px; background: #e0e0e0; transition: background 0.3s; }
    .strength-bar.weak   { background: #e53935; }
    .strength-bar.fair   { background: #fb8c00; }
    .strength-bar.good   { background: #fdd835; }
    .strength-bar.strong { background: #43a047; }
    .strength-label { font-size: 11px; font-weight: 700; color: var(--text-alt-color); text-align: right; margin-bottom: 6px; transition: color 0.3s; }
    .rules-grid { display: flex; flex-direction: column; gap: 3px; }
    .rule { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-alt-color); transition: color 0.2s; }
    .rule i { font-size: 10px; width: 12px; color: #ccc; transition: color 0.2s; }
    .rule.pass { color: #43a047; }
    .rule.pass i { color: #43a047; }

    /* Terms */
    .terms-row {
      display: flex; align-items: center; gap: 9px;
      margin: 4px 0 16px; font-size: 12.5px;
      color: var(--text-alt-color); text-align: left;
    }
    .terms-row input[type=checkbox] { width: 16px; height: 16px; flex-shrink: 0; cursor: pointer; accent-color: var(--main-color); }
    .terms-row a { color: var(--main-color); font-weight: 600; text-decoration: none; }
    .terms-row a:hover { text-decoration: underline; }

    /* Submit — mirrors .btn-primary.login */
    .btn-signup {
      width: 100%; padding: 14px;
      background-color: var(--main-color); color: #fff;
      border: none; border-radius: 10px;
      font-weight: 700; font-size: 1rem;
      font-family: "Montserrat", sans-serif; cursor: pointer;
      transition: filter 0.22s, box-shadow 0.22s, transform 0.15s;
    }
    .btn-signup:hover { filter: brightness(1.12); box-shadow: 0 6px 20px rgba(141,11,65,0.30); transform: translateY(-2px); }
    .btn-signup:active { transform: translateY(0); box-shadow: none; filter: none; }

    /* Google — identical to login */
    .btn-google {
      width: 100%; padding: 13px 18px;
      border: 2px solid var(--main-color); border-radius: 10px;
      background: #fff; color: var(--main-color);
      display: flex; justify-content: center; align-items: center; gap: 11px;
      text-decoration: none; font-size: 14px; font-weight: 700;
      position: relative; overflow: hidden;
      transition: color 0.28s ease, box-shadow 0.28s ease, transform 0.18s ease;
    }
    .btn-google::before {
      content: ''; position: absolute; inset: 0;
      background: var(--main-color); transform: scaleX(0);
      transform-origin: left center;
      transition: transform 0.30s cubic-bezier(0.4,0,0.2,1); z-index: 0;
    }
    .btn-google:hover::before { transform: scaleX(1); }
    .btn-google:hover { color: #fff; box-shadow: 0 6px 22px rgba(141,11,65,0.28); transform: translateY(-2px); }
    .btn-google:active { transform: translateY(0); box-shadow: none; }
    .btn-google .g-icon, .btn-google span { position: relative; z-index: 1; }
    .btn-google .g-icon { width: 20px; height: 20px; flex-shrink: 0; transition: filter 0.28s ease; }
    .btn-google:hover .g-icon { filter: brightness(0) invert(1); }

    .redirect { margin-top: 1.25rem; font-size: 0.9rem; color: var(--text-color); font-weight: 500; }
    .redirect a { color: var(--main-color); text-decoration: none; font-weight: 700; }
    .redirect a:hover { text-decoration: underline; opacity: 0.8; }

    @media (max-width: 850px) { .hero-section { display: none; } }
  </style>
</head>
<body>
<div class="main-wrapper">

  <!-- ══════ LEFT HERO — same as login ══════ -->
  <div class="hero-section">
    <div class="map-box">
      <img src="maps.jpg" alt="Map background">
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-orb"></div>

    <div class="hero-content">
      <div class="hero-badge">
        <i class="fa-solid fa-circle-dot fa-beat-fade"></i>
        Trusted by Thousands of Filipinos
      </div>

      <div class="hero-headline">
        <h1>Find Your <span>Safe</span><br>Space</h1>
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

      <div class="hero-stats">
        <div class="stat-item">
          <span class="stat-value">2,400+</span>
          <span class="stat-label">Active Listings</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">98%</span>
          <span class="stat-label">Verified Landlords</span>
        </div>
        <div class="stat-item">
          <span class="stat-value">15k+</span>
          <span class="stat-label">Happy Tenants</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════ RIGHT SIGNUP PANEL ══════ -->
  <div class="auth-section">
    <div class="auth-card">

      <div class="brand-identity">
        <img src="../img/new_logo.png" alt="MapAware Home Logo">
      </div>

      <!-- Role switcher -->
      <div class="role-tabs">
        <button class="role-tab <?= $activeRole === 'landlord' ? 'active' : '' ?>" id="tab-landlord" onclick="switchTab('landlord')">
          <i class="fa-solid fa-house"></i> Landlord
        </button>
        <button class="role-tab <?= $activeRole === 'tenant' ? 'active' : '' ?>" id="tab-tenant" onclick="switchTab('tenant')">
          <i class="fa-solid fa-user"></i> Tenant
        </button>
      </div>

      <!-- ── LANDLORD ── -->
      <div class="form-panel <?= $activeRole === 'landlord' ? 'active' : '' ?>" id="panel-landlord">
        <form method="POST" action="signup.php">
          <input type="hidden" name="role" value="landlord">

          <div class="input-box">
            <input type="text" name="username" placeholder="Username" required
                   value="<?= (($_POST['role'] ?? '') === 'landlord' && isset($_POST['username'])) ? htmlspecialchars($_POST['username']) : '' ?>">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="input-box">
            <input type="email" name="email" placeholder="Email" required
                   value="<?= (($_POST['role'] ?? '') === 'landlord' && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : '' ?>">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <div class="input-box">
            <input type="password" name="password" id="pw-landlord"
                   placeholder="Password (min. 8 chars)" class="has-toggle" required
                   oninput="checkStrength('landlord')"
                   onfocus="document.getElementById('str-landlord').classList.add('visible')"
                   onblur="if(!this.value) document.getElementById('str-landlord').classList.remove('visible')">
            <button type="button" class="toggle-password" onclick="togglePw('pw-landlord',this)">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
          <div class="strength-wrap" id="str-landlord">
            <div class="strength-bars">
              <div class="strength-bar" id="lb1"></div><div class="strength-bar" id="lb2"></div>
              <div class="strength-bar" id="lb3"></div><div class="strength-bar" id="lb4"></div>
            </div>
            <div class="strength-label" id="llbl">Start typing…</div>
            <div class="rules-grid">
              <div class="rule" id="lr1"><i class="fa-solid fa-circle-check"></i> At least 8 characters</div>
              <div class="rule" id="lr2"><i class="fa-solid fa-circle-check"></i> Uppercase letter (A–Z)</div>
              <div class="rule" id="lr3"><i class="fa-solid fa-circle-check"></i> Lowercase letter (a–z)</div>
              <div class="rule" id="lr4"><i class="fa-solid fa-circle-check"></i> Number (0–9)</div>
              <div class="rule" id="lr5"><i class="fa-solid fa-circle-check"></i> Special character (!@#$…)</div>
            </div>
          </div>
          <div class="terms-row">
            <input type="checkbox" id="terms-landlord" required>
            <label for="terms-landlord">I agree with <a href="terms-landlord.html" target="_blank">Terms and Conditions</a></label>
          </div>
          <button type="submit" class="btn-signup">Sign Up</button>
          <div class="divider"><span>or</span></div>
          <div class="socials">
            <a href="<?= htmlspecialchars($googleUrlLandlord) ?>" class="btn-google">
              <svg class="g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
              </svg>
              <span>Sign up with Google</span>
            </a>
            <p class="redirect">Already have an account? <a href="login.php">Login</a></p>
          </div>
        </form>
      </div>

      <!-- ── TENANT ── -->
      <div class="form-panel <?= $activeRole === 'tenant' ? 'active' : '' ?>" id="panel-tenant">
        <form method="POST" action="signup.php">
          <input type="hidden" name="role" value="tenant">

          <div class="input-box">
            <input type="text" name="username" placeholder="Username" required
                   value="<?= (($_POST['role'] ?? '') === 'tenant' && isset($_POST['username'])) ? htmlspecialchars($_POST['username']) : '' ?>">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="input-box">
            <input type="email" name="email" placeholder="Email" required
                   value="<?= (($_POST['role'] ?? '') === 'tenant' && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : '' ?>">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <div class="input-box">
            <input type="password" name="password" id="pw-tenant"
                   placeholder="Password (min. 8 chars)" class="has-toggle" required
                   oninput="checkStrength('tenant')"
                   onfocus="document.getElementById('str-tenant').classList.add('visible')"
                   onblur="if(!this.value) document.getElementById('str-tenant').classList.remove('visible')">
            <button type="button" class="toggle-password" onclick="togglePw('pw-tenant',this)">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
          <div class="strength-wrap" id="str-tenant">
            <div class="strength-bars">
              <div class="strength-bar" id="tb1"></div><div class="strength-bar" id="tb2"></div>
              <div class="strength-bar" id="tb3"></div><div class="strength-bar" id="tb4"></div>
            </div>
            <div class="strength-label" id="tlbl">Start typing…</div>
            <div class="rules-grid">
              <div class="rule" id="tr1"><i class="fa-solid fa-circle-check"></i> At least 8 characters</div>
              <div class="rule" id="tr2"><i class="fa-solid fa-circle-check"></i> Uppercase letter (A–Z)</div>
              <div class="rule" id="tr3"><i class="fa-solid fa-circle-check"></i> Lowercase letter (a–z)</div>
              <div class="rule" id="tr4"><i class="fa-solid fa-circle-check"></i> Number (0–9)</div>
              <div class="rule" id="tr5"><i class="fa-solid fa-circle-check"></i> Special character (!@#$…)</div>
            </div>
          </div>
          <div class="terms-row">
            <input type="checkbox" id="terms-tenant" required>
            <label for="terms-tenant">I agree with <a href="terms-tenant.html" target="_blank">Terms and Conditions</a></label>
          </div>
          <button type="submit" class="btn-signup">Sign Up</button>
          <div class="divider"><span>or</span></div>
          <div class="socials">
            <a href="<?= htmlspecialchars($googleUrlTenant) ?>" class="btn-google">
              <svg class="g-icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
              </svg>
              <span>Sign up with Google</span>
            </a>
            <p class="redirect">Already have an account? <a href="login.php">Login</a></p>
          </div>
        </form>
      </div>

    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($message) && $messageType === 'error'): ?>
<script>
  Swal.fire({ icon:'error', title:'Sign Up Failed', text:'<?= addslashes($message) ?>',
    confirmButtonColor:'#8d0b41', confirmButtonText:'Try Again' });
</script>
<?php endif; ?>

<?php if (!empty($message) && $messageType === 'success'): ?>
<script>
  Swal.fire({ icon:'success', title:'Account Created!', text:'<?= addslashes($message) ?>',
    confirmButtonColor:'#8d0b41', confirmButtonText:'Okay'
  }).then(() => window.location.href = 'login.php');
</script>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>
  Swal.fire({ icon:'success', title:'Success!', text:'<?= addslashes($_SESSION['success']) ?>',
    confirmButtonColor:'#8d0b41', timer:3000, timerProgressBar:true });
</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<script>
  function switchTab(role) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-'   + role).classList.add('active');
    document.getElementById('panel-' + role).classList.add('active');
  }

  function togglePw(inputId, btn) {
    const inp  = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    const hide = inp.type === 'password';
    inp.type = hide ? 'text' : 'password';
    icon.classList.toggle('fa-eye',      !hide);
    icon.classList.toggle('fa-eye-slash', hide);
  }

  function checkStrength(role) {
    const p   = role[0];
    const val = document.getElementById('pw-' + role).value;
    const rules = {
      length:  val.length >= 8,
      upper:   /[A-Z]/.test(val),
      lower:   /[a-z]/.test(val),
      number:  /[0-9]/.test(val),
      special: /[^A-Za-z0-9]/.test(val),
    };
    ['length','upper','lower','number','special'].forEach((k, i) => {
      document.getElementById(p + 'r' + (i+1)).classList.toggle('pass', rules[k]);
    });
    const score = Object.values(rules).filter(Boolean).length;
    const bars  = [1,2,3,4].map(n => document.getElementById(p + 'b' + n));
    const lbl   = document.getElementById(p + 'lbl');
    bars.forEach(b => { b.className = 'strength-bar'; });
    if (!val) { lbl.textContent = 'Start typing…'; lbl.style.color = '#647887'; return; }
    const levels = [
      { max:1, barClass:['weak'],                              text:'Very Weak',   color:'#e53935' },
      { max:2, barClass:['weak','fair'],                       text:'Weak',        color:'#fb8c00' },
      { max:3, barClass:['fair','fair','good'],                text:'Fair',        color:'#f9a825' },
      { max:4, barClass:['good','good','good','strong'],       text:'Strong',      color:'#43a047' },
      { max:5, barClass:['strong','strong','strong','strong'], text:'Very Strong', color:'#2e7d32' },
    ];
    const lvl = levels.find(l => score <= l.max) || levels[4];
    lvl.barClass.forEach((cls, i) => { if (bars[i]) bars[i].classList.add(cls); });
    lbl.textContent = lvl.text; lbl.style.color = lvl.color;
  }
</script>
</body>
</html>