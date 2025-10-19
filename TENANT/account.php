<?php
require_once '../connection.php';
include '../session_auth.php';

// login tenant from session
$tenant_id = $_SESSION['user_id'];

// fetch landlord profile
$sql = "SELECT firstName, lastName, phoneNum, email, created_at, profilePic 
        FROM tenanttbl 
        WHERE ID= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();

// image path
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
            border: 2px solid var(--main-color);
            width: 200px;
            height: 200px;
            border-radius: 10px;
        }

        .user-profile {
            border: 6px solid var(--main-color);
            padding: 20px;
            border-radius: 10px;
        }

        .avatar {
            width: 200px !important;
            height: 200px !important;
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
    <?php include '../Components/tenant-header.php' ?>
    <!-- ACCOUNT PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h1 class="mb-5">ACCOUNT</h1>
            <div class="row gy-4 justify-content-center user-profile">
                <div class="col-lg-5 col-sm-12">
                    <div class="account-img d-flex align-items-center justify-content-center">
                        <?php if (!empty($tenant['profilePic'])): ?>
                            <img src="<?= htmlspecialchars($profilePath); ?>" 
                             alt="Profile Picture" onerror="this.src='../images/default-avatar.png';
                            ">
                        <?php else: ?>
                            <div class="avatar">
                                <?= $firstLetter ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5 col-sm-12">
                    <h1><?= htmlspecialchars($tenant['firstName'] . ' ' . $tenant['lastName']); ?></h1>
                    <p>Phone Number: <?= htmlspecialchars($tenant['phoneNum']); ?></p>
                    <p>Email: <?= htmlspecialchars($tenant['email']); ?></p>
                    <p>Joined Date: <?= date("m-d-Y", strtotime($tenant['created_at'])); ?></p>


                    <div class="account-action d-flex justify-content-start align-items-center mt-4">
                        <button class="small-button" onclick="location.href='edit-account.php'">Edit</button>
                        <button class="small-button mx-2" onclick="location.href='delete-account.php'">Delete</button>
                        <button class="small-button" onclick="location.href='share-account.php'">Share</button>
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