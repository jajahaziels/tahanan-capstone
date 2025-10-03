<?php
require_once '../connection.php';
include '../session_auth.php';

// property id from URL
$listingID = intval($_GET['ID'] ?? 0);
if ($listingID <= 0) {
    die("Invalid property ID.");
}

// ✅ Query 1: Get Property + Landlord Info
$sqlProperty = "
    SELECT l.*, ld.firstName AS landlord_fname, ld.lastName AS landlord_lname, ld.profilePic 
    FROM listingtbl l
    JOIN landlordtbl ld ON l.landlord_id = ld.ID
    WHERE l.ID = ?
    LIMIT 1
";
$stmt = $conn->prepare($sqlProperty);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$resultProperty = $stmt->get_result();

if ($resultProperty->num_rows === 0) {
    die("Property not found.");
}
$property = $resultProperty->fetch_assoc();
$images = json_decode($property['images'], true) ?? [];
$stmt->close();

// ✅ Query 2: Get Tenant Applications
$sqlApplications = "
    SELECT r.ID as request_id, r.status, r.date,
           t.firstName, t.lastName, t.phoneNum, t.email
    FROM renttbl r
    JOIN tenanttbl t ON r.tenant_id = t.ID
    WHERE r.listing_id = ?
";
$stmt2 = $conn->prepare($sqlApplications);
$stmt2->bind_param("i", $listingID);
$stmt2->execute();
$applications = $stmt2->get_result();

// PHP
$propertyImg = "../img/house1.jpeg";
if (!empty($rental['images'])) {
    $images = json_decode($rental['images'], true);
    if (!empty($images[0])) {
        $propertyImg = "../uploads/" . $images[0];
    }
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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <title>PROPERTIES</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }

        .available {
            background-color: #0697065e;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
            font-size: 12px;
        }

        .occupied {
            background-color: #ff00006b;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
            font-size: 12px;
        }

        .status-occupied {
            background-color: #ff0000c5;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
        }

        .status-available {
            background-color: #008000d0;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--text-color);
        }

        .status {
            position: absolute;
            margin-top: -170px;
            padding: 0 15px;
            display: flex;
            gap: 8px;
        }

        .status-label {
            background-color: #008000d0;
            color: var(--bg-color);
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 14px;
        }

        .edit {
            background-color: #0000ff59;
            color: var(--text-color);
            width: 45px;
            height: 45px;
            font-size: 16px;
            border-radius: 20px;
        }

        .property-img {
            width: 300px;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

        <ul class="nav-links">
            <li><a href="landlord.php">Home</a></li>
            <li><a href="landlord-properties.php" class="active">Properties</a></li>
            <li><a href="landlord-message.php">Messages</a></li>
            <li><a href="support.php">Support</a></li>
        </ul>
        <!-- NAV ICON / NAME -->
        <div class="nav-icons">
            <!-- DROP DOWN -->
            <div class="dropdown">
                <i class="fa-solid fa-user"></i>
                <?= htmlspecialchars(ucwords(strtolower($_SESSION['username']))); ?>
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
    <!-- PROPERTY PAGE -->
    <div class="landlord-page">
        <div class="container m-auto d-flex justify-content-between align-items-center">
            <div class="property-title d-flex ">
                <h1>Property Details</h1>
                <div class="d-flex align-items-center justify-content-center">
                    <p class="available mx-3">Available</p>
                    <p class="occupied">Occupied</p>
                </div>
            </div>
            <div class="d-flex">
                <button class="main-button" onclick="location.href='landlord-properties.php'">Back</button>
            </div>
        </div>
    </div>
    </div>
    <!-- PROPERTY LIST -->
    <div class="property-list">
        <div class="container m-auto">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">
                        <!-- Property Info -->
                        <div class="col-lg-4 col-sm-12">
                            <h1 class="text-center">Property</h1>
                            <div class="d-flex justify-content-center align-items-center">
                                <?php if (!empty($images) && is_array($images)): ?>
                                    <?php foreach ($images as $img): ?>
                                        <img src="../LANDLORD/uploads/<?php echo htmlspecialchars($img); ?>"
                                            alt="Property Image" class="property-img mb-2">
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <img src="../img/house1.jpeg" alt="Property Image" class="property-img">
                                <?php endif; ?>


                            </div>
                            <p><strong><?php echo htmlspecialchars($property['listingName']); ?></strong></p>
                            <p><?php echo htmlspecialchars($property['address']); ?></p>
                        </div>

                        <!-- Requests -->
                        <div class="col-lg-4 col-sm-12">
                            <h1 class="text-center">Requests</h1>
                            <?php if ($applications->num_rows > 0): ?>
                                <?php while ($req = $applications->fetch_assoc()): ?>
                                    <div class="p-2 border rounded mb-2">
                                        <p><strong><?php echo $req['firstName'] . ' ' . $req['lastName']; ?></strong></p>
                                        <p><?php echo $req['email']; ?> | <?php echo $req['phoneNum']; ?></p>
                                        <p>Status: <span class="badge bg-secondary"><?php echo ucfirst($req['status']); ?></span></p>

                                        <!-- Accept/Reject -->
                                        <form method="post" action="update-request.php" class="d-flex gap-2">
                                            <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                            <input type="hidden" name="listing_id" value="<?php echo $listingID; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">❌ Reject</button>
                                        </form>


                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No tenant requests yet.</p>
                            <?php endif; ?>
                        </div>
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