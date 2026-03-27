<?php
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("You must be logged in to edit your account.");
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "tahanandb");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$stmt = $conn->prepare("SELECT * FROM landlordtbl WHERE ID=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$upload_dir = "../uploads/";
if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

function uploadFile($file, $prefix, $dir) {
    if (isset($file) && $file['error'] === 0 && !empty($file['name'])) {
        $filename = time() . "_{$prefix}_" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) return $filename;
    }
    return null;
}

// ── CHANGE PASSWORD ──────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_password, $user['password'])) {
        $pw_error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 8) {
        $pw_error = "New password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $pw_error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE landlordtbl SET password = ? WHERE ID = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Password changed successfully!";
            header("Location: account.php");
            exit;
        } else {
            $pw_error = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ── EDIT ACCOUNT ─────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['change_password'])) {

    if (isset($_POST['removePhoto']) && $_POST['removePhoto'] == '1') {
        if (!empty($user['profilePic']) && file_exists($upload_dir . $user['profilePic']))
            unlink($upload_dir . $user['profilePic']);
        $profilePic = null;
    } else {
        $profilePic = uploadFile($_FILES['profilePic'] ?? null, 'profile', $upload_dir) ?? $user['profilePic'];
    }

    $verificationId = uploadFile($_FILES['verificationId'] ?? null, 'govid',  $upload_dir) ?? $user['verificationId'];
    $ID_image       = uploadFile($_FILES['ID_image']       ?? null, 'selfie', $upload_dir) ?? $user['ID_image'];

    $firstName  = $_POST['firstName']  ?? $user['firstName'];
    $lastName   = $_POST['lastName']   ?? $user['lastName'];
    $middleName = $_POST['middleName'] ?? $user['middleName'];
    $street     = $_POST['street']     ?? $user['street'];
    $barangay   = $_POST['barangay']   ?? $user['barangay'];
    $city       = $_POST['city']       ?? $user['city'];
    $province   = $_POST['province']   ?? $user['province'];
    $zipCode    = $_POST['zipCode']    ?? $user['zipCode'];
    $phoneNum   = $_POST['phoneNum']   ?? $user['phoneNum'];
    $email      = $_POST['email']      ?? $user['email'];
    $birthday   = $_POST['birthday']   ?? $user['birthday'];
    $gender     = $_POST['gender']     ?? $user['gender'];
    $username   = $_POST['userName']   ?? $user['username'];

    $stmt = $conn->prepare("UPDATE landlordtbl SET 
        firstName=?, lastName=?, middleName=?, username=?, street=?, barangay=?, city=?, province=?, zipCode=?, phoneNum=?, email=?, birthday=?, gender=?, profilePic=?, verificationId=?, ID_image=? 
        WHERE ID=?");
    $stmt->bind_param("ssssssssssssssssi",
        $firstName, $lastName, $middleName, $username,
        $street, $barangay, $city, $province, $zipCode,
        $phoneNum, $email, $birthday, $gender,
        $profilePic, $verificationId, $ID_image, $user_id
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>EDIT ACCOUNT</title>
    <style>
        .tenant-page {
            margin-top: 140px !important;
            font-family: 'Inter', sans-serif;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            padding-bottom: 100px;
        }

        /* ── Tabs ── */
        .account-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 0;
        }

        .account-tab {
            padding: 12px 28px;
            font-size: .95rem;
            font-weight: 600;
            color: #888;
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all .2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .account-tab:hover { color: #8d0b41; }
        .account-tab.active { color: #8d0b41; border-bottom-color: #8d0b41; }

        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Card ── */
        .edit {
            background: #fff;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
            transition: box-shadow .3s;
        }
        .edit:hover { box-shadow: 0 12px 30px rgba(0,0,0,.12); }
        .edit-body  { padding: 30px 25px; }

        h2, h4, h5 { font-weight: 600; color: #8d0b41; }

        /* ── Profile image ── */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 30px; }

        .profile-img {
            position: relative; width: 150px; height: 150px;
            border-radius: 50%; background: #f4f4f4; cursor: pointer;
            overflow: hidden; border: 2px solid #8d0b41;
            display: flex; align-items: center; justify-content: center;
            transition: all .3s ease;
        }
        .profile-img:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(141,11,65,.15); }
        .profile-preview   { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

        .profile-placeholder {
            position: absolute; width: 100%; height: 100%;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; color: #8d0b41; font-size: 14px; text-align: center;
        }

        .upload-input { position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .add-photo-icon { position: relative; font-size: 29px; color: #8d0b41; }
        .overlay-plus   { position: absolute; bottom: 0; right: 0; font-size: 12px; }

        /* ── Inputs ── */
        input.form-control, select.form-control {
            border-radius: 10px; padding: 12px 15px; border: 1px solid #ccc;
            transition: all .3s ease; font-size: 14px; background: #fafafa;
        }
        input.form-control:focus, select.form-control:focus {
            border-color: #8d0b41; box-shadow: 0 4px 12px rgba(141,11,65,.15); outline: none;
        }
        label.form-label { font-weight: 500; margin-bottom: 6px; }

        /* ── Buttons ── */
        .main-button {
            background: linear-gradient(135deg, #8d0b41, #a3154f);
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s ease;
            box-shadow: 0 6px 20px rgba(141,11,65,.25);

            display: inline-flex;        
            align-items: center;          
            justify-content: center;      
            white-space: nowrap;   
        }

        .main-button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(141,11,65,.35); }
        .main-button:active { transform: scale(.97); }

        .btn-remove-modern {
            background: transparent; border: 1px solid #8d0b41;
            color: #8d0b41; padding: 6px 15px; border-radius: 20px; font-size: 14px;
        }
        .btn-remove-modern:hover { background: #8d0b41; color: #fff; }

        /* ── Password UI ── */
        .pw-wrapper { position: relative; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%); cursor: pointer;
            color: #aaa; font-size: .9rem; z-index: 5;
        }
        .pw-toggle:hover { color: #8d0b41; }

        .strength-bar-wrap { height: 5px; background: #e9ecef; border-radius: 99px; margin-top: 6px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; border-radius: 99px; transition: width .3s, background .3s; }
        .strength-label { font-size: .78rem; margin-top: 3px; font-weight: 500; }

        .section-label {
            font-size: .78rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: #aaa; margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: #e9ecef; }

        small.text-muted { display: block; margin-top: 3px; font-size: 12px; color: #999; }

        @media (max-width: 768px) {
            .edit-body { padding: 20px; }
            .profile-img { width: 120px; height: 120px; }
            .account-tab { padding: 10px 16px; font-size: .85rem; }
        }
    </style>
</head>

<body>
    <?php include '../Components/tenant-header.php'; ?>

    <div class="tenant-page">
        <div class="container m-auto">
            <h2>Edit Account</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>
            <?php if (isset($pw_error)): ?>
                <div class="alert alert-danger"><?= $pw_error ?></div>
            <?php endif; ?>

            <!-- ── Tabs ── -->
            <div class="account-tabs">
                <button class="account-tab active" data-tab="profile">
                    <i class="fa-solid fa-user"></i> Profile Info
                </button>
                <button class="account-tab" data-tab="password">
                    <i class="fa-solid fa-lock"></i> Change Password
                </button>
            </div>

            <div class="edit">
                <div class="edit-body">

                    <!-- ══ TAB 1 — Profile Info ══ -->
                    <div class="tab-pane active" id="tab-profile">
                        <form method="POST" enctype="multipart/form-data">

                            <!-- Profile image -->
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

                            <h4 class="mb-3">Personal Information</h4>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="firstName" class="form-control" value="<?= htmlspecialchars($user['firstName'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="lastName" class="form-control" value="<?= htmlspecialchars($user['lastName'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middleName" class="form-control" value="<?= htmlspecialchars($user['middleName'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <?php $today = new DateTime(); $today->modify('-21 years'); $maxDOB = $today->format('Y-m-d'); ?>
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($user['birthday'] ?? '') ?>" max="<?= $maxDOB ?>" required>
                                    <small class="text-muted">You must be at least 21 years old.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control" required>
                                        <option value="" disabled>Select your Gender</option>
                                        <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>♀ Female</option>
                                        <option value="Male"   <?= ($user['gender'] ?? '') == 'Male'   ? 'selected' : '' ?>>♂ Male</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="phoneNum" class="form-control" value="<?= htmlspecialchars($user['phoneNum'] ?? '') ?>">
                                </div>
                            </div>

                            <h4 class="mt-4 mb-3">Address</h4>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Street</label>
                                    <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($user['street'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay</label>
                                    <input type="text" name="barangay" class="form-control" value="<?= htmlspecialchars($user['barangay'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Province</label>
                                    <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($user['province'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Zip Code</label>
                                    <input type="text" name="zipCode" class="form-control" value="<?= htmlspecialchars($user['zipCode'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-start gap-4 mt-4">
                                <button type="submit" class="main-button">Save Changes</button>
                                <button type="button" class="main-button" onclick="location.href='account.php'">Cancel</button>
                            </div>
                        </form>
                    </div><!-- end tab-profile -->

                    <!-- ══ TAB 2 — Change Password ══ -->
                    <div class="tab-pane" id="tab-password">
                        <form method="POST" id="pw-form">
                            <input type="hidden" name="change_password" value="1">

                            <p class="text-muted mb-4" style="font-size:.9rem;">
                                Choose a strong password with at least 8 characters.
                                Your current session will remain active after changing.
                            </p>

                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <div class="pw-wrapper">
                                    <input type="password" name="current_password" id="current_password"
                                        class="form-control" placeholder="Enter your current password" required>
                                    <span class="pw-toggle" data-target="current_password"><i class="fa-solid fa-eye"></i></span>
                                </div>
                            </div>

                            <div class="section-label">New Password</div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="pw-wrapper">
                                    <input type="password" name="new_password" id="new_password"
                                        class="form-control" placeholder="At least 8 characters" required
                                        oninput="checkStrength(this.value)">
                                    <span class="pw-toggle" data-target="new_password"><i class="fa-solid fa-eye"></i></span>
                                </div>
                                <div class="strength-bar-wrap mt-2">
                                    <div class="strength-bar" id="strength-bar"></div>
                                </div>
                                <div class="strength-label" id="strength-label" style="color:#aaa;">Enter a new password</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <div class="pw-wrapper">
                                    <input type="password" name="confirm_password" id="confirm_password"
                                        class="form-control" placeholder="Repeat your new password" required
                                        oninput="checkMatch()">
                                    <span class="pw-toggle" data-target="confirm_password"><i class="fa-solid fa-eye"></i></span>
                                </div>
                                <small id="match-msg" style="font-size:.78rem; margin-top:4px; display:block;"></small>
                            </div>

                            <div class="d-flex justify-content-start gap-3 mt-2">
                                <button type="submit" class="main-button">Update Password</button>
                                <button type="button" class="main-button" onclick="location.href='account.php'">Cancel</button>
                            </div>
                        </form>
                    </div><!-- end tab-password -->

                </div>
            </div>
        </div>
    </div>

    <script>
        /* ── Tab switching ── */
        document.querySelectorAll('.account-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.account-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
            });
        });

        <?php if (isset($pw_error)): ?>
        document.querySelector('[data-tab="password"]').click();
        <?php endif; ?>

        /* ── Profile photo ── */
        const uploadInput = document.getElementById('upload');
        const preview     = document.getElementById('preview');
        const profileText = document.getElementById('profile-text');
        const removeBtn   = document.getElementById('remove-photo');

        if (uploadInput) {
            uploadInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        preview.src = ev.target.result;
                        preview.style.display = 'block';
                        if (profileText) profileText.style.display = 'none';
                        if (removeBtn)   removeBtn.style.display   = 'inline-flex';
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
                    const input   = document.createElement('input');
                    input.type    = 'hidden';
                    input.name    = 'removePhoto';
                    input.id      = 'removePhotoInput';
                    input.value   = '1';
                    document.querySelector('form').appendChild(input);
                }
            });
        }

        /* ── Password show/hide ── */
        document.querySelectorAll('.pw-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const input = document.getElementById(toggle.dataset.target);
                const icon  = toggle.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });

        /* ── Strength checker ── */
        function checkStrength(val) {
            const bar = document.getElementById('strength-bar');
            const lbl = document.getElementById('strength-label');
            let score = 0;
            if (val.length >= 8)          score++;
            if (/[A-Z]/.test(val))        score++;
            if (/[0-9]/.test(val))        score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { w:'0%',   color:'#e9ecef', text:'Enter a new password', tc:'#aaa'    },
                { w:'25%',  color:'#dc3545', text:'Weak',                 tc:'#dc3545' },
                { w:'50%',  color:'#fd7e14', text:'Fair',                 tc:'#fd7e14' },
                { w:'75%',  color:'#ffc107', text:'Good',                 tc:'#e6a800' },
                { w:'100%', color:'#198754', text:'Strong',               tc:'#198754' },
            ];
            const lvl = val.length === 0 ? levels[0] : (levels[score] || levels[1]);
            bar.style.width      = lvl.w;
            bar.style.background = lvl.color;
            lbl.textContent      = lvl.text;
            lbl.style.color      = lvl.tc;
            checkMatch();
        }

        /* ── Match checker ── */
        function checkMatch() {
            const np  = document.getElementById('new_password').value;
            const cp  = document.getElementById('confirm_password').value;
            const msg = document.getElementById('match-msg');
            if (!cp) { msg.textContent = ''; return; }
            if (np === cp) { msg.textContent = '✔ Passwords match';        msg.style.color = '#198754'; }
            else           { msg.textContent = '✖ Passwords do not match'; msg.style.color = '#dc3545'; }
        }
    </script>

    <script src="../js/script.js" defer></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/scrollreveal"></script>
</body>
</html>