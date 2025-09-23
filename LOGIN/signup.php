<?php
require_once '../connection.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data safely
    $role        = $_POST['role'] ?? '';
    $username    = trim($_POST['username'] ?? '');
    $first_name  = trim($_POST['firstName'] ?? '');
    $last_name   = trim($_POST['lastName'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phoneNum    = trim($_POST['phoneNum'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? ''; // ✅ fixed name

    // --- VALIDATION ---
    if (
        empty($role) || empty($username) || empty($first_name) || empty($last_name) ||
        empty($email) || empty($phoneNum) || empty($password) || empty($confirm)
    ) {
        $message = "❌ All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format!";
    } elseif ($password !== $confirm) {
        $message = "❌ Passwords do not match!";
    } else {
        // Hash password
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

        if ($role === "Tenant") {
            $stmt = $conn->prepare("INSERT INTO tenanttbl (username, firstName, lastName, email, phoneNum, password) VALUES (?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = $conn->prepare("INSERT INTO landlordtbl (username, firstName, lastName, email, phoneNum, password) VALUES (?, ?, ?, ?, ?, ?)");
        }

        if ($stmt) {
            $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $phoneNum, $hashed_pass);

            try {
                if ($stmt->execute()) {
                    header("Location: login.php?registered=1");
                    exit();
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $message = "❌ Email or Username already exists!";
                } else {
                    $message = "❌ Error: " . $e->getMessage();
                }
            }

            $stmt->close();
        }
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
    <title>Sign up</title>
</head>

<body>
    <!-- SIGN UP FORM -->
    <div class="container m-auto">
        <div class="d-flex align-items-center justify-content-center min-vh-100">
            <div class="signup-container">
                <h1 class="text-center">Sign up</h1>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Sign up As</label>
                                    <select name="role" class="form-control" required>
                                        <option value="" disabled selected>Select Role</option>
                                        <option value="Landlord">Landlord</option>
                                        <option value="Tenant">Tenant</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label class="form-label">User Name</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="firstName" class="form-control" required>
                                </div>
                                <div class="col">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="lastName" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" name="phoneNum" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" required>
                                        <label class="form-check-label">
                                            I agree with <a href="#">Terms and Condition</a>
                                        </label>
                                    </div>
                                </div>
                                <div class="col">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-0">
                                <button type="submit" class="main-button mx-2">Sign Up</button>
                                <button type="button" class="main-button" onclick="location.href='login.php'">Cancel</button>
                            </div>
                        </form>

                        <!-- Show PHP Message -->
                        <div class="mt-3 text-center">
                            <?php if (!empty($message)) echo "<p>$message</p>"; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>