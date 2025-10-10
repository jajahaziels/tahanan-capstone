<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $error = "";

    // First, check admins (login with email)
    $stmt = $conn->prepare("SELECT * FROM admintbl WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_type'] = "admin";
            $_SESSION['admin_id']  = $row['ID'];
            $_SESSION['email']     = $row['email'];
            $_SESSION['firstName'] = $row['firstName'];
            $_SESSION['lastName']  = $row['lastName'];

            header("Location: ../ADMIN/admin.php");
            exit;
        }
    }

    // Second, check landlords
    $stmt = $conn->prepare("SELECT * FROM landlordtbl WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_type']   = "landlord";
            $_SESSION['landlord_id'] = $row['ID'];
            $_SESSION['username']    = $row['username'];

            header("Location: ../LANDLORD/landlord-properties.php");
            exit;
        }
    }

    // Third, check tenants
    $stmt = $conn->prepare("SELECT * FROM tenanttbl WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_type']  = "tenant";
            $_SESSION['tenant_id']  = $row['ID'];
            $_SESSION['username']   = $row['username'];

            header("Location: ../TENANT/tenant.php");
            exit;
        }
    }

    // If no match found
    $error = "Invalid username or password.";
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
                        <label class="form-label">User Name or Email</label>
                        <input type="text" name="username" class="form-control" required>
                        <small class="text-muted">Admins: use your email address</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <a href="#">Forgot Password?</a> <br>
                        <button type="submit" class="login-button mt-3">Log In</button>
                        <p class="mt-2">Are you new? <a href="signup.php">Create an Account</a></p><br>
                    </div>
                </form>

                <!-- Show Error Message -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


</body>

</html>