<?php
include '../includes/db.php'; // database connection
session_start();

require_once __DIR__ . '/google-config.php'; 

$errorMsg = "";

// ---------------- GOOGLE LOGIN CALLBACK ----------------
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token["error"])) {
        $client->setAccessToken($token["access_token"]);

        $google_service = new Google_Service_Oauth2($client);
        $data = $google_service->userinfo->get();

        $email = $data['email'];
        $name = $data['name'];

        // Check if user exists in DB
        $tables = [
            "admintbl" => "homepage.html", // to change to php 
            "landlordtbl" => "landlord.html", // to change to php
            "tenanttbl" => "tenant-rental.html" // to change to php
        ];

        $found = false;

        foreach ($tables as $table => $redirect) {
            $stmt = $conn->prepare("SELECT ID, firstName, lastName FROM $table WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $_SESSION["user_id"] = $row["ID"];
                $_SESSION["user_name"] = $row["firstName"] . " " . $row["lastName"];
                $_SESSION["user_type"] = $table;
                $found = true;

                header("Location: " . $redirect);
                exit;
            }
        }

        // if no account found 
        if (!$found) {
            $errorMsg = "No account found for <b>$email</b>. Please sign up first.";
        }
    }
}
// -------------------------------------------------------


// ---------------- LOGIN -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $tables = [
        "admintbl" => "dashboard.php",
        "landlordtbl" => "landlord_home.php",
        "tenanttbl" => "tenant_home.php"
    ];
    $found = false;

    foreach ($tables as $table => $redirect) {
        $stmt = $conn->prepare("SELECT ID, password, firstName, lastName FROM $table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["ID"];
                $_SESSION["user_name"] = $row["firstName"] . " " . $row["lastName"];
                $_SESSION["user_type"] = $table;

                header("Location: " . $redirect);
                exit;
            } else {
                $errorMsg = "Incorrect password. Please try again.";
                $found = true;
                break;
            }
        }
        $stmt->close();
    }

    if (!$found && $errorMsg == "") {
        $errorMsg = "No account found with this email.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Main CSS -->
    <link rel="stylesheet" href="signup.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inknut+Antiqua:wght@300;400;500;600;700;800;900&family=Quicksand:wght@300..700&display=swap"
        rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
</head>

<body>
    <div class="form-container login-container">
        <!--Login Form -->
        <div class="form-box login-form">
            <form method="POST" action="">
                <h1>Login Form</h1>

                <?php if (!empty($errorMsg)): ?>
                    <p style="color: red; font-size: 14px;"><?php echo $errorMsg; ?></p>
                <?php endif; ?>

                <div class="input-box">
                    <input type="text" name="email" placeholder="Email" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox"> Remember me</label>
                    <a href="#">Forgot Password?</a>
                </div><br>
                <button type="submit" class="btn login">Login</button>
                <div class="socials"><br>
                    <p>or</p><br>
                    <a href="<?php echo $client->createAuthUrl(); ?>">
                        <i class="fa-brands fa-google"></i> Login with Google
                    </a>
                </div><br>
                <div class="signup-link">
                    Not a member? <a href="signup.php">Signup now</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>