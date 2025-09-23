<?php
session_start();
require_once '../connection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "❌ Both fields are required!";
    } else {
        // First check Tenant table
        $stmt = $conn->prepare("SELECT ID, username, password FROM tenanttbl WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        if (!$user) {
            // If not found, check Landlord table
            $stmt = $conn->prepare("SELECT ID, username, password FROM landlordtbl WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $role = "Landlord";
        } else {
            $role = "Tenant";
        }

        if ($user && password_verify($password, $user['password'])) {
            // Store session
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $role;

            // Redirect based on role
            if ($role === "Tenant") {
                header("Location: ../TENANT/tenant.php");
            } else {
                header("Location: ../LANDLORD/landlord.php");
            }
            exit();
        } else {
            $message = "❌ Invalid username or password!";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FAVICON -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <!-- FA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- BS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="signup.css?v=<?= time(); ?>">
    <title>Log in</title>
</head>

<body>
    <!-- LOGIN FORM -->
    <div class="d-flex align-items-center justify-content-center min-vh-100">
        <div class="login-container d-flex shadow rounded overflow-hidden">

            <div class="login-img"></div>

            <div class="login-form p-4 bg-white">
                <h2 class="text-center mb-4">Log in</h2>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">User Name</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <a href="#">Forgot Password?</a> <br>
                        <button type="submit" class="login-button mt-3">Log In</button>
                        <p class="mt-2">Are you new? <a href="signup.php">Create an Account</a></p><br>
                    </div>
                </form>

                <!-- Show PHP Message -->
                <div class="mt-3 text-center">
                    <?php if (!empty($message)) echo "<p class='text-danger'>$message</p>"; ?>
                </div>
            </div>
        </div>
    </div>


</body>

</html>