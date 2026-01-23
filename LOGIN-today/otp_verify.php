<?php
session_start();
include '../includes/db.php';

// redirect if OTP/email not set
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email']) || !isset($_SESSION['otp_expiry']) || !isset($_SESSION['user_type'])) {
    header("Location: signup.php");
    exit;
}

$message = "";

// check if OTP expired
if (time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['otp']);
    $message = "OTP expired. Please request a new one.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);

    if (!isset($_SESSION['otp'])) {
        $message = "OTP expired. Please request a new one.";
    } elseif ($enteredOtp == $_SESSION['otp']) {
        $email = $_SESSION['otp_email'];   // instead of $_SESSION['email']
        $role = $_SESSION['otp_role'];     // instead of $_SESSION['user_type']


        // mark account as active
        if ($role === "landlord") {
            $sql = "UPDATE landlordtbl SET status='active' WHERE email=?";
        } else {
            $sql = "UPDATE tenanttbl SET status='active' WHERE email=?";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // fetch user ID and name for session
        if ($role === "landlord") {
            $stmtUser = $conn->prepare("SELECT ID, firstName, lastName FROM landlordtbl WHERE email=? LIMIT 1");
        } else {
            $stmtUser = $conn->prepare("SELECT ID, firstName, lastName FROM tenanttbl WHERE email=? LIMIT 1");
        }
        $stmtUser->bind_param("s", $email);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        $user = $resUser->fetch_assoc();

        // set session
        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['username'] = $user['firstName'] . ' ' . $user['lastName'];
        $_SESSION['user_type'] = $role;

        // clear only OTP .session variables
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['email']);

        // redirect based on role
        if ($role === "landlord") {
            header("Location: /TAHANAN/LANDLORD/landlord-properties.php");
        } else {
            header("Location: /TAHANAN/TENANT/tenant.php");
        }
        exit();

    } else {
        $message = "❌ Invalid OTP. Try again.";
    }
}

// resend OTP
if (isset($_POST['resend'])) {
    $newOtp = rand(100000, 999999);
    $_SESSION['otp'] = $newOtp;
    $_SESSION['otp_expiry'] = time() + (10 * 60); // 10 minutes

    $to = $_SESSION['email'];
    $subject = "Your OTP Code";
    $body = "Your OTP code is: $newOtp. It will expire in 10 minutes.";
    mail($to, $subject, $body);

    $message = "A new OTP has been sent to your email.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <script>
        // countdown timer for 10 mins
        let expiryTime = <?php echo $_SESSION['otp_expiry'] - time(); ?>;
        function startCountdown() {
            let timer = document.getElementById("countdown");
            let interval = setInterval(() => {
                if (expiryTime <= 0) {
                    clearInterval(interval);
                    timer.innerHTML = "⏰ OTP expired. Please request a new one.";
                    document.getElementById("otpInput").disabled = true;
                    document.getElementById("verifyBtn").disabled = true;
                } else {
                    let minutes = Math.floor(expiryTime / 60);
                    let seconds = expiryTime % 60;
                    timer.innerHTML = `Expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;
                    expiryTime--;
                }
            }, 1000);
        }
        window.onload = startCountdown;
    </script>
</head>

<body>
    <h2>Enter OTP</h2>
    <?php if (!empty($message))
        echo "<p style='color:red;'>$message</p>"; ?>
    <p id="countdown" style="color:blue; font-weight:bold;"></p>

    <form method="POST">
        <input type="text" id="otpInput" name="otp" placeholder="Enter OTP" required>
        <button type="submit" id="verifyBtn">Verify</button>
    </form>

    <form method="POST">
        <button type="submit" name="resend">Resend OTP</button>
    </form>
</body>

</html>