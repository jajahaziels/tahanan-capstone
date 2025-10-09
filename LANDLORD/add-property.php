<?php
require_once '../connection.php';
include '../session_auth.php';

$errors = [];
$success = "";

// Landlord ID from session
$landlord_id = $_SESSION['landlord_id'] ?? null;

// ✅ Check verification status before allowing property posting
if ($landlord_id) {
    $sql = "SELECT verification_status FROM landlordtbl WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    if ($status !== 'verified') {
        $_SESSION['error'] = "❌ You must be verified before posting a property. Please upload your ID first.";
        header("Location: landlord-verification.php");
        exit;
    }
} else {
    $errors[] = "Landlord is not logged in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errors)) {
    // Collect inputs
    $listingName  = trim($_POST['listing_name'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $barangay     = $_POST['barangay'] ?? '';
    $price        = (float)($_POST['price'] ?? 0);
    $rooms        = (int)($_POST['rooms'] ?? 0);
    $category     = $_POST['category'] ?? '';
    $listingDesc  = trim($_POST['description'] ?? '');
    $latitude     = $_POST['latitude'] ?? null;
    $longitude    = $_POST['longitude'] ?? null;

    // Validation
    if ($listingName === '') $errors[] = "Listing name is required.";
    if ($address === '') $errors[] = "Address is required.";
    if ($barangay === '') $errors[] = "Barangay is required.";
    if ($price <= 0) $errors[] = "Price must be greater than 0.";
    if ($rooms <= 0) $errors[] = "Number of rooms is required.";
    if ($category === '') $errors[] = "Category is required.";
    if (empty($latitude) || empty($longitude)) $errors[] = "Location must be pinned on the map.";
    if (empty($_FILES['image']['name'][0])) $errors[] = "At least one image is required.";

    $listingDate = date('Y-m-d H:i:s');
    if ($listingDesc === '') $listingDesc = null;

    // Handle image uploads
    $uploadedImages = [];
    if (!empty($_FILES['image']['name'][0])) {
        $targetDir = __DIR__ . "/uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach ($_FILES['image']['name'] as $key => $name) {
            $tmpName = $_FILES['image']['tmp_name'][$key];
            $error   = $_FILES['image']['error'][$key];

            if ($error === UPLOAD_ERR_OK && !empty($tmpName)) {
                $newName = time() . "_" . uniqid() . "_" . basename($name);
                $targetFile = $targetDir . $newName;

                if (move_uploaded_file($tmpName, $targetFile)) {
                    $uploadedImages[] = $newName;
                } else {
                    $errors[] = "Failed to upload image: $name";
                }
            }
        }
    }

    // Save to database
    if (empty($errors)) {
        $imagesJson = json_encode($uploadedImages);

        $stmt = $conn->prepare("
            INSERT INTO listingtbl 
            (listingName, address, barangay, price, rooms, category, listingDesc, images, listingDate, latitude, longitude, landlord_id)  
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssiissssddi",
            $listingName,
            $address,
            $barangay,
            $price,
            $rooms,
            $category,
            $listingDesc,
            $imagesJson,
            $listingDate,
            $latitude,
            $longitude,
            $landlord_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Property added successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Show success message after redirect
if (!empty($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
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
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title>ADD PROPERTIES</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        .add-property {
            background-color: #0000ffb6;
            color: white;
            padding: 8px;
            border-radius: 20px;
        }

        .form-control {
            border: 2px solid var(--main-color);
        }

        #map {
            height: 400px;
            padding: 0 !important;
            margin: auto;
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
            <li><a href="../support.php">Support</a></li>
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
        <div class="container m-auto">
            <h2>Add Property</h2>
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-6">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success; ?></div>
                    <?php endif; ?>

                    <form class="mt-4" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Listing Name</label>
                                <input type="text" name="listing_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Barangay</label>
                                <select name="barangay" class="form-control" required>
                                    <option value="" disabled selected>Select Barangay</option>
                                    <option value="Bagong Silang">Bagong Silang</option>
                                    <option value="Calendola">Calendola</option>
                                    <option value="Chrysanthemum">Chrysanthemum</option>
                                    <!-- BASTA 27 TO -->
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="San Pedro" readonly>
                            </div>
                            <div class="col">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" class="form-control" value="Laguna" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Number of Rooms</label>
                                <select name="rooms" class="form-control" required>
                                    <option value="" disabled selected>Select No. of Rooms</option>
                                    <option value="1">1 Bedroom</option>
                                    <option value="2">2 Bedrooms</option>
                                    <option value="3">3 Bedrooms</option>
                                    <option value="4">4 Bedrooms</option>
                                    <option value="5">5+ Bedrooms</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="" disabled selected>Select Category</option>
                                    <option value="Condominium">Condominium</option>
                                    <option value="Apartment complex">Apartment complex</option>
                                    <option value="Single-family home">Single-family home</option>
                                    <option value="Townhouse">Townhouse</option>
                                    <option value="Low-rise apartment">Low-rise apartment</option>
                                    <option value="High-rise apartment">High-rise apartment</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"></textarea>
                            </div>
                            <div class="col">
                                <label class="form-label">Images</label>
                                <input type="file" name="image[]" class="form-control" accept="image/*" multiple>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label>Pin Location</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude">
                                <input type="hidden" name="longitude" id="longitude">
                            </div>
                        </div>
                        <div class="mb-5">
                            if (isset($_GET['success'])) {
                            echo "<script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Record deleted successfully!'
                                });
                            </script>";
                        }
                            <button type="submit" class="main-button mx-2">Add property</button>
                            <button class="main-button" onclick="location.href='landlord-properties.php'">Cancel</button>
                        </div>
                    </form>
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
    <!-- LEAFLET JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        // INITIALIZE MAP IN SAN PEDRO
        var map = L.map('map').setView([14.3647, 121.0556], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker;

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (marker) {
                map.removeLayer(marker);
            }

            marker = L.marker([lat, lng]).addTo(map).bindPopup("Selected Location").openPopup();

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
        });
    </script>
</body>

</html>