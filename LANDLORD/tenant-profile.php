<?php
require_once '../connection.php';
include '../session_auth.php';

// Ensure landlord is logged in
if (!isset($_SESSION['landlord_id'])) {
    die("Unauthorized access. Please log in as landlord.");
}

$landlord_id = (int)$_SESSION['landlord_id'];

// Get tenant_id from URL
$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 0;
if ($tenant_id <= 0) {
    die("Invalid tenant ID.");
}

// Fetch tenant profile
$sql = "SELECT ID, firstName, lastName, phoneNum, email, created_at, profilePic 
        FROM tenanttbl 
        WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$stmt->close();


if (!$tenant) {
    die("Tenant not found.");
}

// Profile image
$profilePath = $tenant['profilePic'] ?? '';
if (!empty($profilePath) && !str_starts_with($profilePath, 'http')) {
    $profilePath = "../uploads/" . $profilePath;
}

$firstLetter = strtoupper(substr($tenant['firstName'], 0, 1));
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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>ACCOUNT</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        .account-img img {
            width: 150px !important;
            height: 150px !important;
            border-radius: 50%;
            background: var(--main-color);
            color: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        }

        .user-profile {
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .avatar {
            width: 100px !important;
            height: 100px !important;
            border-radius: 10px;
            background: var(--main-color);
            color: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php' ?>
    <!-- ACCOUNT PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h1 class="mb-5">Tenant Profile</h1>
            <div class="row gy-4 justify-content-center user-profile">
                <div class="col-lg-5 col-sm-12">
                    <div class="account-img d-flex align-items-center justify-content-center">
                        <?php if (!empty($tenant['profilePic'])): ?>
                            <img src="<?= htmlspecialchars($profilePath); ?>" alt="Profile Picture" class="rounded-circle" style="width:150px; height:150px; object-fit:cover;">
                        <?php else: ?>
                            <div class="avatar" style="width:150px; height:150px; display:flex; align-items:center; justify-content:center; font-size:48px; background:#ccc; border-radius:50%;">
                                <?= $firstLetter ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5 col-sm-12">
                    <h2><?= htmlspecialchars(ucwords($tenant['firstName'] . ' ' . $tenant['lastName'])); ?></h2>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($tenant['phoneNum']); ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($tenant['email']); ?></p>
                    <p><strong>Joined:</strong> <?= date("F j, Y", strtotime($tenant['created_at'])); ?></p>

                    <div class="account-action d-flex justify-content-start align-items-center mt-4">
                        <!-- Chat button -->
                        <button class="small-button"
                            onclick="location.href='landlord-message.php?tenant_id=<?= htmlspecialchars($tenant['ID']); ?>'">
                            Chat
                        </button>

                        <!-- Optional actions -->
                        <button class="small-button mx-2" onclick="location.href='report-tenant.php?tenant_id=<?= $tenant_id ?>'">Report</button>
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