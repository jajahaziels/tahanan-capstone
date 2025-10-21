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

// Fetch landlord's available listings only
$sql = "
    SELECT 
        l.ID, 
        l.listingName, 
        l.price, 
        l.address, 
        l.barangay, 
        l.category, 
        l.images, 
        l.rooms, 
        lt.firstName, 
        lt.lastName, 
        lt.profilePic
    FROM listingtbl AS l
    JOIN landlordtbl AS lt ON l.landlord_id = lt.ID
    LEFT JOIN renttbl AS r 
        ON l.ID = r.listing_id AND r.status = 'approved'
    WHERE lt.ID = ? 
      AND l.availability = 'available'
      AND r.ID IS NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$listingsResult = $stmt->get_result();
$listings = $listingsResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();



$firstLetter = strtoupper(substr($landlord['firstName'], 0, 1));
$profilePath = !empty($landlord['profilePic']) ? "../uploads/" . $landlord['profilePic'] : "";
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
    <title>Landlord Profile</title>
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
    <?php include '../Components/tenant-header.php' ?>
    <!-- ACCOUNT PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h1 class="mb-5">Landlord Profile</h1>

            <!-- Landlord Info -->
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="row gy-4 justify-content-center user-profile">
                        <div class="col-lg-5 col-sm-12">
                            <div class="account-img d-flex align-items-center justify-content-center">
                                <?php if (!empty($landlord['profilePic'])): ?>
                                    <img src="<?= htmlspecialchars($profilePath); ?>" alt="Profile Picture" class="rounded-circle" style="width:180px; height:180px; object-fit:cover;">
                                <?php else: ?>
                                    <div class="avatar d-flex align-items-center justify-content-center" style="width:150px; height:150px; border-radius:50%; background:#ddd; font-size:3rem; font-weight:bold;">
                                        <?= $firstLetter ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-lg-5 col-sm-12">
                            <h2><?= htmlspecialchars(ucwords(strtolower($landlord['firstName'] . ' ' . $landlord['lastName']))); ?></h2>
                            <p><strong>Phone Number:</strong> <?= htmlspecialchars($landlord['phoneNum']); ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($landlord['email']); ?></p>
                            <p><strong>Joined Date:</strong> <?= date("F j, Y", strtotime($landlord['created_at'])); ?></p>

                            <div class="account-action d-flex justify-content-start align-items-center mt-4">
                                <button class="small-button"
                                    onclick="window.location.href='tenant-messages.php?landlord_id=<?= htmlspecialchars($landlord['ID']); ?>'">
                                    Chat
                                </button>
                                <button class="small-button mx-2" onclick="alert('Report function coming soon.')">Report</button>
                                <button class="small-button" onclick="navigator.clipboard.writeText(window.location.href); alert('Profile link copied!')">Share</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Landlord’s Listings -->
            <!-- Property Listings -->
            <h2 class="mb-4 mt-4 text-start">Properties by <?= htmlspecialchars(ucwords(strtolower($landlord['firstName']))); ?></h2>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">
                        <?php if (!empty($listings)): ?>
                            <?php foreach ($listings as $row): ?>
                                <div class="col-lg-4 col-sm-12">
                                    <div class="cards mb-4" onclick="window.location='property-details.php?ID=<?= $row['ID']; ?>'">
                                        <div class="position-relative">
                                            <?php
                                            $images = json_decode($row['images'], true);
                                            $imagePath = '../img/house1.jpeg';
                                            if (!empty($images) && is_array($images) && isset($images[0])) {
                                                $imagePath = '../LANDLORD/uploads/' . $images[0];
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imagePath); ?>"
                                                alt="Property Image"
                                                class="property-img"
                                                style="width:100%; max-height:200px; object-fit:cover;"
                                                onerror="this.src='../img/house1.jpeg'">

                                            <div class="labels">
                                                <div class="label"><i class="fa-regular fa-star"></i> Featured</div>
                                                <div class="label">Specials</div>
                                            </div>

                                            <!-- Price -->
                                            <div class="price-tag">₱ <?= number_format($row['price']); ?></div>
                                        </div>

                                        <div class="cards-content">
                                            <!-- Property Name -->
                                            <h5 class="mb-2 house-name"><?= htmlspecialchars($row['listingName']); ?></h5>

                                            <!-- Address -->
                                            <div class="mb-2 location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($row['address']); ?>
                                            </div>

                                            <!-- Features -->
                                            <div class="features">
                                                <div class="m-2">
                                                    <i class="fas fa-bed"></i> <?= htmlspecialchars($row['rooms']); ?> Bedroom
                                                </div>
                                                <div class="m-2">
                                                    <i class="fa-solid fa-building"></i> <?= htmlspecialchars($row['category']); ?>
                                                </div>
                                            </div>

                                            <div class="divider my-3"></div>

                                            <!-- Landlord Info -->
                                            <div class="landlord-info">
                                                <div class="landlord-left d-flex align-items-center">
                                                    <?php if (!empty($row['profilePic'])): ?>
                                                        <img src="../uploads/<?= htmlspecialchars($row['profilePic']); ?>"
                                                            alt="Landlord"
                                                            style="width:40px; height:40px; border-radius:50%; object-fit:cover;"
                                                            onerror="this.style.display='none'">
                                                    <?php endif; ?>

                                                    <div class="ms-2">
                                                        <div class="landlord-name">
                                                            <?= ucwords(htmlspecialchars($row['firstName'] . ' ' . $row['lastName'])); ?>
                                                        </div>
                                                        <div class="landlord-role">Landlord</div>
                                                    </div>
                                                </div>

                                                <div class="landlord-actions">
                                                    <div class="btn"><i class="fa-solid fa-user"></i></div>
                                                    <div class="btn"><i class="fas fa-comment-dots"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #666;">No available listings from this landlord.</p>
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