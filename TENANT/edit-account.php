<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = ($_SESSION['user_type'] === 'tenant') ? $_SESSION['user_id'] : null;

if (!$tenant_id) {
    header("Location: ../LOGIN/login.php");
    exit;
}

// edit acc for tenant
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $username = trim($_POST['username']);
    $middleName = trim($_POST['middleName']);
    $email = trim($_POST['email']);
    $phoneNum = trim($_POST['phoneNum']);
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];

    // file uploads
    $upload_dir = __DIR__ . "/../uploads/";
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $profilePic = "";

    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] == 0) {
        $profilePic = time() . "_profile_" . basename($_FILES['profilePic']['name']);
        $full_path = $upload_dir . $profilePic;
        
        $uploaded = move_uploaded_file($_FILES['profilePic']['tmp_name'], $full_path);
        
        if (!$uploaded) {
            $error_message = "Failed to upload profile picture!";
        }
    }

    // Update database
    if ($profilePic != "") {
        $sql = "UPDATE tenanttbl SET 
                firstName = ?,
                lastName = ?,
                middleName = ?,
                email = ?,
                phoneNum = ?,
                birthday = ?,
                gender = ?,
                username = ?,
                profilePic = ?
                WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $firstName, $lastName, $middleName, $email, $phoneNum, $birthday, $gender, $username, $profilePic, $tenant_id);
    } else {
        $sql = "UPDATE tenanttbl SET 
                firstName = ?,
                lastName = ?,
                middleName = ?,
                email = ?,
                phoneNum = ?,
                birthday = ?,
                gender = ?,
                username = ?
                WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $firstName, $lastName, $middleName, $email, $phoneNum, $birthday, $gender, $username, $tenant_id);
    }

    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = trim($firstName . ' ' . $lastName);
        
        $_SESSION['success_message'] = "Account updated successfully!";
        header("Location: account.php");
        exit;
    } else {
        $error_message = "Error updating account: " . $stmt->error;
    }
    $stmt->close();
}

// get tenant data
$stmt = $conn->prepare("SELECT * FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>EDIT ACCOUNT</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        .edit {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px var(--shadow-color);
        }

        .profile-img {
            width: 120px;
            height: 120px;
            background-color: #d9d9d9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border: 2px solid var(--main-color);
        }

        .profile-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .upload-input {
            display: none;
        }
    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="landlord-page">
        <div class="container m-auto">
            <h2 class="mb-4">EDIT ACCOUNT</h2>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    
                    <div class="row gy-4 justify-content-center edit">
                        <div class="col-lg-8">
                            <form class="mt-4" method="POST" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col d-flex justify-content-center align-items-center">
                                        <label class="profile-img" for="upload">
                                            <span id="profile-text"
                                                style="<?= !empty($user['profilePic']) ? 'display: none;' : '' ?>">add
                                                img</span>
                                            <img id="preview"
                                                src="<?= !empty($user['profilePic']) ? '../uploads/' . htmlspecialchars($user['profilePic']) : '' ?>"
                                                alt=""
                                                style="<?= !empty($user['profilePic']) ? 'display: block;' : 'display: none;' ?>">
                                            <input type="file" id="upload" name="profilePic" class="upload-input"
                                                accept="image/*">
                                        </label>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="firstName" class="form-control"
                                            value="<?= htmlspecialchars($user['firstName'] ?? '') ?>" required>

                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="lastName" class="form-control"
                                            value="<?= htmlspecialchars($user['lastName'] ?? '') ?>" required>

                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middleName" class="form-control"
                                            value="<?= htmlspecialchars($user['middleName'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                    </div>

                                    <div class="col">
                                        <label class="form-label">User Name</label>
                                        <input type="text" name="username" class="form-control"
                                            value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                                    </div>

                                </div>

                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="birthday" class="form-control"
                                            value="<?= htmlspecialchars($user['birthday'] ?? '') ?>">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-control" required>
                                            <option value="" disabled selected>Select your Gender</option>
                                            <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>♀
                                                Female</option>
                                            <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>♂ Male
                                            </option>
                                        </select>
                                    </div>


                                    <div class="col">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" name="phoneNum" class="form-control"
                                            value="<?= htmlspecialchars($user['phoneNum'] ?? '') ?>">
                                    </div>

                                    <div class="d-flex justify-content-start gap-2 mt-4">
                                        <button type="submit" class="main-button">Save</button>
                                        <button type="button" class="main-button"
                                            onclick="location.href='account.php'">Cancel</button>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const uploadInput = document.getElementById('upload');
            const preview = document.getElementById('preview');
            const profileText = document.getElementById('profile-text');

            uploadInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        profileText.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                }
            });
        </script>

        <script src="../js/script.js" defer></script>
        <script src="../js/bootstrap.bundle.min.js"></script>
        <script src="https://unpkg.com/scrollreveal"></script>
</body>

</html>