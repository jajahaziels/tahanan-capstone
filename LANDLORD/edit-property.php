<?php
require_once '../connection.php';
session_start();

$errors = [];
$success = "";

$ID = $_GET['ID'] ?? null;
if (!$ID) {
    die("Invalid request.");
}

$stmt = $conn->prepare("SELECT * FROM listingtbl WHERE ID = ?");
$stmt->bind_param("i", $ID);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    die("Property not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $listingName  = trim($_POST['listingName'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $barangay     = $_POST['barangay'] ?? '';
    $price        = $_POST['price'] ?? '';
    $rooms        = $_POST['rooms'] ?? '';
    $category     = $_POST['category'] ?? '';
    $listingDesc  = trim($_POST['description'] ?? '');
    $latitude     = $_POST['latitude'] ?? null;
    $longitude    = $_POST['longitude'] ?? null;

    // Validation
    if (empty($listingName)) $errors[] = "Listing name is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($barangay)) $errors[] = "Barangay is required.";
    if (empty($price) || $price <= 0) $errors[] = "Price must be greater than 0.";
    if (empty($rooms)) $errors[] = "Number of rooms is required.";
    if (empty($category)) $errors[] = "Category is required.";
    if (empty($latitude) || empty($longitude)) $errors[] = "Location must be pinned on the map.";

    // Get current images from DB
    $uploadedImages = json_decode($property['images'], true) ?? [];


    if (!empty($_POST['remove_images'])) {
        foreach ($_POST['remove_images'] as $removeImg) {
            // Delete from array
            if (($key = array_search($removeImg, $uploadedImages)) !== false) {
                unset($uploadedImages[$key]);

                // (Optional) also delete the actual file from server
                $filePath = "uploads/" . $removeImg;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
        // Reindex array
        $uploadedImages = array_values($uploadedImages);
    }

    if (!empty($_FILES['image']['name'][0])) {
        foreach ($_FILES['image']['name'] as $key => $name) {
            $tmpName = $_FILES['image']['tmp_name'][$key];
            $error   = $_FILES['image']['error'][$key];

            if ($error === UPLOAD_ERR_OK && !empty($tmpName)) {
                $newName = time() . "_" . uniqid() . "_" . basename($name);
                $targetFile = "uploads/" . $newName;

                if (move_uploaded_file($tmpName, $targetFile)) {
                    $uploadedImages[] = $newName;
                }
            }
        }
    }
    // // Limit to max 5 images
    // if (count($uploadedImages) > 5) {
    //     $errors[] = "You can upload a maximum of 5 images.";
    // }


    if (empty($errors)) {
        $imagesJson = json_encode($uploadedImages);

        $stmt = $conn->prepare("
            UPDATE listingtbl 
            SET listingName=?, address=?, barangay=?, price=?, rooms=?, category=?, listingDesc=?, images=?, latitude=?, longitude=?
            WHERE ID=?
        ");

        $stmt->bind_param(
            "sssiisssddi",
            $listingName,
            $address,
            $barangay,
            $price,
            $rooms,
            $category,
            $listingDesc,
            $imagesJson,
            $latitude,
            $longitude,
            $ID
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Property updated successfully!";
            header("Location: landlord-properties.php");
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
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
    <link rel="stylesheet" href="../css/style.css">
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <title>EDIT PROPERTIES</title>
    <style>
        .landlord-page {
            margin: 140px 0px 80px 0px !important;
        }

        .form-container {
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

        textarea {
            height: 190px;
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
            <div class="d-flex justify-content-between">
                <h2>Edit Property</h2>
                <form method="POST" action="delete-property.php" onsubmit="return confirm('Are you sure you want to delete this property?');">
                    <input type="hidden" name="id" value="<?= $property['ID']; ?>">
                    <button type="submit" class="main-button">Delete Property</button>
                </form>

            </div>
            <div class="row gy-4 justify-content-center">
                <div class="col-6">
                    <form method="POST" enctype="multipart/form-data" class="form-container">
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Listing Name</label>
                                <input type="text" name="listingName" class="form-control"
                                    value="<?= htmlspecialchars($property['listingName']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control"
                                    value="<?= htmlspecialchars($property['address']) ?>" required>
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

                        <!-- same for price, rooms, category -->
                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Price</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%);">₱</span>
                                    <input type="text" name="price" class="form-control" placeholder="0.00" style="padding-left: 25px;" value="<?= $property['price'] ?>" required>
                                </div>
                            </div>
                            <div class="col">
                                <label class="form-label">No. of Rooms</label>
                                <select name="rooms" class="form-control" required>
                                    <option value="1" <?= $property['rooms'] == 1 ? 'selected' : '' ?>>1 Bedroom</option>
                                    <option value="2" <?= $property['rooms'] == 2 ? 'selected' : '' ?>>2 Bedrooms</option>
                                    <option value="3" <?= $property['rooms'] == 3 ? 'selected' : '' ?>>3 Bedrooms</option>
                                    <option value="4" <?= $property['rooms'] == 4 ? 'selected' : '' ?>>4 Bedrooms</option>
                                    <option value="5" <?= $property['rooms'] == 5 ? 'selected' : '' ?>>5+ Bedrooms</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="Condominium" <?= $property['category'] == "Condominium" ? 'selected' : '' ?>>Condominium</option>
                                    <option value="Apartment complex" <?= $property['category'] == "Apartment complex" ? 'selected' : '' ?>>Apartment complex</option>
                                    <option value="Single-family home" <?= $property['category'] == "Single-family home" ? 'selected' : '' ?>>Single-family home</option>
                                    <option value="Townhouse" <?= $property['category'] == "Townhouse" ? 'selected' : '' ?>>Townhouse</option>
                                    <option value="Low-rise apartment" <?= $property['category'] == "Low-rise apartment" ? 'selected' : '' ?>>Low-rise apartment</option>
                                    <option value="High-rise apartment" <?= $property['category'] == "High-rise apartment" ? 'selected' : '' ?>>High-rise apartment</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-1">
                            <div class="col">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"><?= htmlspecialchars($property['listingDesc']) ?></textarea>
                            </div>
                            <div class="col">
                                <label class="form-label">Upload New Images (optional)</label>
                                <input type="file" name="image[]" class="form-control" accept="image/*" multiple>

                                <p>Current Images:</p>
                                <?php foreach (json_decode($property['images'], true) ?? [] as $img): ?>
                                    <div style="display:inline-block; margin:5px; text-align:center;">
                                        <img src="uploads/<?= $img ?>" alt="property image" width="80" style="display:block; margin-bottom:5px;">
                                        <label>
                                            <input type="checkbox" name="remove_images[]" value="<?= $img ?>"> Remove
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>

                        <!-- Map -->
                        <div class="row mb-3">
                            <div class="col">
                                <label>Pin Location</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude" value="<?= $property['latitude'] ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?= $property['longitude'] ?>">
                            </div>
                        </div>

                        <!-- Button -->
                        <div class="mb-3">
                            <button type="submit" class="main-button mx-2">Update property</button>
                            <button type="button" class="main-button" onclick="location.href='landlord-properties.php'">Cancel</button>
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
    <!-- GOOGLE MAPS API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWEGYpvzU62c47VL2_FCiMCtlNRk7VKl4&callback=initMap" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function initMap() {
    // Default coordinates if none
    var lat = parseFloat(document.getElementById('latitude').value) || 14.3647;
    var lng = parseFloat(document.getElementById('longitude').value) || 121.0556;
    
    var map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: lat, lng: lng},
        zoom: 15
    });

    // Marker
    var marker = new google.maps.Marker({
        position: {lat: lat, lng: lng},
        map: map,
        draggable: true
    });

    // Update hidden inputs when marker is dragged
    marker.addListener('dragend', function(event) {
        document.getElementById('latitude').value = event.latLng.lat();
        document.getElementById('longitude').value = event.latLng.lng();
    });

    // Click on map to move marker
    map.addListener('click', function(event) {
        var clickedLocation = event.latLng;
        marker.setPosition(clickedLocation);
        document.getElementById('latitude').value = clickedLocation.lat();
        document.getElementById('longitude').value = clickedLocation.lng();
    });
}
</script>

    <script>
        document.getElementById('deleteBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This property will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to PHP delete handler
                    window.location.href = 'delete-property.php?id=<?= $property_id ?>';
                }
            });
        });
    </script>
</body>