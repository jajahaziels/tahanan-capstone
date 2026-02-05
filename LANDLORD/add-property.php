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
    if ($rooms < 0) $errors[] = "Number of rooms is required.";
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
    <link rel="stylesheet" href="../css/style.css?v=<?= time(); ?>">
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- SWEET ALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>ADD PROPERTIES</title>
    <style>
        .landlord-page {
            margin-top: 140px !important;
        }

        form {
            background-color: var(--bg-color);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.165);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border: 2px solid var(--main-color) !important;
            background: var(--bg-alt-color) !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .form-control {
            background: var(--bg-alt-color);
        }

        #map {
            height: 300px;
            padding: 0 !important;
            margin: auto;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <?php include '../Components/landlord-header.php'; ?>

    <!-- PROPERTY PAGE -->
    <div class="landlord-page">
        <div class="container m-auto">
            <h2>Add Property</h2>
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-6">
                    <form id="property-form" method="POST" enctype="multipart/form-data" action="add-property.php">

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Listing Name</label>
                                <input type="text" name="listing_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Barangay</label>
                                <select name="barangay" class="form-control" required>
                                    <option value="" disabled selected>Select Barangay</option>
                                    <option value="Bagong Silang">Bagong Silang</option>
                                    <option value="Calendola">Calendola</option>
                                    <option value="Chrysanthemum">Chrysanthemum</option>
                                    <option value="Cuyab">Cuyab</option>
                                    <option value="Estrella">Estrella</option>
                                    <option value="Fatima">Fatima</option>
                                    <option value="G.S.I.S.">G.S.I.S.</option>
                                    <option value="Landayan">Landayan</option>
                                    <option value="Langgam">Langgam</option>
                                    <option value="Laram">Laram</option>
                                    <option value="Magsaysay">Magsaysay</option>
                                    <option value="Maharlika">Maharlika</option>
                                    <option value="Narra">Narra</option>
                                    <option value="Nueva">Nueva</option>
                                    <option value="Pacita 1">Pacita 1</option>
                                    <option value="Pacita 2">Pacita 2</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="Riverside">Riverside</option>
                                    <option value="Rosario">Rosario</option>
                                    <option value="Sampaguita Village">Sampaguita Village</option>
                                    <option value="San Antonio">San Antonio</option>
                                    <option value="San Roque">San Roque</option>
                                    <option value="San Vicente">San Vicente</option>
                                    <option value="San Lorenzo Ruiz">San Lorenzo Ruiz</option>
                                    <option value="Santo Niño">Santo Niño</option>
                                    <option value="United Bayanihan">United Bayanihan</option>
                                    <option value="United Better Living">United Better Living</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="San Pedro" readonly disabled>
                            </div>
                            <div class="col">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" class="form-control" value="Laguna" readonly disabled>
                            </div>
                        </div>

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Price</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">₱</span>
                                    <input type="number" name="price" class="form-control" placeholder="0.00" style="padding-left: 25px;" step="0.01" min="1" required>
                                </div>
                            </div>
                            <div class="col">
                                <label class="form-label">No. of Rooms</label>
                                <select name="rooms" class="form-control" required>
                                    <option value="" disabled selected>Select No. of Rooms</option>
                                    <option value="0">Studio Type</option>
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

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Images (Select multiple)</label>
                                <input type="file" name="image[]" class="form-control" accept="image/*" multiple required>
                                <small class="text-muted">You can select multiple images at once</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Pin Location on Map</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude" required>
                                <input type="hidden" name="longitude" id="longitude" required>
                                <small class="text-muted">Click on the map to pin your property location</small>
                            </div>
                        </div>
                        
                        <div class="mb-1">
                            <button type="submit" class="main-button mx-2">
                                <i class="fas fa-plus"></i> Add Property
                            </button>
                            <button type="button" class="main-button" onclick="location.href='landlord-properties.php'">
                                <i class="fas fa-times"></i> Cancel
                            </button>
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

        // Show success message if property was added
        <?php if (!empty($success)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Added Successfully!',
            text: 'Your property was added successfully.',
            confirmButtonColor: '#4caf50'
        }).then(() => {
            // Redirect to properties page
            window.location.href = 'landlord-properties.php';
        });
        <?php endif; ?>

        // Show error messages if any
        <?php if (!empty($errors)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Submission Error!',
            html: '<?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?>',
            confirmButtonColor: '#dc3545'
        });
        <?php endif; ?>
    </script>
</body>

</html>