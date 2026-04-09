<?php
session_start();
include '../includes/db.php';

$message = "";
$messageType = "";

// If form is submitted (verify OTP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
  $enteredOtp = trim($_POST['otp']);

  if (!isset($_SESSION['device_otp'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry'])) {
    $_SESSION['error'] = "OTP expired. Please login again.";
    header("Location: login.php");
    exit();
  }

  if ($enteredOtp != $_SESSION['device_otp'] || time() > $_SESSION['otp_expiry']) {
    $message = "Invalid or expired OTP. Please try again.";
    $messageType = "error";
  } else {
    $stmt = $conn->prepare("
      INSERT INTO trusted_devices (user_id, device_hash, last_ip, last_used, role)
      VALUES (?, ?, ?, NOW(), ?)
      ON DUPLICATE KEY UPDATE last_ip=VALUES(last_ip), last_used=NOW()
    ");
    $stmt->bind_param("isss",
      $_SESSION['otp_user_id'],
      $_SESSION['otp_device_hash'],
      $_SERVER['REMOTE_ADDR'],
      $_SESSION['otp_role']
    );
    $stmt->execute();

    $_SESSION['user_id']   = $_SESSION['otp_user_id'];
    $_SESSION['username']  = $_SESSION['otp_username'] ?? $_SESSION['otp_name'];
    $_SESSION['full_name'] = $_SESSION['otp_name'];
    $_SESSION['user_type'] = $_SESSION['otp_role'];

    if ($_SESSION['otp_role'] === 'tenant') {
      $_SESSION['tenant_id'] = $_SESSION['otp_user_id'];
    } elseif ($_SESSION['otp_role'] === 'landlord') {
      $_SESSION['landlord_id'] = $_SESSION['otp_user_id'];
    }

    $redirect = $_SESSION['otp_redirect'] ?? ($_SESSION['otp_role'] === 'landlord'
      ? '/TAHANAN/LANDLORD/landlord-properties.php'
      : '/TAHANAN/TENANT/tenant.php');

    unset(
      $_SESSION['device_otp'],
      $_SESSION['otp_user_id'],
      $_SESSION['otp_device_hash'],
      $_SESSION['otp_role'],
      $_SESSION['otp_expiry'],
      $_SESSION['otp_name'],
      $_SESSION['otp_username'],
      $_SESSION['otp_email'],
      $_SESSION['otp_redirect']
    );

    header("Location: $redirect");
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email — Tahanan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --main:        #8D0B41;
      --main-hover:  #b30d4f;
      --main-light:  #fce8f0;
      --main-ring:   rgba(141,11,65,0.15);
      --bg:          #fafafa;
      --bg-alt:      #f0f0f0;
      --text:        #42505A;
      --muted:       #647887;
      --border:      #dde3e8;
      --white:       #ffffff;
      --success-bg:  #eaf3de;
      --success-txt: #3b6d11;
      --success-bdr: #97c459;
      --error-bg:    #fcebeb;
      --error-txt:   #a32d2d;
      --error-bdr:   #f09595;
      --radius:      12px;
      --radius-sm:   8px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Montserrat', sans-serif;
      background: linear-gradient(135deg, #fafafa 0%, #f0e8ed 50%, #dfdfdf 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .card {
      background: var(--white);
      border-radius: 24px;
      box-shadow: 0 8px 40px rgba(141,11,65,0.08), 0 2px 8px rgba(0,0,0,0.06);
      padding: 2.5rem 2.25rem;
      width: 100%;
      max-width: 460px;
      text-align: center;
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .icon-wrap {
      width: 72px; height: 72px;
      background: var(--main-light);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.5rem;
    }
    .icon-wrap svg { width: 32px; height: 32px; }

    h1 {
      font-size: 26px;
      font-weight: 800;
      color: var(--main);
      margin-bottom: 0.5rem;
    }

    .subtitle {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.65;
      margin-bottom: 2rem;
    }
    .subtitle strong { color: var(--text); font-weight: 600; }

    /* ── Alert ── */
    .alert {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 1.25rem;
      text-align: left;
      animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
    .alert-error   { background: var(--error-bg);   color: var(--error-txt);   border: 1px solid var(--error-bdr); }
    .alert-success { background: var(--success-bg); color: var(--success-txt); border: 1px solid var(--success-bdr); }
    .alert svg { flex-shrink: 0; width: 16px; height: 16px; }

    /* ── OTP Boxes ── */
    .otp-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      text-align: left;
      margin-bottom: 10px;
    }

    .otp-boxes {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-bottom: 6px;
    }

    .otp-box {
      width: 58px;
      height: 64px;
      border-radius: var(--radius-sm);
      border: 1.5px solid var(--border);
      background: var(--bg-alt);
      font-size: 24px;
      font-weight: 700;
      color: var(--text);
      text-align: center;
      transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s, background 0.2s;
      outline: none;
      font-family: 'Montserrat', sans-serif;
    }
    .otp-box:focus {
      border-color: var(--main);
      box-shadow: 0 0 0 3px var(--main-ring);
      background: var(--white);
      transform: scale(1.06) translateY(-2px);
    }
    .otp-box.filled {
      border-color: var(--main);
      background: var(--white);
    }
    .otp-box.error {
      border-color: #e24b4a;
      background: var(--error-bg);
      box-shadow: 0 0 0 3px rgba(226,75,74,0.12);
    }
    .otp-box.shake {
      animation: shake 0.4s ease;
    }
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-6px); }
      40%      { transform: translateX(6px); }
      60%      { transform: translateX(-4px); }
      80%      { transform: translateX(4px); }
    }

    /* ── Progress bar ── */
    .progress-wrap {
      height: 3px;
      background: var(--bg-alt);
      border-radius: 99px;
      overflow: hidden;
      margin: 10px 0 1.5rem;
    }
    .progress-fill {
      height: 100%;
      background: var(--main);
      border-radius: 99px;
      width: 0%;
      transition: width 0.25s ease;
    }

    /* ── Button ── */
    .btn {
      width: 100%;
      padding: 15px;
      background: var(--main);
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      font-size: 15px;
      font-weight: 700;
      font-family: 'Montserrat', sans-serif;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s, opacity 0.2s;
      margin-bottom: 1rem;
      letter-spacing: 0.3px;
    }
    .btn:hover:not(:disabled) { background: var(--main-hover); }
    .btn:active:not(:disabled) { transform: scale(0.98); }
    .btn:disabled { opacity: 0.45; cursor: not-allowed; }

    /* ── Resend ── */
    .resend-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 1.5rem;
    }
    .resend-btn {
      background: none;
      border: none;
      color: var(--main);
      font-size: 13px;
      font-weight: 600;
      font-family: 'Montserrat', sans-serif;
      cursor: pointer;
      transition: color 0.2s;
      padding: 0;
    }
    .resend-btn:hover:not(:disabled) { color: var(--main-hover); text-decoration: underline; }
    .resend-btn:disabled { color: var(--muted); cursor: not-allowed; }

    /* ── Divider + Links ── */
    .divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0.5rem 0 1.25rem;
    }
    .divider-line { flex: 1; height: 1px; background: var(--border); }
    .divider-text { font-size: 12px; color: var(--muted); }

    .link-row {
      font-size: 13px;
      color: var(--muted);
      line-height: 2;
    }
    .link-row a {
      color: var(--main);
      text-decoration: none;
      font-weight: 600;
    }
    .link-row a:hover { color: var(--main-hover); text-decoration: underline; }
  </style>
