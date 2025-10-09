<?php
require_once '../connection.php';
include '../session_auth.php';

// Get logged-in landlord_id from session
$landlord_id = $_SESSION['landlord_id'];

// Fetch landlord profile
$sql = "SELECT firstName, lastName, phoneNum, email, created_at, profilePic 
        FROM landlordtbl 
        WHERE ID= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
$landlord = $result->fetch_assoc();

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
            background-color: var(--bg-color);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        .avatar {
            width: 100px;
            height: 100px;
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
            <li><a href="landlord.php">Home</a></li>
            <li><a href="landlord-properties.php">Properties</a></li>
            <li><a href="landlord-message.php">Messages</a></li>
            <li><a href="support.php">Support</a></li>
        </ul>
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                <?= htmlspecialchars($_SESSION['username']); ?>
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
            <h1 class="mb-4">ACCOUNT</h1>
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="row justify-content-center user-profile">
                        <div class="col-lg-5 col-sm-12 justify-content-center d-flex">
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
                            <h1><?= htmlspecialchars(ucwords(strtolower($landlord['firstName'] . ' ' . $landlord['lastName']))); ?></h1>
                            <p>Phone Number: <?= htmlspecialchars($landlord['phoneNum']); ?></p>
                            <p>Email: <?= htmlspecialchars($landlord['email']); ?></p>
                            <p>Joined Date: <?= date("m-d-Y", strtotime($landlord['created_at'])); ?></p>


                            <div class="account-action d-flex justify-content-start align-items-center mt-4">
                                <button class="small-button" onclick="location.href='edit-account.php'">Edit</button>
                                <button class="small-button mx-2" onclick="location.href='delete-account.php'">Delete</button>
                                <button class="small-button" onclick="location.href='share-account.php'">Share</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- <div class="landlord-page">
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
                        <button class="small-button" onclick="location.href='edit-account.php'">Edit</button>
                        <button class="small-button mx-2" onclick="location.href='delete-account.php'">Delete</button>
                        <button class="small-button" onclick="location.href='share-account.php'">Share</button>
                    </div>
                </div>
            </div>
        </div>
    </div> -->


    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
</body>