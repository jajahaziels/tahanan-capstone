<?php
require_once '../connection.php';
include '../session_auth.php';

$landlord_id = $_SESSION['landlord_id'] ?? null;
$message = "";

if (!$landlord_id) {
    die("You must log in as a landlord to access this page.");
}

if (!empty($_SESSION['error'])) {
    $message = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ID_image'])) {
    $targetDir = __DIR__ . "/uploads/ids";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["ID_image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["ID_image"]["tmp_name"], $targetFile)) {
        // Save relative path for web access
        $dbPath = "../LANDLORD/uploads/ids" . $fileName;

        $sql = "UPDATE landlordtbl 
                    SET ID_image = ?, verification_status = 'pending' 
                    WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $dbPath, $landlord_id);
        $stmt->execute();
        $message = "✅ Verification request submitted. Please wait for admin approval.";
    } else {
        $message = "❌ Error uploading file.";
    }
}

$sql = "SELECT verification_status, ID_image FROM landlordtbl WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$status = $result['verification_status'] ?? 'not_submitted';
$id_image = $result['ID_image'] ?? null;

if ($status === 'pending' && empty($id_image)) {
    $status = 'not_submitted';
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
    <link rel="stylesheet" href="../css/style.css">
    <title>Landlord Verification</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }

        .verification-form {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        input {
            border: 2px solid var(--main-color);
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <!-- PROPERTY PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h1>Landlord Verification</h1>
            <p>To maintain the safety and trust of our platform, all landlords are required to go through a verification process before they can post a property. This ensures that only legitimate property owners or managers are able to list rentals, protecting tenants from fraudulent listings.</p>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-6">
                    <div class="verification-form">
                        <h1>Upload Valid ID</h1>
                        <p>Upload a clear picture of your valid ID to get verified and start posting your property listings.</p>
                        <p>Valid Government-Issued ID:</p>
                        <ul>
                            <li>Philippine Passport</li>
                            <li>Driver’s License (Professional / Non-Professional)</li>
                            <li>SSS ID / UMID</li>
                            <li>Postal ID</li>
                            <li>Voter’s ID / COMELEC Voter’s Certificate with photo</li>
                            <li>NBI Clearance</li>
                            <li>Police Clearance</li>
                            <li>PhilSys National ID</li>
                        </ul>


                        <p>Your Verification Status: <?= htmlspecialchars($status) ?></p>
                        <?php if (!empty($message)) echo "<p style='color:#0000ffb6;'>$message</p>"; ?>

                        <?php if ($status === 'pending'): ?>
                            <p>⏳ Your verification is being reviewed by the admin.</p>
                            <?php if ($id_image): ?>
                                <img src="<?= htmlspecialchars($id_image) ?>" width="200">
                            <?php endif; ?>

                        <?php elseif ($status === 'verified'): ?>
                            <p>✅ You are verified! You can now post properties.</p>
                            <?php if ($id_image): ?>
                                <img src="<?= htmlspecialchars($id_image) ?>" width="200">
                            <?php endif; ?>

                        <?php elseif ($status === 'rejected'): ?>
                            <p>❌ Your verification was rejected. Please re-upload a valid ID.</p>
                            <form method="POST" enctype="multipart/form-data">
                                <label>Upload Valid ID:</label>
                                <input type="file" name="ID_image" accept="image/*" required>
                                <button class="main-button mt-2" type="submit">Submit Again</button>
                            </form>

                        <?php else: ?> <!-- not_submitted -->
                            <p>📤 Please upload a valid ID for verification.</p>
                            <form method="POST" enctype="multipart/form-data">
                                <label>Upload Valid ID:</label>
                                <input type="file" name="ID_image" accept="image/*" required>
                                <button class="main-button mt-2" type="submit">Submit</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
</body>

</html>