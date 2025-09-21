<?php
include '../includes/db.php';
require_once __DIR__ . '/google-config.php';
session_start();

$message = "";

// ---------------- GOOGLE SIGNUP CALLBACK ----------------
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!isset($token["error"])) {
        $client->setAccessToken($token["access_token"]);
        $google_service = new Google_Service_Oauth2($client);
        $data = $google_service->userinfo->get();

        $email = $data['email'];
        $name = $data['name'];
        $nameParts = explode(" ", $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : "";

        // Check if email already exists
        $checkSql = "SELECT email FROM landlordtbl WHERE email=? UNION SELECT email FROM tenanttbl WHERE email=?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ss", $email, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // if registered redirect to login
            header("Location: login.php");
            exit;
        } else {
            // auto-create rows in databas
            $stmt = $conn->prepare("INSERT INTO tenanttbl (firstName, lastName, email, dateJoin) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $firstName, $lastName, $email);
            $stmt->execute();

            // Redirect to login
            header("Location: login.php");
            exit;
        }
    }
}
// --------------------------------------------------------


// ---------------- NORMAL FORM SIGNUP --------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email) || empty($password)) {
        $message = "❌ All fields are required.";
    } else {
        // check if email already exists
        $checkSql = "SELECT email FROM landlordtbl WHERE email=? UNION SELECT email FROM tenanttbl WHERE email=?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ss", $email, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "⚠️ Email already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if ($role === "landlord") {
                $sql = "INSERT INTO landlordtbl (firstName, email, password, status, dateJoin) 
                        VALUES (?, ?, ?, 'pending', NOW())";
            } else {
                $sql = "INSERT INTO tenanttbl (firstName, email, password, dateJoin) 
                        VALUES (?, ?, ?, NOW())";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            if ($stmt->execute()) {
                // Redirect landlord to login 
                header("Location: login.php");
                exit;
            } else {
                $message = "❌ Error: " . $stmt->error;
            }
        }
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
    <title>Signup Form</title>
</head>

<body>
    <!-- CONTAINER -->
    <div class="form-container">

        <!--  messages  -->
        <?php if (!empty($message)): ?>
            <p style="color:red; text-align:center; font-weight:bold;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <!--Landlord Form -->
        <div class="form-box landlord">
            <form method="POST" action="signup.php">
                <h1>Sign Up as Landlord</h1>
                <input type="hidden" name="role" value="landlord">

                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="terms">
                    <input type="checkbox" required> I agree with <a href="">Terms and Condition</a>
                </div>
                <button type="submit" class="btn signup">Sign Up</button>
                <div class="socials">
                    <p>or</p><br>
                    <a href="<?php echo $client->createAuthUrl(); ?>"><i class="fa-brands fa-google"></i> Signup with
                        Google</a>
                </div>
            </form>
        </div>

        <!--Tenant Form-->
        <div class="form-box tenant">
            <form method="POST" action="signup.php">
                <h1>Sign Up as Tenant</h1>
                <input type="hidden" name="role" value="tenant">

                <div class="input-box">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa-solid fa-key"></i>
                </div>
                <div class="terms">
                    <input type="checkbox" required> I agree with <a href="">Terms and Condition</a>
                </div>
                <button type="submit" class="btn signup">Sign Up</button><br><br>
                <div class="socials">
                    <p>or</p><br>
                    <a href="<?php echo $client->createAuthUrl(); ?>"><i class="fa-brands fa-google"></i> Signup with
                        Google</a>
                </div>
            </form>
        </div>


        <!-- Toggle Box -->
        <div class="toggle-box">

            <!-- Toggle Panel Left -->
            <div class="toggle-panel left">
                <h1>Hello, Welcome</h1>
                <h1>to TAHANAN</h1>
                <button class="btn tenant-btn">Sign Up as Tenant</button><br>
                <p>Already have Account?</p>
                <a href="login.php"><button class="btn">Login </button></a>
            </div>

            <!-- Toggle Panel Right -->
            <div class="toggle-panel right">
                <h1>Hello, Welcome</h1>
                <h1>to TAHANAN</h1>
                <button class="btn landlord-btn">Sign Up as Landlord</button>
                <p>Already have Account?</p>
                <a href="login.php"><button class="btn">Login </button></a>
            </div>

        </div>

    </div>

</body>
<script src="script.js"></script>

</html>