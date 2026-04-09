<?php
include '../includes/db.php';

$message     = "";
$messageType = "";
$email = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = strtolower(trim($_POST['email']));
  $otp      = trim($_POST['otp']);
  $password = $_POST['password'];
  $confirm  = $_POST['confirm_password'];

  if ($password !== $confirm) {
    $message     = "Passwords do not match.";
    $messageType = "error";
  } else {
    $stmt = $conn->prepare("SELECT * FROM reset_password WHERE email=? AND token=? LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
      $row = $result->fetch_assoc();

      if (time() <= strtotime($row['expires_at'])) {
        $hashed  = password_hash($password, PASSWORD_DEFAULT);
        $updated = false;

        foreach (['admintbl', 'landlordtbl', 'tenanttbl'] as $table) {
          $s = $conn->prepare("UPDATE $table SET password=? WHERE email=?");
          if (!$s) continue;
          $s->bind_param("ss", $hashed, $email);
          $s->execute();
          if ($s->affected_rows > 0) $updated = true;
        }

        if ($updated) {
          $s = $conn->prepare("DELETE FROM reset_password WHERE email=?");
          $s->bind_param("s", $email);
          $s->execute();
          $message     = "Password reset successful! You can now log in.";
          $messageType = "success";
        } else {
          $message     = "No account found for this email address.";
          $messageType = "error";
        }
      } else {
        $message     = "OTP has expired. Please request a new one.";
        $messageType = "error";
      }
    } else {
      $message     = "Invalid OTP. Please check and try again.";
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
  <title>Reset Password — MapAware Home</title>
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
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family:'Montserrat',sans-serif;
      background:linear-gradient(135deg,#fafafa 0%,#f0e8ed 50%,#dfdfdf 100%);
      min-height:100vh;
      display:flex; align-items:center; justify-content:center;
      padding:1.5rem;
    }
    .card {
      background:var(--white);
      border-radius:24px;
      box-shadow:0 8px 40px rgba(141,11,65,0.08),0 2px 8px rgba(0,0,0,0.06);
      padding:2.5rem 2.25rem;
      width:100%; max-width:460px;
      text-align:center;
      animation:slideUp 0.4s ease;
    }
    @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

    .icon-wrap {
      width:72px; height:72px;
      background:var(--main-light);
      border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 1.5rem;
    }
    .icon-wrap svg { width:32px; height:32px; }

    h1 { font-size:26px; font-weight:800; color:var(--main); margin-bottom:0.5rem; }
    .subtitle { font-size:14px; color:var(--muted); line-height:1.65; margin-bottom:1.75rem; }

    .alert {
      display:flex; align-items:center; gap:10px;
      padding:11px 14px; border-radius:8px;
      font-size:13px; font-weight:600;
      margin-bottom:1.25rem; text-align:left;
      animation:fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }
    .alert-error   { background:var(--error-bg);   color:var(--error-txt);   border:1px solid var(--error-bdr); }
    .alert-success { background:var(--success-bg); color:var(--success-txt); border:1px solid var(--success-bdr); }
    .alert svg { flex-shrink:0; width:16px; height:16px; }

    /* ── Step labels ── */
    .steps {
      display:flex; align-items:center; gap:0;
      margin-bottom:1.75rem; font-size:12px; font-weight:600;
    }
    .step { display:flex; align-items:center; gap:6px; color:var(--muted); }
    .step.active { color:var(--main); }
    .step.done   { color:var(--success-txt); }
    .step-num {
      width:22px; height:22px; border-radius:50%;
      border:1.5px solid currentColor;
      display:flex; align-items:center; justify-content:center;
      font-size:11px; flex-shrink:0;
    }
    .step.done .step-num { background:var(--success-txt); border-color:var(--success-txt); color:#fff; }
    .step.active .step-num { background:var(--main); border-color:var(--main); color:#fff; }
    .step-line { flex:1; height:1px; background:var(--border); margin:0 6px; }

    .otp-label { font-size:13px; font-weight:600; color:var(--muted); text-align:left; margin-bottom:10px; }

    .otp-boxes { display:flex; gap:10px; justify-content:center; margin-bottom:6px; }
    .otp-box {
      width:58px; height:64px;
      border-radius:8px; border:1.5px solid var(--border);
      background:var(--bg-alt);
      font-size:24px; font-weight:700; color:var(--text); text-align:center;
      transition:border-color 0.2s,box-shadow 0.2s,transform 0.15s,background 0.2s;
      outline:none; font-family:'Montserrat',sans-serif;
    }
    .otp-box:focus { border-color:var(--main); box-shadow:0 0 0 3px var(--main-ring); background:var(--white); transform:scale(1.06) translateY(-2px); }
    .otp-box.filled { border-color:var(--main); background:var(--white); }
    .otp-box.error  { border-color:#e24b4a; background:var(--error-bg); box-shadow:0 0 0 3px rgba(226,75,74,0.12); }
    .otp-box.shake  { animation:shake 0.4s ease; }
    @keyframes shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-6px)} 40%{transform:translateX(6px)} 60%{transform:translateX(-4px)} 80%{transform:translateX(4px)} }

    .progress-wrap { height:3px; background:var(--bg-alt); border-radius:99px; overflow:hidden; margin:10px 0 1.5rem; }
    .progress-fill { height:100%; background:var(--main); border-radius:99px; width:0%; transition:width 0.25s ease; }

    /* ── Password inputs ── */
    .input-group { position:relative; margin-bottom:0.75rem; text-align:left; }
    .input-group input {
      width:100%; padding:14px 44px 14px 16px;
      background:var(--bg-alt); border:1.5px solid var(--border);
      border-radius:8px; font-size:14px; font-weight:500;
      color:var(--text); outline:none;
      font-family:'Montserrat',sans-serif;
      transition:border-color 0.2s,box-shadow 0.2s,background 0.2s;
    }
    .input-group input:focus { border-color:var(--main); box-shadow:0 0 0 3px var(--main-ring); background:var(--white); }
    .input-group input::placeholder { color:var(--muted); font-weight:500; }
    .toggle-pw {
      position:absolute; right:14px; top:50%; transform:translateY(-50%);
      background:none; border:none; cursor:pointer;
      color:var(--muted); font-size:15px; line-height:1;
      transition:color 0.2s; padding:0;
    }
    .toggle-pw:hover { color:var(--main); }

    /* Password strength */
    .strength-row { display:flex; gap:5px; margin-bottom:0.5rem; }
    .strength-seg { flex:1; height:3px; border-radius:99px; background:var(--bg-alt); transition:background 0.3s; }
    .strength-label { font-size:11px; font-weight:600; text-align:left; margin-bottom:1rem; color:var(--muted); min-height:14px; }

    /* Match indicator */
    .match-row { display:flex; align-items:center; gap:6px; font-size:12px; font-weight:600; margin-bottom:1.25rem; text-align:left; min-height:18px; }
    .match-icon { width:14px; height:14px; border-radius:50%; flex-shrink:0; }

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

    .link-row { font-size:13px; color:var(--muted); }
    .link-row a { color:var(--main); text-decoration:none; font-weight:600; }
    .link-row a:hover { color:var(--main-hover); text-decoration:underline; }
  </style>
</head>
<body>

<div class="card">

  <div class="icon-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke="#8D0B41" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="11" width="18" height="11" rx="2"/>
      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      <circle cx="12" cy="16" r="1" fill="#8D0B41"/>
    </svg>
  </div>

  <h1>Reset password</h1>
  <p class="subtitle">Enter the OTP sent to your email, then choose a new password.</p>

  <!-- Progress steps -->
  <div class="steps">
    <div class="step active" id="step1">
      <div class="step-num">1</div>
      <span>Enter OTP</span>
    </div>
    <div class="step-line"></div>
    <div class="step" id="step2">
      <div class="step-num">2</div>
      <span>New password</span>
    </div>
    <div class="step-line"></div>
    <div class="step" id="step3">
      <div class="step-num">3</div>
      <span>Done</span>
    </div>
  </div>

  <?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageType ?>">
    <?php if ($messageType === 'error'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9,12 11,14 15,10"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($message) ?>
    <?php if ($messageType === 'success'): ?>
      &nbsp;<a href="login.php" style="color:var(--success-txt);text-decoration:underline;">Go to login →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="reset-form">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <input type="hidden" name="otp" id="otp-hidden">

    <!-- OTP boxes -->
    <p class="otp-label">Verification code</p>
    <div class="otp-boxes" id="otp-boxes">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <input class="otp-box" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" <?= $i === 0 ? 'autofocus' : '' ?>>
      <?php endfor; ?>
    </div>
    <div class="progress-wrap"><div class="progress-fill" id="otp-progress"></div></div>

    <!-- New password -->
    <div class="input-group">
      <input type="password" id="pw" name="password" placeholder="New password" required>
      <button type="button" class="toggle-pw" onclick="togglePw('pw', this)" tabindex="-1">&#128065;</button>
    </div>

    <div class="strength-row">
      <div class="strength-seg" id="seg0"></div>
      <div class="strength-seg" id="seg1"></div>
      <div class="strength-seg" id="seg2"></div>
      <div class="strength-seg" id="seg3"></div>
    </div>
    <div class="strength-label" id="strength-label"></div>

    <!-- Confirm password -->
    <div class="input-group">
      <input type="password" id="pw2" name="confirm_password" placeholder="Confirm new password" required>
      <button type="button" class="toggle-pw" onclick="togglePw('pw2', this)" tabindex="-1">&#128065;</button>
    </div>
    <div class="match-row" id="match-row"></div>

    <button type="submit" class="btn" id="submit-btn" disabled>Reset password</button>
  </form>

  <div class="link-row"><a href="login.php">← Back to login</a></div>

</div>

<script>
  const boxes     = document.querySelectorAll('.otp-box');
  const hidden    = document.getElementById('otp-hidden');
  const otpProg   = document.getElementById('otp-progress');
  const submitBtn = document.getElementById('submit-btn');
  const pwField   = document.getElementById('pw');
  const pw2Field  = document.getElementById('pw2');

  let otpFull = false, pwStrong = false, pwMatch = false;

  // ── OTP box logic ──
  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(-1);
      box.classList.toggle('filled', !!box.value);
      if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
      updateOtp();
    });
    box.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !box.value && i > 0) {
        boxes[i - 1].value = ''; boxes[i - 1].classList.remove('filled'); boxes[i - 1].focus(); updateOtp();
      }
    });
    box.addEventListener('paste', e => {
      e.preventDefault();
      const txt = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      boxes.forEach((b, j) => { b.value = txt[j] || ''; b.classList.toggle('filled', !!b.value); });
      const last = Math.min(txt.length, boxes.length) - 1;
      if (last >= 0) boxes[last].focus();
      updateOtp();
    });
  });

  function updateOtp() {
    const filled = [...boxes].filter(b => b.value).length;
    otpProg.style.width = (filled / boxes.length * 100) + '%';
    hidden.value = [...boxes].map(b => b.value).join('');
    otpFull = filled === boxes.length;
    if (otpFull) {
      document.getElementById('step1').classList.remove('active');
      document.getElementById('step1').classList.add('done');
      document.getElementById('step1').querySelector('.step-num').textContent = '✓';
      document.getElementById('step2').classList.add('active');
    }
    checkReady();
  }

  // ── Password strength ──
  const segs   = [0,1,2,3].map(i => document.getElementById('seg' + i));
  const slabel = document.getElementById('strength-label');
  const colors = ['#e24b4a', '#ba7517', '#639922', '#1d9e75'];
  const labels = ['Weak', 'Fair', 'Good', 'Strong'];

  pwField.addEventListener('input', () => {
    const v = pwField.value;
    let score = 0;
    if (v.length >= 8)         score++;
    if (/[A-Z]/.test(v))       score++;
    if (/[0-9]/.test(v))       score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    segs.forEach((s, i) => s.style.background = i < score ? colors[score - 1] : 'var(--bg-alt)');
    slabel.textContent = v.length ? labels[score - 1] || '' : '';
    slabel.style.color = v.length ? colors[score - 1] : 'var(--muted)';
    pwStrong = score >= 3;
    checkMatch();
    checkReady();
  });

  // ── Confirm password match ──
  pw2Field.addEventListener('input', () => { checkMatch(); checkReady(); });

  function checkMatch() {
    const row = document.getElementById('match-row');
    if (!pw2Field.value) { row.innerHTML = ''; pwMatch = false; return; }
    if (pwField.value === pw2Field.value) {
      row.innerHTML = '<div class="match-icon" style="background:#3b6d11"></div><span style="color:#3b6d11">Passwords match</span>';
      pwMatch = true;
    } else {
      row.innerHTML = '<div class="match-icon" style="background:#a32d2d"></div><span style="color:#a32d2d">Passwords do not match</span>';
      pwMatch = false;
    }
  }

  function checkReady() {
    submitBtn.disabled = !(otpFull && pwStrong && pwMatch);
    if (otpFull && pwStrong) {
      document.getElementById('step2').classList.remove('active');
      document.getElementById('step2').classList.add('done');
      document.getElementById('step2').querySelector('.step-num').textContent = '✓';
      document.getElementById('step3').classList.add('active');
    }
  }

  function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.style.color = inp.type === 'text' ? 'var(--main)' : 'var(--muted)';
  }

  <?php if ($messageType === 'error'): ?>
  boxes.forEach(b => { b.classList.add('error','shake'); setTimeout(() => b.classList.remove('shake'), 500); });
  <?php endif; ?>
</script>

</body>
</html>