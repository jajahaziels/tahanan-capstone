<?php
require_once '../connection.php';
include '../session_auth.php';

// Get landlord ID from session
$landlord_id = $_SESSION['landlord_id'];

// Fetch all landlord properties + check if they have approved tenants
$sql = "
    SELECT l.*, 
           (SELECT r.ID 
            FROM renttbl r 
            WHERE r.listing_id = l.ID 
              AND r.status = 'approved' 
            LIMIT 1) AS approved_rental_id,
           (SELECT COUNT(*) 
            FROM renttbl r 
            WHERE r.listing_id = l.ID 
              AND r.status = 'approved') AS approved_count,
           l.availability
    FROM listingtbl l
    WHERE l.landlord_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
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
            margin-top: 140px;
        }

        .status-occupied {
            background-color: #ff0000c5;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--bg-color);
        }

        .status-available {
            background-color: #008000d0;
            color: white;
            padding: 8px;
            border-radius: 20px;
            color: var(--bg-color);
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
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <!-- PROPERTY PAGE -->
    <div class="landlord-page">
        <div class="container m-auto d-flex justify-content-between align-items-center">
            <div class="property-title d-flex ">
                <h1>My Properties</h1>
            </div>
            <div class="d-flex">
                <button class="main-button" onclick="location.href='add-property.php'">Add Property</button>
            </div>
        </div>
    </div>
    </div>
    <!-- PROPERTY LIST -->
    <div class="property-list mt-4">
        <div class="container m-auto">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row justify-content-center">

                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php $isApproved = $row['approved_count'] > 0; ?>

                                <div class="col-lg-4 col-sm-12">
                                    <div class="cards mb-4">
                                        <div class="position-relative">
                                            <?php
                                            $images = json_decode($row['images'], true);
                                            $imagePath = '../LANDLORD/uploads/placeholder.jpg';
                                            if (!empty($images) && is_array($images) && isset($images[0])) {
                                                $imagePath = '../LANDLORD/uploads/' . $images[0];
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imagePath); ?>"
                                                alt="Property Image"
                                                class="property-img"
                                                style="width:100%; max-height:200px; object-fit:cover;">

                                            <!-- Status -->
                                            <div class="status">
                                                <p class="<?= $isApproved ? 'status-occupied' : 'status-available'; ?>">
                                                    <?= $isApproved ? "Occupied" : "Available"; ?>
                                                </p>
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

                                            <!-- LANDLORD ACTIONS -->
                                            <div class="d-flex justify-content-center align-items-center">
                                                <button class="small-button mx-2"
                                                    onclick="location.href='edit-property.php?ID=<?= $row['ID'] ?>'">Edit</button>

                                                <?php if ($isApproved): ?>
                                                    <button class="small-button"
                                                        onclick="location.href='landlord-rental.php?request_id=<?= $row['approved_rental_id'] ?>'">
                                                        View
                                                    </button>
                                                <?php else: ?>
                                                    <button class="small-button"
                                                        onclick="location.href='property-details.php?ID=<?= $row['ID'] ?>'">
                                                        View
                                                    </button>
                                                <?php endif; ?>



                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No listings found.</p>
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