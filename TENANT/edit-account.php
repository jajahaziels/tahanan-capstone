<?php
require_once '../connection.php';
include '../session_auth.php';

$tenant_id = ($_SESSION['user_type'] === 'tenant') ? $_SESSION['user_id'] : null;

if (!$tenant_id) {
    header("Location: ../LOGIN/login.php");
    exit;
}

// Upload directory
$upload_dir = __DIR__ . "/../uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// GET USER DATA first
$stmt = $conn->prepare("SELECT * FROM tenanttbl WHERE ID = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// REMOVE PHOTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['removePhoto']) && $_POST['removePhoto'] == '1') {
    if (!empty($user['profilePic']) && file_exists($upload_dir . $user['profilePic'])) {
        unlink($upload_dir . $user['profilePic']);
    }

    // Remove from DB
    $stmt = $conn->prepare("UPDATE tenanttbl SET profilePic = NULL WHERE ID = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Profile photo removed successfully!";
    header("Location: account.php");
    exit;
}

// EDIT ACCOUNT
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['removePhoto'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $middleName = trim($_POST['middleName']);
    $email = trim($_POST['email']);
    $phoneNum = trim($_POST['phoneNum']);
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];
    $username = trim($_POST['username']);

    $profilePic = $user['profilePic']; // default: keep old

    // Handle new uploaded photo
    if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] == 0) {
        // Delete old photo if exists
        if (!empty($user['profilePic']) && file_exists($upload_dir . $user['profilePic'])) {
            unlink($upload_dir . $user['profilePic']);
        }

        // Save new photo
        $profilePic = time() . "_profile_" . basename($_FILES['profilePic']['name']);
        move_uploaded_file($_FILES['profilePic']['tmp_name'], $upload_dir . $profilePic);
    }

    // Prepare SQL
    $sql = "UPDATE tenanttbl SET 
                firstName = ?, lastName = ?, middleName = ?, 
                email = ?, phoneNum = ?, birthday = ?, 
                gender = ?, username = ?, profilePic = ?
            WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssi",
        $firstName,
        $lastName,
        $middleName,
        $email,
        $phoneNum,
        $birthday,
        $gender,
        $username,
        $profilePic,
        $tenant_id
    );

    if ($stmt->execute()) {
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

    <title>Edit Account</title>
    <style>
        :root {
            --main-color: #8d0b41;
            --bg-color: #fff;
            --shadow-color: rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
        }

        .landlord-page {
            margin-top: 140px !important;
        }

        .edit {
            background-color: var(--bg-color);
            padding: 30px 25px;
            border-radius: 20px;
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .edit:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }

        h2 {
            font-weight: 600;
            color: var(--main-color);
            margin-bottom: 25px;
        }

        .profile-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-img {
            width: 140px;
            height: 140px;
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
            transition: all 0.3s ease;
        }

        .profile-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.3);
            opacity: 0;
            transition: 0.3s;
        }

        .profile-img:hover .profile-overlay {
            opacity: 1;
        }

        .profile-overlay i {
            color: #fff;
            font-size: 24px;
        }

        .upload-input {
            display: none;
        }

        input.form-control,
        select.form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ccc;
            background-color: #fafafa;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input.form-control:focus,
        select.form-control:focus {
            border-color: var(--main-color);
            box-shadow: 0 4px 12px rgba(141, 11, 65, 0.15);
            outline: none;
        }

        label.form-label {
            font-weight: 500;
            margin-bottom: 6px;
        }

        .main-button {
            background: linear-gradient(135deg, #8d0b41, #a3154f);
            color: #fff;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(141, 11, 65, 0.25);
        }

        .main-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(141, 11, 65, 0.35);
        }

        .main-button:active {
            transform: scale(0.97);
        }

        .btn-remove-modern {
            background: linear-gradient(135deg, #8d0b41, #a3154f);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(141, 11, 65, 0.35);
        }

        @media (max-width: 768px) {
            .edit {
                padding: 20px;
            }

            .profile-img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="landlord-page">
        <div class="container m-auto">
            <h2>Edit Account</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <div class="edit">
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-container">
                        <label class="profile-img" for="upload">
                            <img id="preview"
                                src="<?= !empty($user['profilePic']) ? '../uploads/' . htmlspecialchars($user['profilePic']) : '' ?>"
                                style="<?= !empty($user['profilePic']) ? 'display:block;' : 'display:none;' ?>">
                            <div class="profile-overlay"><i class="fa fa-camera"></i></div>
                            <input type="file" id="upload" name="profilePic" class="upload-input" accept="image/*">
                        </label>

                        <?php if (!empty($user['profilePic'])): ?>
                            <button type="button" id="remove-photo" class="btn-remove-modern mt-3">
                                <i class="fa fa-trash"></i> Remove 
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name</label>
                            <input type="text" name="firstName" class="form-control"
                                value="<?= htmlspecialchars($user['firstName'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastName" class="form-control"
                                value="<?= htmlspecialchars($user['lastName'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middleName" class="form-control"
                                value="<?= htmlspecialchars($user['middleName'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control"
                                value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Date of Birth</label>
                                    <?php
                                        // Calculate max allowed DOB: today - 19 years
                                        $today = new DateTime();
                                        $today->modify('-19 years');
                                        $maxDOB = $today->format('Y-m-d');
                                    ?>
                                        <input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($user['birthday'] ?? '') ?>"
                                            max="<?= $maxDOB ?>" required>
                                        <small class="text-muted">You must be at least 19 years old.</small>
                                    </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="" disabled selected>Select your Gender</option>
                                <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>♀
                                    Female</option>
                                <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>♂ Male
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="phoneNum" class="form-control"
                                value="<?= htmlspecialchars($user['phoneNum'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-start gap-3 mt-4">
                        <button type="submit" class="main-button">Save</button>
                        <button type="button" class="main-button" onclick="location.href='account.php'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const uploadInput = document.getElementById('upload');
        const preview = document.getElementById('preview');
        const removeBtn = document.getElementById('remove-photo');

        if (uploadInput) {
            uploadInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        if (removeBtn) removeBtn.style.display = 'inline-flex';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                preview.src = '';
                preview.style.display = 'none';
                removeBtn.style.display = 'none';
                uploadInput.value = '';

                if (!document.getElementById('removePhotoInput')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'removePhoto';
                    input.id = 'removePhotoInput';
                    input.value = '1';
                    document.querySelector('form').appendChild(input);
                }
            });
        }
    </script>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>

</html>