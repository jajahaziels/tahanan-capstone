<?php
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("You must be logged in to edit your account.");
}

$user_id = $_SESSION['user_id'];

// database connection
$conn = new mysqli("localhost", "root", "", "tahanandb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// fetch current user data
$stmt = $conn->prepare("SELECT * FROM landlordtbl WHERE ID=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$upload_dir = "../uploads/";
if (!file_exists($upload_dir))
    mkdir($upload_dir, 0777, true);

// File upload function
function uploadFile($file, $prefix, $dir)
{
    if (isset($file) && $file['error'] === 0 && !empty($file['name'])) {
        $filename = time() . "_{$prefix}_" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            return $filename;
        }
    }
    return null;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstName = $_POST['firstName'] ?? $user['firstName'];
    $lastName = $_POST['lastName'] ?? $user['lastName'];
    $middleName = $_POST['middleName'] ?? $user['middleName'];
    $street = $_POST['street'] ?? $user['street'];
    $barangay = $_POST['barangay'] ?? $user['barangay'];
    $city = $_POST['city'] ?? $user['city'];
    $province = $_POST['province'] ?? $user['province'];
    $zipCode = $_POST['zipCode'] ?? $user['zipCode'];
    $phoneNum = $_POST['phoneNum'] ?? $user['phoneNum'];
    $email = $_POST['email'] ?? $user['email'];
    $birthday = $_POST['birthday'] ?? $user['birthday'];
    $gender = $_POST['gender'] ?? $user['gender'];
    $username = $_POST['userName'] ?? $user['username'];

    // File uploads
    $profilePic = uploadFile($_FILES['profilePic'] ?? null, 'profile', $upload_dir) ?? $user['profilePic'];
    $verificationId = uploadFile($_FILES['verificationId'] ?? null, 'govid', $upload_dir) ?? $user['verificationId'];
    $ID_image = uploadFile($_FILES['ID_image'] ?? null, 'selfie', $upload_dir) ?? $user['ID_image'];

    // Update query using ID
    $stmt = $conn->prepare("UPDATE landlordtbl SET 
        firstName=?, lastName=?, middleName=?, username=?, street=?, barangay=?, city=?, province=?, zipCode=?, phoneNum=?, email=?, birthday=?, gender=?, profilePic=?, verificationId=?, ID_image=? 
        WHERE ID=?");

    $stmt->bind_param(
        "ssssssssssssssssi",
        $firstName,
        $lastName,
        $middleName,
        $username,
        $street,
        $barangay,
        $city,
        $province,
        $zipCode,
        $phoneNum,
        $email,
        $birthday,
        $gender,
        $profilePic,
        $verificationId,
        $ID_image,
        $user_id
    );


    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Account updated successfully!";
        header("Location: account.php");
        exit;
    } else {
        $error_message = "Error updating account: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
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
            border: 6px solid var(--main-color);
            padding: 40px;
            border-radius: 20px;
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
    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page">
        <div class="container m-auto">
            <h2 class="mb-4">EDIT ACCOUNT</h2>

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
                                <label class="form-label">Firstname</label>
                                <input type="text" name="firstName" class="form-control"
                                    value="<?= htmlspecialchars($user['firstName'] ?? '') ?>" required>

                                <label class="form-label">Lastname</label>
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
                                <input type="text" name="userName" class="form-control"
                                    value="<?= htmlspecialchars($user['userName'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Street</label>
                                <input type="text" name="street" class="form-control"
                                    value="<?= htmlspecialchars($user['street'] ?? '') ?>">
                            </div>
                            <div class="col">
                                <label class="form-label">Barangay</label>
                                <input type="text" name="barangay" class="form-control"
                                    value="<?= htmlspecialchars($user['barangay'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control"
                                    value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                            </div>
                            <div class="col">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" class="form-control"
                                    value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zipCode" class="form-control"
                                    value="<?= htmlspecialchars($user['zipCode'] ?? '') ?>">
                            </div>
                            <div class="col">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="phoneNum" class="form-control"
                                    value="<?= htmlspecialchars($user['phoneNum'] ?? '') ?>">
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
                        </div>

                        <h4>Identity Verification</h4>
                        <div class="mb-3">
                            <label class="form-label">Upload Valid Government ID</label>
                            <input type="file" class="form-control" name="govID" accept="image/*,.pdf">
                            <?php if (!empty($user['verificationId'])): ?>
                                <small class="text-muted">Current: <?= htmlspecialchars($user['verificationId']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Upload Selfie Holding the ID</label>
                            <input type="file" class="form-control" name="selfieID" accept="image/*,.pdf">
                            <?php if (!empty($user['ID_image'])): ?>
                                <small class="text-muted">Current: <?= htmlspecialchars($user['ID_image']) ?></small>
                            <?php endif; ?>
                        </div>

                        <h5 class="mt-4">Property Ownership or Authorization</h5>
                        <div class="mb-3">
                            <label class="form-label">Upload Proof of Ownership</label>
                            <input type="file" class="form-control" name="govID" accept="image/*,.pdf">
                            <?php if (!empty($user['verificationId'])): ?>
                                <small class="text-muted">Current: <?= htmlspecialchars($user['verificationId']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Authorization Letter + Owner’s ID copy</label>
                            <input type="file" class="form-control" name="selfieID" accept="image/*,.pdf" multiple>
                            <?php if (!empty($user['ID_image'])): ?>
                                <small class="text-muted">Current: <?= htmlspecialchars($user['ID_image']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-start gap-2 mt-4">
                            <button type="submit" class="main-button">Save</button>
                            <button type="button" class="main-button"
                                onclick="location.href='account.php'">Cancel</button>
                        </div>
                    </form>
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