<?php
include '../includes/db.php'; // ✅ database connection
session_start();

$errorMsg = "";

//  login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // list of tables to check in tahanandb
    $tables = ["admintbl", "landlordtbl", "tenanttbl"];
    $found = false;

    foreach ($tables as $table) {
        $stmt = $conn->prepare("SELECT ID, password, firstName, lastName FROM $table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["ID"];
                $_SESSION["user_name"] = $row["firstName"] . " " . $row["lastName"];
                $_SESSION["user_type"] = $table;

                // redirect to either landlord or tenant if successful login
                header("Location: dashboard.php"); // insert the home page of either landlord or tenant
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
    <!-- Font Awsome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
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
                    <a href=""><i class="fa-brands fa-google"></i> Login with Google</a>
                    <a href=""><i class="fa-brands fa-facebook"></i> Login with Facebook</a>
                </div><br>
                <div class="signup-link">
                    Not a member? <a href="signup.php">Signup now</a>
                </div>
            </form>
        </div>

    </div>
</body>

</html>