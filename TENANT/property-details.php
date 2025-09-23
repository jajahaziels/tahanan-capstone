<?php
include '../connection.php';

// Accept both ?id=1 and ?ID=1
$idParam = $_GET['id'] ?? $_GET['ID'] ?? null;

if (!$idParam || !is_numeric($idParam)) {
    die("Invalid property ID.");
}

$ID = intval($idParam);

// Fetch property from DB
$stmt = $conn->prepare("SELECT * FROM listingtbl WHERE ID = ?");
$stmt->bind_param("i", $ID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Property not found.");
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
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- MAIN CSS -->
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title><?= htmlspecialchars($property['listingName']); ?> - Details</title>
    <style>
        .tenant-page {
            margin-top: 140px !important;
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
    <!-- HEADER -->
    <header>
        <a href="#" class="logo d-flex justify-content-center align-items-center"><img src="../img/logo.png" alt="">Tahanan</a>

        <ul class="nav-links">
            <li><a href="tenant.php">Home</a></li>
            <li><a href="tenant-rental.php">My Rental</a></li>
            <li><a href="tenant-favorite.php">Favorite</a></li>
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
                    <a href="tenant-profile.html">Account</a>
                    <a href="settings.html">Settings</a>
                    <a href="logout.html">Log out</a>
                </div>
            </div>
            <!-- NAVMENU -->
            <div class="fa-solid fa-bars" id="navmenu"></div>
        </div>
    </header>
    <div class="tenant-page">
        <div class="container m-auto">
            <div class="d-flex justify-content-start align-items-center">
                <button class="main-button" onclick="location.href='tenant.php'">Back</button>
            </div>
        </div>
        <div class="row justify-content-center align-items-center mt-5">
            <div class="col-lg-6 border p-3">

                <!-- Bootstrap Carousel -->
                <div id="carouselExample" class="carousel slide mb-4">
                    <div class="carousel-inner">
                        <?php if (!empty($images)): ?>
                            <?php foreach ($images as $index => $img): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-12">
                                            <img src="../LANDLORD/uploads/<?= htmlspecialchars($img); ?>"
                                                class="d-block w-100"
                                                style="max-height:400px; object-fit:cover;"
                                                alt="Property Image">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="carousel-item active">
                                <div class="row justify-content-center">
                                    <div class="col-lg-12">
                                        <img src="../LANDLORD/uploads/placeholder.jpg"
                                            class="d-block w-100"
                                            style="max-height:400px; object-fit:cover;"
                                            alt="No Image">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Controls -->
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>

                <!-- Property Info -->
                <h2><?= htmlspecialchars($property['listingName']); ?></h2>
                <p><strong>Price:</strong> ₱ <?= number_format($property['price']); ?></p>

                <ul>
                    <li><strong>Address:</strong> <?= htmlspecialchars($property['address']); ?>, <?= htmlspecialchars($property['barangay']); ?>, San Pedro, Laguna</li>
                    <li><strong>Category:</strong> <?= htmlspecialchars($property['category']); ?></li>
                    <li><strong>Rooms:</strong> <?= htmlspecialchars($property['rooms']); ?> Bedroom(s)</li>
                </ul>
                <h3>Property Description</h3>
                <p><?= nl2br(htmlspecialchars($property['listingDesc'] ?? "No description available.")); ?></p>

                <!-- Map -->
                <div id="map"></div>
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