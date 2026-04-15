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

    /* ===== ENHANCED ANIMATIONS (ADDED ONLY) ===== */
    
    /* Smooth fade-in animation for the whole auth card */
    .auth-card {
      animation: cardFadeIn 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    @keyframes cardFadeIn {
      0% {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .field {
      opacity: 0;
      animation: slideUpFade 0.4s ease forwards;
    }

    .field:nth-of-type(1) {
      animation-delay: 0.1s;
    }
    .field:nth-of-type(2) {
      animation-delay: 0.2s;
    }

    @keyframes slideUpFade {
      0% {
        opacity: 0;
        transform: translateY(12px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .remember-forgot {
      opacity: 0;
      animation: fadeInItem 0.4s ease forwards;
      animation-delay: 0.3s;
    }

    @keyframes fadeInItem {
      0% {
        opacity: 0;
        transform: translateY(8px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .btn-primary {
      opacity: 0;
      animation: fadeInItem 0.4s ease forwards;
      animation-delay: 0.4s;
      transition: transform 0.25s ease, background 0.2s, box-shadow 0.2s;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(141, 11, 65, 0.25);
    }

    .btn-primary:active {
      transform: translateY(1px);
    }

    .divider {
      opacity: 0;
      animation: fadeInItem 0.4s ease forwards;
      animation-delay: 0.45s;
    }

    .socials {
      opacity: 0;
      animation: fadeInItem 0.4s ease forwards;
      animation-delay: 0.5s;
    }

    .brand-identity {
      transition: transform 0.3s ease;
      animation: gentleScale 0.5s ease-out;
    }

    @keyframes gentleScale {
      0% {
        opacity: 0;
        transform: scale(0.92);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }

    .field input:focus {
      animation: subtlePulse 0.3s ease-out;
    }

    @keyframes subtlePulse {
      0% {
        box-shadow: 0 0 0 0 rgba(141, 11, 65, 0.2);
      }
      100% {
        box-shadow: 0 0 0 3px rgba(141, 11, 65, 0.12);
      }
    }

    .btn-google {
      transition: all 0.25s ease;
    }

    .btn-google:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .forgot-link {
      transition: all 0.2s ease;
    }

    .forgot-link:hover {
      transform: translateX(2px);
      letter-spacing: 0.3px;
    }

    .signup {
      position: relative;
      transition: color 0.2s;
    }

    .signup::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 0;
      background-color: #8D0B41;
      transition: width 0.25s ease;
    }

    .signup:hover::after {
      width: 100%;
    }

    .remember-forgot input[type="checkbox"] {
      transition: transform 0.15s ease, accent-color 0.2s;
    }

    .remember-forgot input[type="checkbox"]:active {
      transform: scale(0.92);
    }

    .hero-section {
      animation: heroSlideIn 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    @keyframes heroSlideIn {
      0% {
        opacity: 0;
        transform: translateX(-16px);
      }
      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .map-box img {
      transition: transform 0.4s ease;
      animation: imageReveal 0.7s ease-out;
    }

    @keyframes imageReveal {
      0% {
        opacity: 0;
        transform: scale(0.96);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }

    .hero-text h1 {
      animation: textGlide 0.5s ease-out;
    }

    @keyframes textGlide {
      0% {
        opacity: 0;
        transform: translateY(18px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .main-wrapper {
      animation: wrapperFade 0.4s ease;
    }

    @keyframes wrapperFade {
      0% { opacity: 0; }
      100% { opacity: 1; }
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