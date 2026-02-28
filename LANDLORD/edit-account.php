<?php
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("You must be logged in to edit your account.");
}

$user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "tahanandb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current user data
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

    // PROFILE PICTURE: handle removal first
    if (isset($_POST['removePhoto']) && $_POST['removePhoto'] == '1') {
        if (!empty($user['profilePic']) && file_exists($upload_dir . $user['profilePic'])) {
            unlink($upload_dir . $user['profilePic']);
        }
        $profilePic = null;
    } else {
        $profilePic = uploadFile($_FILES['profilePic'] ?? null, 'profile', $upload_dir) ?? $user['profilePic'];
    }

    // Other uploaded files
    $verificationId = uploadFile($_FILES['verificationId'] ?? null, 'govid', $upload_dir) ?? $user['verificationId'];
    $ID_image = uploadFile($_FILES['ID_image'] ?? null, 'selfie', $upload_dir) ?? $user['ID_image'];

    // Other fields
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

    // Update query
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
        /* PAGE & CONTAINER */
.landlord-page {
    margin-top: 140px !important;
    font-family: 'Inter', sans-serif;
    color: #333;
    background-color: #f9f9f9;
}

.edit {
    background-color: var(--bg-color);
    padding: 30px 25px;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease-in-out;
}

.edit:hover {
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

/* HEADINGS */
h2, h4, h5 {
    font-weight: 600;
    color: var(--main-color);
}

/* PROFILE IMAGE */
.profile-img {
    width: 140px;
    height: 140px;
    background-color: #d9d9d9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    cursor: pointer;
    overflow: hidden;
    border: 2px solid var(--main-color);
    transition: all 0.3s ease;
}

.profile-img:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(108, 99, 255, 0.15);
}

.profile-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* INPUT FIELDS */
input.form-control, select.form-control {
    border-radius: 10px;
    padding: 12px 15px;
    border: 1px solid #ccc;
    transition: all 0.3s ease;
    font-size: 14px;
    background-color: #fafafa;
}

input.form-control:focus, select.form-control:focus {
    border-color: var(--main-color);
    box-shadow: 0 4px 12px rgba(141, 11, 65, 0.15);
    outline: none;
}

.upload-input {
    opacity: 0;
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    cursor: pointer;
}

/* LABELS */
label.form-label {
    font-weight: 500;
    margin-bottom: 6px;
}

