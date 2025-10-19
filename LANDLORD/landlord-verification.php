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
    $targetDir = __DIR__ . "/uploads/ids/"; // Fixed: added trailing slash
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["ID_image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["ID_image"]["tmp_name"], $targetFile)) {
        // Save relative path for web access - Fixed path
        $dbPath = "uploads/ids/" . $fileName; // Relative to LANDLORD folder

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

        input[type="file"] {
            border: 2px solid var(--main-color);
            border-radius: 10px;
            padding: 10px;
            width: 100%;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 10px 0;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-verified {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-not_submitted {
            background: #d1ecf1;
            color: #0c5460;
        }

        .uploaded-id {
            margin-top: 15px;
            border: 2px solid var(--main-color);
            border-radius: 10px;
            padding: 10px;
            background: white;
        }

        .uploaded-id img {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
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
                        <h2>Upload Valid ID</h2>
                        <p>Upload a clear picture of your valid ID to get verified and start posting your property listings.</p>
                        
                        <div class="status-badge status-<?= htmlspecialchars($status) ?>">
                            <?php if ($status === 'pending'): ?>
                                ⏳ Verification Status: Pending Review
                            <?php elseif ($status === 'verified'): ?>
                                ✅ Verification Status: Verified
                            <?php elseif ($status === 'rejected'): ?>
                                ❌ Verification Status: Rejected
                            <?php else: ?>
                                📤 Verification Status: Not Submitted
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-info mt-3" role="alert">
                                <?= $message ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($status === 'pending'): ?>
                            <p class="mt-3">⏳ Your verification is being reviewed by the admin.</p>
                            <?php if ($id_image): ?>
                                <div class="uploaded-id">
                                    <p><strong>Uploaded ID:</strong></p>
                                    <img src="<?= htmlspecialchars($id_image) ?>" alt="Uploaded ID">
                                </div>
                            <?php endif; ?>

                        <?php elseif ($status === 'verified'): ?>
                            <div class="alert alert-success mt-3" role="alert">
                                <strong>✅ You are verified!</strong> You can now post properties.
                            </div>
                            <?php if ($id_image): ?>
                                <div class="uploaded-id">
                                    <p><strong>Verified ID:</strong></p>
                                    <img src="<?= htmlspecialchars($id_image) ?>" alt="Verified ID">
                                </div>
                            <?php endif; ?>

                        <?php elseif ($status === 'rejected'): ?>
                            <div class="alert alert-danger mt-3" role="alert">
                                <strong>❌ Your verification was rejected.</strong><br>
                                Please re-upload a clearer image of your valid ID.
                            </div>
                            
                            <h4 class="mt-4">Valid Government-Issued ID:</h4>
                            <ul style="font-size: 14px;">
                                <li>Philippine Passport</li>
                                <li>Driver's License (Professional / Non-Professional)</li>
                                <li>SSS ID / UMID</li>
                                <li>Postal ID</li>
                                <li>Voter's ID / COMELEC Voter's Certificate with photo</li>
                                <li>NBI Clearance</li>
                                <li>Police Clearance</li>
                                <li>PhilSys National ID</li>
                            </ul>

                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <label><strong>Upload Valid ID:</strong></label>
                                <input type="file" name="ID_image" accept="image/*" required class="form-control mt-2">
                                <button class="main-button mt-3 w-100" type="submit">Submit Again</button>
                            </form>

                        <?php else: ?> <!-- not_submitted -->
                            <div class="alert alert-info mt-3" role="alert">
                                📤 Please upload a valid ID for verification.
                            </div>

                            <h4 class="mt-4">Valid Government-Issued ID:</h4>
                            <ul style="font-size: 14px;">
                                <li>Philippine Passport</li>
                                <li>Driver's License (Professional / Non-Professional)</li>
                                <li>SSS ID / UMID</li>
                                <li>Postal ID</li>
                                <li>Voter's ID / COMELEC Voter's Certificate with photo</li>
                                <li>NBI Clearance</li>
                                <li>Police Clearance</li>
                                <li>PhilSys National ID</li>
                            </ul>

                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <label><strong>Upload Valid ID:</strong></label>
                                <input type="file" name="ID_image" accept="image/*" required class="form-control mt-2">
                                <button class="main-button mt-3 w-100" type="submit">Submit for Verification</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../Components/footer.php'; ?>

    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
</body>

</html>