<?php
require_once 'connection.php';

// Get property ID safely
$idParam = $_GET['id'] ?? $_GET['ID'] ?? null;
if (!$idParam || !is_numeric($idParam)) {
    echo "<div class='text-center mt-5 text-danger fw-bold'>⚠️ Invalid property ID.</div>";
    exit;
}
$listingID = intval($idParam);

// Fetch property (only if available)
$sql = "
    SELECT 
        l.ID AS listing_id,
        l.listingName,
        l.address,
        l.barangay,
        l.category,
        l.rooms,
        l.price,
        l.listingDesc,
        l.images,
        l.latitude,
        l.longitude,
        ld.ID AS landlord_id,
        ld.firstName AS landlord_fname,
        ld.lastName AS landlord_lname,
        ld.profilePic
    FROM listingtbl l
    JOIN landlordtbl ld ON l.landlord_id = ld.ID
    WHERE l.ID = ?
      AND l.ID NOT IN (SELECT listing_id FROM renttbl WHERE status = 'approved')
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $listingID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<div class='text-center mt-5 text-danger fw-bold'>⚠️ Property not found or already rented.</div>";
    exit;
}
$property = $result->fetch_assoc();
$images = json_decode($property['images'], true) ?? [];
$stmt->close();
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
    <link rel="stylesheet" href="../TAHANAN/css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../TAHANAN/css/style.css?v=<?= time(); ?>">
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title><?= htmlspecialchars($property['listingName']); ?> - Details</title>
    <style>
        .tenant-page {
            margin-top: 140px !important;
        }

        .prorperty-details {
            border: 3px solid var(--main-color);
            border-radius: 10px;
        }

        .back-button {
            position: fixed;
        }

        .price {
            font-size: 3rem;
        }

        .landlord-info {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--main-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }


        #map {
            height: 500px;
            padding: 0;
            margin: auto;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="img/logo.png" alt="">Tahanan</a>

        <ul class="nav-links">
            <li><a href="index.php#home">Home</a></li>
            <li><a href="index.php#services">Services</a></li>
            <li><a href="index.php#home-listing">Listing</a></li>
            <li><a href="index.php#testimonials">Testimonials</a></li>
            <li><a href="index.php#footer">Contact</a></li>
        </ul>
        <!-- SIGN UP -->
        <div class="nav-icons">
            <i class="fa-solid fa-user"></i>
            <a href="../TAHANAN/LOGIN/login.php">Sign In</a>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>
    <div class="tenant-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-start align-items-center">
                <button class="main-button back-button" onclick="location.href='index.php#home-listing'">Back</button>
            </div>
        </div>

        <div class="row justify-content-center align-items-center mt-5">
            <div class="col-lg-6 property-details p-3">

                <!-- Carousel -->
                <div id="carouselExample" class="carousel slide">
                    <div class="carousel-inner">
                        <?php if (!empty($images)): ?>
                            <?php foreach ($images as $index => $img): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-12">
                                            <img src="LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                                                class="d-block w-100"
                                                style="max-height:400px; object-fit:cover;"
                                                alt="Property Image">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="carousel-item active">
                                <img src="LANDLORD/uploads/placeholder.jpg" class="d-block w-100"
                                    style="max-height:400px; object-fit:cover;" alt="No Image">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Controls -->
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>

                <!-- Property Info -->
                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0"><?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</p>

                    <!-- Instead of Apply button, show Login prompt -->
                    <button type="button" class="main-button mx-5" onclick="window.location.href='../TAHANAN/LOGIN/login.php'">
                        Log in to Apply
                    </button>
                </div>

                <h4><?= htmlspecialchars($property['listingName']); ?></h4>
                <h1 class="price">
                    ₱ <?= number_format($property['price']); ?>.00
                    <small class="text-muted fs-5">/month</small>
                </h1>

                <!-- Landlord Info -->
                <div class="d-flex align-items-center p-2 border rounded mb-4 mt-4">
                    <div class="avatar me-3">
                        <?php if (!empty($property['profilePic'])): ?>
                            <img src="LANDLORD/uploads/<?= htmlspecialchars($property['profilePic']); ?>" alt="Profile">
                        <?php else: ?>
                            <div class="landlord-info">
                                <?= strtoupper(substr($property['landlord_fname'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="info flex-grow-1 mt-2">
                        <h1 class="mb-0">
                            <?= htmlspecialchars(ucwords(strtolower($property['landlord_fname'] . ' ' . $property['landlord_lname']))); ?>
                        </h1>
                        <p class="text-muted">Landlord</p>
                    </div>

                    <!-- Actions (disabled for guests) -->
                    <div class="d-flex">
                        <button class="small-button" disabled title="Login to view landlord profile">
                            <i class="fa-solid fa-user"></i>
                        </button>
                        <button class="small-button mx-3" disabled title="Login to chat">
                            <i class="fas fa-comment-dots"></i>
                        </button>
                    </div>
                </div>

                <!-- Property Description -->
                <h3>Property Description</h3>
                <p><?= nl2br(htmlspecialchars($property['listingDesc'] ?? "No description available.")); ?></p>
                <ul>
                    <li><strong>Address:</strong> <?= htmlspecialchars($property['address']); ?>, <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</li>
                    <li><strong>Category:</strong> <?= htmlspecialchars($property['category']); ?></li>
                    <li><strong>Rooms:</strong> <?= htmlspecialchars($property['rooms']); ?> Bedroom(s)</li>
                </ul>

                <div id="map"></div>
            </div>
        </div>
    </div>




    <!-- MAIN JS -->
    <script src="../js/script.js" defer></script>
    <!-- BS JS -->
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SCROLL REVEAL -->
    <script src="https://unpkg.com/scrollreveal"></script>
    <!-- LEAFLET JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        var lat = <?= $property['latitude'] ?: 14.3647 ?>;
        var lng = <?= $property['longitude'] ?: 121.0556 ?>;

        var map = L.map('map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map).bindPopup("<?= htmlspecialchars($property['listingName']); ?>");
    </script>
</body>