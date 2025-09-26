<?php
require_once '../connection.php';
include '../session_auth.php';

$landlordId = $_GET['id'] ?? null;

if (!$landlordId || !is_numeric($landlordId)) {
    die("Invalid landlord ID.");
}

$landlordId = intval($landlordId);

// Fetch landlord info
$sql = "SELECT ID, firstName, lastName, email, phoneNum, profilePic, created_at 
        FROM landlordtbl 
        WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$landlordResult = $stmt->get_result();

if ($landlordResult->num_rows === 0) {
    die("Landlord not found.");
}
$landlord = $landlordResult->fetch_assoc();
$stmt->close();

// Fetch landlord's listings
$sql = "SELECT ID, listingName, price, address, barangay, category, images 
        FROM listingtbl 
        WHERE landlord_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$listingsResult = $stmt->get_result();
$listings = $listingsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$firstLetter = strtoupper(substr($landlord['firstName'], 0, 1)); 
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
    <title>Listing Landlord</title>
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
            width: 200px;
            height: 200px;
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
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>
        <ul class="nav-links">
            <li><a href="tenant.php">Home</a></li>
            <li><a href="tenant-rental.php">My Rental</a></li>
            <li><a href="tenant-favorite.php">Favorite</a></li>
            <li><a href="tenant-map.php">Map</a></li>
            <li><a href="tenant-messages.php">Messages</a></li>
            <li><a href="../support.php">Support</a></li>
        </ul>
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                Tenant
                <div class="dropdown-content">
                    <a href="account.php">Account</a>
                    <a href="settings.php">Settings</a>
                    <a href="../LOGIN/logout.php">Log out</a>
                </div>
            </div>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>
    <!-- ACCOUNT PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h1 class="mb-5">ACCOUNT</h1>
            <div class="row gy-4 justify-content-center user-profile">
                <div class="col-lg-5 col-sm-12">
                    <div class="account-img d-flex align-items-center justify-content-center">
                        <?php if (!empty($landlord['profilePic'])): ?>
                            <img src="<?= $landlord['profilePic']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <div class="avatar">
                                <?= $firstLetter ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5 col-sm-12">
                    <h1><?= htmlspecialchars($landlord['firstName'] . ' ' . $landlord['lastName']); ?></h1>
                    <p>Phone Number: <?= htmlspecialchars($landlord['phoneNum']); ?></p>
                    <p>Email: <?= htmlspecialchars($landlord['email']); ?></p>
                    <p>Joined Date: <?= date("m-d-Y", strtotime($landlord['created_at'])); ?></p>


                    <div class="account-action d-flex justify-content-start align-items-center mt-4">
                        <button class="small-button" onclick="location.href='edit-account.php'">Chat</button>
                        <button class="small-button mx-2" onclick="location.href='delete-account.php'">Report</button>
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