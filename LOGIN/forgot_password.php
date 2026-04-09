<?php
include '../includes/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$message     = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = strtolower(trim($_POST['email']));

  $found  = false;
  foreach (['admintbl', 'landlordtbl', 'tenanttbl'] as $table) {
    $stmt = $conn->prepare("SELECT email FROM $table WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { $found = true; break; }
  }

  if (!$found) {
    $message     = "No account found with that email address.";
    $messageType = "error";
  } else {
    $otp        = rand(100000, 999999);
    $expires_at = date("Y-m-d H:i:s", time() + 600);

    $conn->query("ALTER TABLE reset_password ADD UNIQUE (email)");
    $stmt = $conn->prepare("
      INSERT INTO reset_password (email, token, expires_at) VALUES (?, ?, ?)
      ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ");
    $stmt->bind_param("sss", $email, $otp, $expires_at);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
      try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = "jajasison07@gmail.com";
        $mail->Password   = "aebfllyitmpjvzqz";
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('jajasison07@gmail.com', 'MapAware Home');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'MapAware Home Password Reset OTP';
        $mail->Body    = "
          <div style='font-family:sans-serif;max-width:820px;margin:auto;padding:32px;background:#fafafa;border-radius:12px;'>
            <h2 style='color:#8D0B41;margin-bottom:8px;'>Reset your password</h2>
            <p style='color:#647887;margin-bottom:24px;'>Use the OTP below to reset your MapAware Home password. It expires in 10 minutes.</p>
            <div style='background:#fff;border:1.5px solid #f4c0d1;border-radius:10px;padding:24px;text-align:center;margin-bottom:24px;'>
              <span style='font-size:36px;font-weight:800;letter-spacing:10px;color:#8D0B41;'>$otp</span>
            </div>
            <p style='color:#aaa;font-size:13px;'>Do not share this code with anyone. If you didn't request this, you can safely ignore this email.</p>
          </div>
        ";
        $mail->send();
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
      } catch (Exception $e) {
        $message     = "Failed to send email: " . $mail->ErrorInfo;
        $messageType = "error";
      }
    } else {
      $message     = "Failed to generate OTP. Please try again.";
      $messageType = "error";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — MapAware Home</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --main:       #8D0B41;
      --main-hover: #b30d4f;
      --main-light: #fce8f0;
      --main-ring:  rgba(141,11,65,0.15);
      --bg:         #fafafa;
      --bg-alt:     #f0f0f0;
      --text:       #42505A;
      --muted:      #647887;
      --border:     #dde3e8;
      --white:      #ffffff;
      --error-bg:   #fcebeb;
      --error-txt:  #a32d2d;
      --error-bdr:  #f09595;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Montserrat', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: stretch;
      background: var(--bg);
    }

    /* ===== LEFT PANEL (NEW MAP DESIGN) ===== */
.split-left{
  flex:1;
  position:relative;
  overflow:hidden;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
}

/* map image */
.map-box{
  position:absolute;
  width:100%;
  height:100%;
}
.map-box img{
  width:100%;
  height:100%;
  object-fit:cover;
}

/* dark overlay */
.hero-overlay{
  position:absolute;
  width:100%;
  height:100%;
  background:rgba(141,11,65,0.75);
  z-index:1;
}

/* glowing orb */
.hero-orb{
  position:absolute;
  width:400px;
  height:400px;
  background:radial-gradient(circle, rgba(255,255,255,0.2), transparent);
  border-radius:50%;
  top:-100px;
  right:-100px;
  z-index:1;
}

/* content */
.left-content{
  position:relative;
  z-index:2;
  text-align:center;
  max-width:320px;
}

.left-content img{
  width:140px;
  margin-bottom:20px;
  filter:brightness(0) invert(1);
}

.left-content h2{
  font-size:28px;
  font-weight:800;
}

.left-content p{
  font-size:14px;
  opacity:.85;
  margin-top:8px;
}


    /* ── Split layout ── */
    .split-left {
      flex: 1;
      background: var(--main);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 3rem;
      position: relative;
      overflow: hidden;
    }

    .split-left::before {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      border-radius: 50%;
      background: rgba(255,255,255,0.05);
      top: -120px; right: -120px;
    }
    .split-left::after {
      content: '';
      position: absolute;
      width: 300px; height: 300px;
      border-radius: 50%;
      background: rgba(255,255,255,0.05);
      bottom: -60px; left: -60px;
    }

    .brand {
      position: relative; z-index: 1;
      text-align: center;
      color: white;
    }
    .brand-logo-img {
      width: 150px;
      height: auto;
      object-fit: contain;
      display: block;
      margin: 0 auto 1.25rem;
      filter: brightness(0) invert(1);
    }
    .brand h2 {
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -0.5px;
      margin-bottom: 0.5rem;
    }
    .brand p {
      font-size: 14px;
      opacity: 0.75;
      line-height: 1.6;
      max-width: 300px;
      margin: 0 auto;
    }

    .steps-preview {
      position: relative; z-index: 1;
      margin-top: 3rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .step-item {
      display: flex;
      align-items: center;
      gap: 12px;
      color: rgba(255,255,255,0.85);
      font-size: 14px;
      font-weight: 500;
    }
    .step-circle {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700;
      flex-shrink: 0;
    }

    /* ── Right panel ── */
    .split-right {
      width: 820px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem;
      background: var(--white);
    }

    .form-wrap {
      width: 100%;
      max-width: 360px;
      animation: slideUp 0.45s ease;
    }
    @keyframes slideUp {
      from { opacity:0; transform:translateY(18px); }
      to   { opacity:1; transform:none; }
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      text-decoration: none;
      margin-bottom: 2rem;
      transition: color 0.2s;
    }
    .back-btn svg { width: 16px; height: 16px; }
    .back-btn:hover { color: var(--main); }

    h1 {
      font-size: 28px;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 0.4rem;
      letter-spacing: -0.3px;
    }
    .subtitle {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.65;
      margin-bottom: 2rem;
    }

    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 14px; border-radius: 8px;
      font-size: 13px; font-weight: 600;
      margin-bottom: 1.25rem; text-align: left;
      animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:none} }
    .alert-error { background:var(--error-bg); color:var(--error-txt); border:1px solid var(--error-bdr); }
    .alert svg { flex-shrink:0; width:16px; height:16px; }

    .field-label {
      font-size: 13px; font-weight: 600; color: var(--text);
      margin-bottom: 8px; display: block;
    }

    .input-wrap {
      position: relative;
      margin-bottom: 1.5rem;
    }
    .input-wrap svg {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 18px; height: 18px; color: var(--muted);
      pointer-events: none;
    }
    .input-wrap input {
      width: 100%;
      padding: 14px 16px 14px 44px;
      background: var(--bg-alt);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 14px; font-weight: 500;
      color: var(--text);
      font-family: 'Montserrat', sans-serif;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .input-wrap input:focus {
      border-color: var(--main);
      box-shadow: 0 0 0 3px var(--main-ring);
      background: var(--white);
    }
    .input-wrap input::placeholder { color: var(--muted); font-weight: 400; }

    .btn {
      width: 100%; padding: 15px;
      background: var(--main); color: #fff;
      border: none; border-radius: 10px;
      font-size: 15px; font-weight: 700;
      font-family: 'Montserrat', sans-serif;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-bottom: 1.25rem;
    }
    .btn:hover { background: var(--main-hover); box-shadow: 0 4px 16px rgba(141,11,65,0.25); transform: translateY(-1px); }
    .btn:active { transform: none; box-shadow: none; }
    .btn svg { width: 18px; height: 18px; }

    .divider { display:flex; align-items:center; gap:10px; margin-bottom:1.25rem; }
    .divider-line { flex:1; height:1px; background:var(--border); }
    .divider-text { font-size:12px; color:var(--muted); font-weight:500; }

    .link-row { text-align:center; font-size:13px; color:var(--muted); }
    .link-row a { color:var(--main); font-weight:600; text-decoration:none; }
    .link-row a:hover { color:var(--main-hover); text-decoration:underline; }

    /* Loading state */
    .btn.loading { pointer-events:none; opacity:0.7; }
    .btn .spinner {
      width:16px; height:16px;
      border:2px solid rgba(255,255,255,0.4);
      border-top-color:#fff;
      border-radius:50%;
      animation:spin 0.7s linear infinite;
      display:none;
    }
    .btn.loading .btn-text { display:none; }
    .btn.loading .spinner { display:block; }
    @keyframes spin { to{transform:rotate(360deg)} }

    @media (max-width: 768px) {
      body { flex-direction: column; }
      .split-left { display:none; }
      .split-right { width:100%; padding:2rem 1.5rem; }
    }
  </style>
</head>
<body>

<!-- Left decorative panel -->
<div class="split-left">

<div class="map-box">
    <img src="maps.jpg" alt="Map background">
  </div>

  <div class="hero-overlay"></div>
  <div class="hero-orb"></div>

  <div class="brand">
    <img src="../img/new_logo.png" alt="MapAware Home Logo" class="brand-logo-img">
    <h2>Find Your Safe Space</h2>
    <p>Your trusted rental management platform. We'll help you get back in.</p>
  </div>

  <div class="steps-preview">
    <div class="step-item">
      <span>Enter your registered email</span>
    </div>
    <div class="step-item">
      <span>Receive a 6-digit OTP code</span>
    </div>
    <div class="step-item">
      <span>Set your new password</span>
    </div>
  </div>
</div>

<!-- Right form panel -->
<div class="split-right">
  <div class="form-wrap">

    <a href="login.php" class="back-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
      Back to login
    </a>

    <h1>Forgot password?</h1>
    <p class="subtitle">No worries — enter your email and we'll send you a reset code.</p>

    <?php if (!empty($message)): ?>
    <div class="alert alert-error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="forgot-form">
      <label class="field-label" for="email">Email address</label>
      <div class="input-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
          <polyline points="22,6 12,13 2,6"/>
        </svg>
        <input type="email" name="email" id="email" placeholder="you@example.com" required
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
               autofocus>
      </div>

      <button type="submit" class="btn" id="submit-btn">
        <span class="btn-text">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:17px;height:17px">
            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
          Send reset code
        </span>
        <div class="spinner"></div>
      </button>
    </form>

    <div class="divider">
      <div class="divider-line"></div>
      <div class="divider-text">or</div>
      <div class="divider-line"></div>
    </div>

    <div class="link-row">
      Remembered it? <a href="login.php">Sign in instead</a>
    </div>

  </div>
</div>

<script>
  document.getElementById('forgot-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.classList.add('loading');
  });
</script>

</body>
</html>