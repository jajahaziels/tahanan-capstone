<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email']) || !isset($_SESSION['otp_expiry']) || !isset($_SESSION['user_type'])) {
  header("Location: signup.php");
  exit;
}

$message     = "";
$messageType = "";

if (time() > $_SESSION['otp_expiry']) {
  unset($_SESSION['otp']);
  $message     = "OTP expired. Please request a new one.";
  $messageType = "error";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
  $enteredOtp = trim($_POST['otp']);

  if (!isset($_SESSION['otp'])) {
    $message     = "OTP expired. Please request a new one.";
    $messageType = "error";
  } elseif ($enteredOtp == $_SESSION['otp']) {
    $email = $_SESSION['otp_email'];
    $role  = $_SESSION['otp_role'];

    $sql = $role === 'landlord'
      ? "UPDATE landlordtbl SET status='active' WHERE email=?"
      : "UPDATE tenanttbl SET status='active' WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $sqlUser = $role === 'landlord'
      ? "SELECT ID, firstName, lastName FROM landlordtbl WHERE email=? LIMIT 1"
      : "SELECT ID, firstName, lastName FROM tenanttbl WHERE email=? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("s", $email);
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();

    $_SESSION['user_id']   = $user['ID'];
    $_SESSION['username']  = $user['firstName'] . ' ' . $user['lastName'];
    $_SESSION['user_type'] = $role;

    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['email']);

    header("Location: " . ($role === 'landlord'
      ? '/TAHANAN/LANDLORD/landlord-properties.php'
      : '/TAHANAN/TENANT/tenant.php'));
    exit();
  } else {
    $message     = "Invalid OTP. Please try again.";
    $messageType = "error";
  }
}

// Resend OTP
if (isset($_POST['resend'])) {
  $newOtp = rand(100000, 999999);
  $_SESSION['otp']        = $newOtp;
  $_SESSION['otp_expiry'] = time() + 600;
  mail($_SESSION['otp_email'], "Your OTP Code", "Your OTP code is: $newOtp. It expires in 10 minutes.");
  $message     = "A new OTP has been sent to your email.";
  $messageType = "success";
}

$secondsLeft = max(0, $_SESSION['otp_expiry'] - time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify OTP — MapAware Home</title>
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
      --success-bg:  #eaf3de;
      --success-txt: #3b6d11;
      --success-bdr: #97c459;
      --error-bg:    #fcebeb;
      --error-txt:   #a32d2d;
      --error-bdr:   #f09595;
      --warn-bg:     #faeeda;
      --warn-txt:    #854f0b;
      --warn-bdr:    #ef9f27;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Montserrat', sans-serif;
      background: linear-gradient(135deg, #fafafa 0%, #f0e8ed 50%, #dfdfdf 100%);
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 1.5rem;
    }
    .card {
      background: var(--white);
      border-radius: 24px;
      box-shadow: 0 8px 40px rgba(141,11,65,0.08), 0 2px 8px rgba(0,0,0,0.06);
      padding: 2.5rem 2.25rem;
      width: 100%; max-width: 460px;
      text-align: center;
      animation: slideUp 0.4s ease;
    }
    @keyframes slideUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .icon-wrap {
      width: 72px; height: 72px;
      background: var(--main-light);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.5rem;
    }
    .icon-wrap svg { width: 32px; height: 32px; }
    h1 { font-size: 26px; font-weight: 800; color: var(--main); margin-bottom: 0.5rem; }
    .subtitle { font-size: 14px; color: var(--muted); line-height: 1.65; margin-bottom: 1.25rem; }

    /* Timer pill */
    .timer-pill {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--main-light);
      color: var(--main);
      font-size: 13px; font-weight: 600;
      padding: 6px 14px;
      border-radius: 99px;
      margin-bottom: 1.5rem;
    }
    .timer-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--main);
      animation: pulse 1s ease-in-out infinite;
    }
    .timer-pill.expired { background: var(--error-bg); color: var(--error-txt); }
    .timer-pill.expired .timer-dot { background: var(--error-txt); animation: none; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 11px 14px; border-radius: 8px;
      font-size: 13px; font-weight: 600;
      margin-bottom: 1.25rem; text-align: left;
      animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }
    .alert-error   { background:var(--error-bg);   color:var(--error-txt);   border:1px solid var(--error-bdr); }
    .alert-success { background:var(--success-bg); color:var(--success-txt); border:1px solid var(--success-bdr); }
    .alert-warn    { background:var(--warn-bg);    color:var(--warn-txt);    border:1px solid var(--warn-bdr); }
    .alert svg { flex-shrink:0; width:16px; height:16px; }

    .otp-label { font-size:13px; font-weight:600; color:var(--muted); text-align:left; margin-bottom:10px; }

    .otp-boxes { display:flex; gap:10px; justify-content:center; margin-bottom:6px; }
    .otp-box {
      width:58px; height:64px;
      border-radius:8px;
      border:1.5px solid var(--border);
      background:var(--bg-alt);
      font-size:24px; font-weight:700;
      color:var(--text); text-align:center;
      transition:border-color 0.2s,box-shadow 0.2s,transform 0.15s,background 0.2s;
      outline:none;
      font-family:'Montserrat',sans-serif;
    }
    .otp-box:focus {
      border-color:var(--main);
      box-shadow:0 0 0 3px var(--main-ring);
      background:var(--white);
      transform:scale(1.06) translateY(-2px);
    }
    .otp-box.filled { border-color:var(--main); background:var(--white); }
    .otp-box.error  { border-color:#e24b4a; background:var(--error-bg); box-shadow:0 0 0 3px rgba(226,75,74,0.12); }
    .otp-box.shake  { animation:shake 0.4s ease; }
    @keyframes shake {
      0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)}
      40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)}
    }
    .progress-wrap { height:3px; background:var(--bg-alt); border-radius:99px; overflow:hidden; margin:10px 0 1.5rem; }
    .progress-fill { height:100%; background:var(--main); border-radius:99px; width:0%; transition:width 0.25s ease; }

    .btn {
      width:100%; padding:15px;
      background:var(--main); color:#fff;
      border:none; border-radius:8px;
      font-size:15px; font-weight:700;
      font-family:'Montserrat',sans-serif;
      cursor:pointer;
      transition:background 0.2s,transform 0.1s,opacity 0.2s;
      margin-bottom:1rem;
    }
    .btn:hover:not(:disabled) { background:var(--main-hover); }
    .btn:active:not(:disabled) { transform:scale(0.98); }
    .btn:disabled { opacity:0.45; cursor:not-allowed; }

    .resend-form { display:flex; align-items:center; justify-content:center; gap:5px; margin-bottom:1.5rem; font-size:13px; color:var(--muted); }
    .resend-btn {
      background:none; border:none;
      color:var(--main); font-size:13px; font-weight:600;
      font-family:'Montserrat',sans-serif;
      cursor:pointer; padding:0;
      transition:color 0.2s;
    }
    .resend-btn:hover { color:var(--main-hover); text-decoration:underline; }

    .divider { display:flex; align-items:center; gap:10px; margin:0.5rem 0 1.25rem; }
    .divider-line { flex:1; height:1px; background:var(--border); }
    .divider-text { font-size:12px; color:var(--muted); }
    .link-row { font-size:13px; color:var(--muted); line-height:2; }
    .link-row a { color:var(--main); text-decoration:none; font-weight:600; }
    .link-row a:hover { color:var(--main-hover); text-decoration:underline; }
  </style>