</head>
<body>

<div class="card">

  <div class="icon-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke="#8D0B41" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
      <polyline points="22,6 12,13 2,6"/>
    </svg>
  </div>

  <h1>Verify your email</h1>
  <p class="subtitle">
    We sent a 6-digit verification code to your email address.<br>
    Enter it below to activate your account.
  </p>

  <?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageType === 'error' ? 'error' : 'success' ?>">
    <?php if ($messageType === 'error'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9,12 11,14 15,10"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <p class="otp-label">Verification code</p>

  <form method="POST" action="" id="otp-form">
    <!-- Hidden field that holds the assembled OTP -->
    <input type="hidden" name="otp" id="otp-hidden">

    <div class="otp-boxes" id="otp-boxes">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>" <?= $i === 0 ? 'autofocus' : '' ?>>
      <?php endfor; ?>
    </div>

    <div class="progress-wrap"><div class="progress-fill" id="progress"></div></div>

    <button type="submit" class="btn" id="submit-btn" disabled>Verify email</button>
  </form>

  <div class="resend-row">
    Didn't receive the code?
    <button class="resend-btn" id="resend-btn" onclick="resendCode()">Resend</button>
    <span id="resend-timer"></span>
  </div>

  <div class="divider">
    <div class="divider-line"></div>
    <div class="divider-text">or</div>
    <div class="divider-line"></div>
  </div>

  <div class="link-row">
    <a href="signup.php">Back to Signup</a><br>
    <a href="login.php">Already have an account? Login</a>
  </div>

</div>

<script>
  const boxes     = document.querySelectorAll('.otp-box');
  const hidden    = document.getElementById('otp-hidden');
  const progress  = document.getElementById('progress');
  const submitBtn = document.getElementById('submit-btn');

  boxes.forEach((box, i) => {
    box.addEventListener('input', e => {
      box.value = box.value.replace(/\D/g, '').slice(-1);
      box.classList.toggle('filled', !!box.value);
      if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
      updateState();
    });

    box.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !box.value && i > 0) {
        boxes[i - 1].value = '';
        boxes[i - 1].classList.remove('filled');
        boxes[i - 1].focus();
        updateState();
      }
    });

    box.addEventListener('paste', e => {
      e.preventDefault();
      const txt = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      boxes.forEach((b, j) => {
        b.value = txt[j] || '';
        b.classList.toggle('filled', !!b.value);
      });
      const last = Math.min(txt.length, boxes.length) - 1;
      if (last >= 0) boxes[last].focus();
      updateState();
    });
  });

  function updateState() {
    const filled = [...boxes].filter(b => b.value).length;
    progress.style.width = (filled / boxes.length * 100) + '%';
    const code = [...boxes].map(b => b.value).join('');
    hidden.value = code;
    submitBtn.disabled = filled < boxes.length;
  }

  <?php if ($messageType === 'error'): ?>
  // Shake boxes on page load if there was an error
  window.addEventListener('DOMContentLoaded', () => {
    boxes.forEach(b => {
      b.classList.add('error', 'shake');
      setTimeout(() => b.classList.remove('shake'), 500);
    });
  });
  <?php endif; ?>

  // Resend OTP with cooldown
  let resendCooldown;
  function resendCode() {
    const btn   = document.getElementById('resend-btn');
    const timer = document.getElementById('resend-timer');
    btn.disabled = true;
    let secs = 30;
    timer.textContent = '(' + secs + 's)';

    fetch('resend_otp.php')
      .then(r => r.json())
      .then(data => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + (data.success ? 'success' : 'error');
        alert.innerHTML = data.success
          ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:16px;height:16px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><polyline points="9,12 11,14 15,10"/></svg>'
          : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="width:16px;height:16px;flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
        alert.innerHTML += ' ' + data.message;
        document.querySelector('.card').insertBefore(alert, document.querySelector('.otp-label'));
        setTimeout(() => alert.remove(), 4000);
      })
      .catch(() => {});

    resendCooldown = setInterval(() => {
      secs--;
      if (secs <= 0) {
        clearInterval(resendCooldown);
        btn.disabled = false;
        timer.textContent = '';
      } else {
        timer.textContent = '(' + secs + 's)';
      }
    }, 1000);
  }
</script>

</body>
</html>