/* BUTTONS */
.main-button {
    background: linear-gradient(135deg, #8d0b41, #a3154f); 
    color: #fff;
    border: none;
    padding: 10px 25px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
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

/* REMOVE BUTTON */
.btn-remove-modern {
    background: linear-gradient(135deg, #8d0b41, #a3154f);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 12px;
}

.btn-remove-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(141, 11, 65, 0.35);
}

.btn-remove-modern:active {
    transform: scale(0.96);
}

/* FORM SECTIONS */
h4, h5 {
    margin-top: 30px;
    margin-bottom: 15px;
}

small.text-muted {
    display: block;
    margin-top: 3px;
    font-size: 12px;
    color: #999;
}

/* FLEX LAYOUT FOR PROFILE PIC + REMOVE BUTTON */
.profile-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .edit {
        padding: 20px;
    }
    .profile-img {
        width: 120px;
        height: 120px;
    }
}

.profile-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.profile-img {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: #f4f4f4;
    cursor: pointer;
    overflow: hidden;
    border: 2px solid #8d0b41;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.profile-placeholder {
    position: absolute;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #8d0b41;
    font-size: 14px;
    text-align: center;
}

.upload-input {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.btn-remove-modern {
    background: transparent;
    border: 1px solid #8d0b41;
    color: #8d0b41;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 1px;
}

.btn-remove-modern:hover {
    background: #8d0b41;
    color: #fff;
}

.add-photo-icon {
    position: relative;
    font-size: 29px;
    color: #8d0b41;
}
.overlay-plus {
    position: absolute;
    bottom: 0;
    right: 0;
    font-size: 12px;
}
    </style>
</head>

<body>
    <?php include '../Components/landlord-header.php'; ?>

    <div class="landlord-page">
    <div class="container m-auto">
        <h2>Edit Account</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success_message'];
                    unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
    
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>
    
            <div class="edit">
                <form method="POST" enctype="multipart/form-data">
    
                    <!-- ================= PROFILE IMAGE ================= -->
                    <div class="profile-container text-center mb-4">
                        <label class="profile-img" for="upload">
                            <img id="preview"
                                src="<?= !empty($user['profilePic']) ? '../uploads/' . htmlspecialchars($user['profilePic']) : '' ?>"
                                class="profile-preview"
                                style="<?= !empty($user['profilePic']) ? 'display:block;' : 'display:none;' ?>">
    
                            <div class="profile-placeholder"
                                style="<?= !empty($user['profilePic']) ? 'display:none;' : 'display:flex;' ?>">
                                <div class="add-photo-icon">
                                    <i class="fa-solid fa-image"></i>
                                    <i class="fa-solid fa-circle-plus overlay-plus"></i>
                                </div>
                                <div id="profile-text">Add Photo</div>
                            </div>
    
                            <input type="file" id="upload" name="profilePic" class="upload-input" accept="image/*">
                        </label>
    
                        <?php if (!empty($user['profilePic'])): ?>
                            <button type="button" id="remove-photo" class="btn-remove-modern mt-3">
                                <i class="fa fa-trash"></i> Remove Photo
                            </button>
                        <?php endif; ?>
                    </div>
    
                    <!-- ================= PERSONAL INFO ================= -->
                    <h4 class="mb-3">Personal Information</h4>
    
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
                        <div class="col-md-4">
                            <?php
                            $today = new DateTime();
                            $today->modify('-21 years');
                            $maxDOB = $today->format('Y-m-d');
                            ?>
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="birthday" class="form-control"
                                value="<?= htmlspecialchars($user['birthday'] ?? '') ?>" max="<?= $maxDOB ?>" required>
                            <small class="text-muted">You must be at least 21 years old.</small>
                        </div>
    
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="" disabled>Select your Gender</option>
                                <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>♀ Female
                                </option>
                                <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>♂ Male</option>
                            </select>
                        </div>
    
                        <div class="col-md-4">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="phoneNum" class="form-control"
                                value="<?= htmlspecialchars($user['phoneNum'] ?? '') ?>">
                        </div>
                    </div>
    
                    <!-- ================= ADDRESS ================= -->
                    <h4 class="mt-4 mb-3">Address</h4>
    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Street</label>
                            <input type="text" name="street" class="form-control"
                                value="<?= htmlspecialchars($user['street'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barangay</label>
                            <input type="text" name="barangay" class="form-control"
                                value="<?= htmlspecialchars($user['barangay'] ?? '') ?>">
                        </div>
                    </div>
    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control"
                                value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Province</label>
                            <input type="text" name="province" class="form-control"
                                value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Zip Code</label>
                            <input type="text" name="zipCode" class="form-control"
                                value="<?= htmlspecialchars($user['zipCode'] ?? '') ?>">
                        </div>
                    </div>
    
                    <!-- ================= IDENTITY VERIFICATION ================= -->
                    <h4 class="mt-4 mb-3">Identity Verification</h4>
    
                    <div class="mb-3">
                        <label class="form-label">Upload Government ID</label>
                        <input type="file" class="form-control" name="govID" accept="image/*,.pdf">
                    </div>
    
                    <div class="mb-3">
                        <label class="form-label">Upload Selfie Holding ID</label>
                        <input type="file" class="form-control" name="selfieID" accept="image/*">
                    </div>
    
                    <!-- ================= PROPERTY OWNERSHIP ================= -->
                    <h4 class="mt-4 mb-3">Property Ownership / Authorization</h4>
    
                    <div class="mb-3">
                        <label class="form-label">Proof of Ownership</label>
                        <input type="file" class="form-control" name="proofOwnership" accept="image/*,.pdf">
                    </div>
    
                    <div class="mb-3">
                        <label class="form-label">Authorization Letter + Owner’s ID</label>
                        <input type="file" class="form-control" name="authorizationDocs[]" accept="image/*,.pdf" multiple>
                    </div>
    
                    <!-- ================= BUTTONS ================= -->
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
const profileText = document.getElementById('profile-text');
const removeBtn = document.getElementById('remove-photo');

uploadInput.addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            profileText.style.display = 'none';
            removeBtn.style.display = 'inline-flex';
        };
        reader.readAsDataURL(file);
    }
});

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

    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>
</body>

</html>