</head>
<body>

<div class="card">

  <div class="icon-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke="#8D0B41" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="5" y="11" width="14" height="10" rx="2"/>
      <path d="M12 15v2"/>
      <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
    </svg>
  </div>

  <h1>Enter your OTP</h1>
  <p class="subtitle">We sent a one-time password to your registered email. Enter it below to continue.</p>

  <div class="timer-pill" id="timer-pill">
    <div class="timer-dot" id="timer-dot"></div>
    <span id="countdown">Expires in <?= gmdate('i:s', $secondsLeft) ?></span>
  </div>

  <?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageType ?>">
    <?php if ($messageType === 'error'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9,12 11,14 15,10"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($message) ?>
  </div>
  <?php endif; ?>

  <p class="otp-label">One-time password</p>

  <form method="POST" id="otp-form">
    <input type="hidden" name="otp" id="otp-hidden">
    <div class="otp-boxes" id="otp-boxes">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" <?= $i === 0 ? 'autofocus' : '' ?>>
      <?php endfor; ?>
    </div>
    <div class="progress-wrap"><div class="progress-fill" id="progress"></div></div>
    <button type="submit" class="btn" id="submit-btn" disabled>Verify OTP</button>
  </form>

  <form method="POST" class="resend-form">
    Didn't get it?
    <button type="submit" name="resend" class="resend-btn">Resend OTP</button>
  </form>

  <div class="divider">
    <div class="divider-line"></div><div class="divider-text">or</div><div class="divider-line"></div>
  </div>
  <div class="link-row"><a href="signup.php">Back to Signup</a></div>

</div>

<script>
  const boxes     = document.querySelectorAll('.otp-box');
  const hidden    = document.getElementById('otp-hidden');
  const progress  = document.getElementById('progress');
  const submitBtn = document.getElementById('submit-btn');

  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
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
      boxes.forEach((b, j) => { b.value = txt[j] || ''; b.classList.toggle('filled', !!b.value); });
      const last = Math.min(txt.length, boxes.length) - 1;
      if (last >= 0) boxes[last].focus();
      updateState();
    });
  });

  function updateState() {
    const filled = [...boxes].filter(b => b.value).length;
    progress.style.width = (filled / boxes.length * 100) + '%';
    hidden.value = [...boxes].map(b => b.value).join('');
    submitBtn.disabled = filled < boxes.length;
  }

  <?php if ($messageType === 'error'): ?>
  boxes.forEach(b => { b.classList.add('error', 'shake'); setTimeout(() => b.classList.remove('shake'), 500); });
  <?php endif; ?>

  // Countdown
  let secs = <?= $secondsLeft ?>;
  const pill = document.getElementById('timer-pill');
  const dot  = document.getElementById('timer-dot');
  const cd   = document.getElementById('countdown');
  const tick = setInterval(() => {
    secs--;
    if (secs <= 0) {
      clearInterval(tick);
      cd.textContent = 'OTP expired';
      pill.classList.add('expired');
      submitBtn.disabled = true;
      boxes.forEach(b => b.disabled = true);
    } else {
      const m = Math.floor(secs / 60), s = secs % 60;
      cd.textContent = 'Expires in ' + m + ':' + String(s).padStart(2, '0');
    }
  }, 1000);
</script>

</body>
</html>